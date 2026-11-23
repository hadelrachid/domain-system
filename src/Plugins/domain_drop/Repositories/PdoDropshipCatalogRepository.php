<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use PDO;

/**
 * Repositório PDO para o Catálogo de Produtos Minerados do DomainDrop (DIP / SRP).
 */
class PdoDropshipCatalogRepository implements DropshipCatalogRepositoryInterface
{
    private PDO $pdo;
    private CategoryTaxonomyInterface $taxonomy;

    public function __construct(
        DatabaseConnectionInterface|PDO $connection,
        ?CategoryTaxonomyInterface $taxonomy = null
    ) {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
        $this->taxonomy = $taxonomy ?? new CategoryTaxonomyService();
        DomainDropSchemaEnsurer::ensure($this->pdo);
    }

    public function save(DropshipProductDto $product): int
    {
        $existing = $this->findByAliExpressId($product->aliexpressId);
        $now = date('Y-m-d H:i:s');
        $imagesJson = json_encode($product->images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $attrJson = json_encode($product->attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($existing !== null && $existing->id !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE domain_drop_catalog
                SET title = ?, slug = ?, cost_price_usd = ?, sale_price_brl = ?,
                    main_image_url = ?, images = ?, category = ?, description = ?,
                    rating = ?, orders_count = ?, attributes = ?, status = ?, updated_at = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $product->title,
                $product->slug,
                $product->costPriceUsd,
                $product->salePriceBrl,
                $product->mainImageUrl,
                $imagesJson,
                $product->category,
                $product->description,
                $product->rating,
                $product->ordersCount,
                $attrJson,
                $product->status,
                $now,
                $existing->id,
            ]);

            return $existing->id;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_catalog
                (aliexpress_id, title, slug, cost_price_usd, sale_price_brl, main_image_url, images, category, description, rating, orders_count, attributes, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product->aliexpressId,
            $product->title,
            $product->slug,
            $product->costPriceUsd,
            $product->salePriceBrl,
            $product->mainImageUrl,
            $imagesJson,
            $product->category,
            $product->description,
            $product->rating,
            $product->ordersCount,
            $attrJson,
            $product->status,
            $now,
            $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?DropshipProductDto
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_catalog WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findByAliExpressId(string $aliexpressId): ?DropshipProductDto
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_catalog WHERE aliexpress_id = ? LIMIT 1");
        $stmt->execute([$aliexpressId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?DropshipProductDto
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_catalog WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function list(array $criteria = [], int $limit = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($criteria['category'])) {
            $where[] = $this->buildCategoryClause((string)$criteria['category'], $params);
        }

        if (!empty($criteria['slug'])) {
            $where[] = "slug = ?";
            $params[] = (string)$criteria['slug'];
        }

        if (!empty($criteria['status'])) {
            $where[] = "status = ?";
            $params[] = (string)$criteria['status'];
        }

        if (!empty($criteria['search'])) {
            $where[] = $this->buildSearchClause((string)$criteria['search'], $params);
        }

        $sql = "SELECT * FROM domain_drop_catalog";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $orderBy = "id DESC";
        if (!empty($criteria['sort'])) {
            $sortVal = (string)$criteria['sort'];
            if ($sortVal === 'sales' || $sortVal === 'best_sellers' || $sortVal === 'volume_desc') {
                $orderBy = "orders_count DESC, id DESC";
            } elseif ($sortVal === 'updated_desc' || $sortVal === 'recent' || $sortVal === 'cycle') {
                $orderBy = "updated_at DESC, id DESC";
            } elseif ($sortVal === 'price_asc') {
                $orderBy = "sale_price_brl ASC, id DESC";
            } elseif ($sortVal === 'price_desc') {
                $orderBy = "sale_price_brl DESC, id DESC";
            } elseif ($sortVal === 'rating') {
                $orderBy = "rating DESC, id DESC";
            }
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $sql .= " ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'hydrate'], $rows);
    }

    public function count(array $criteria = []): int
    {
        $where = [];
        $params = [];

        if (!empty($criteria['category'])) {
            $where[] = $this->buildCategoryClause((string)$criteria['category'], $params);
        }

        if (!empty($criteria['slug'])) {
            $where[] = "slug = ?";
            $params[] = (string)$criteria['slug'];
        }

        if (!empty($criteria['status'])) {
            $where[] = "status = ?";
            $params[] = (string)$criteria['status'];
        }

        if (!empty($criteria['search'])) {
            $where[] = $this->buildSearchClause((string)$criteria['search'], $params);
        }

        $sql = "SELECT COUNT(*) FROM domain_drop_catalog";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Constrói cláusula SQL inteligente para busca textual tolerante a tokens, plural/singular,
     * remoção de preposições (stop words) e sinônimos frequentes de mercado.
     *
     * @param array<int, mixed> $params
     */
    private function buildSearchClause(string $search, array &$params): string
    {
        $search = trim($search);
        if ($search === '') {
            return '1=1';
        }

        $stopWords = ['de', 'da', 'do', 'das', 'dos', 'em', 'na', 'no', 'nas', 'nos', 'com', 'por', 'para', 'pra', 'um', 'uma', 'uns', 'umas', 'e', 'ou', 'a', 'o', 'as', 'os', 'ao', 'aos', 'que', 'se'];

        // Separa em palavras/tokens limpos
        $rawTokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($search, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Filtra stop words se sobrarem outros termos significativos
        $filtered = array_values(array_filter($rawTokens, fn($t) => !in_array($t, $stopWords, true)));
        $tokens = !empty($filtered) ? $filtered : $rawTokens;

        if (empty($tokens)) {
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            return "(title LIKE ? OR description LIKE ? OR category LIKE ?)";
        }

        $tokenClauses = [];
        foreach ($tokens as $token) {
            $variants = [$token];

            // 1. Versão sem acentos
            $unaccented = preg_replace(
                ['/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòõôö]/u', '/[úùûü]/u', '/[ç]/u'],
                ['a', 'e', 'i', 'o', 'u', 'c'],
                $token
            );
            if ($unaccented !== $token) {
                $variants[] = $unaccented;
            }

            // 2. Singular / Plural inteligente em português
            if (mb_strlen($token, 'UTF-8') > 3 && str_ends_with($token, 's')) {
                $variants[] = mb_substr($token, 0, -1, 'UTF-8');
                if (str_ends_with($token, 'es')) {
                    $variants[] = mb_substr($token, 0, -2, 'UTF-8');
                }
            } elseif (mb_strlen($token, 'UTF-8') >= 3) {
                $variants[] = $token . 's';
            }

            // 3. Normalização das variantes geradas
            foreach ($variants as $v) {
                $unacc = preg_replace(
                    ['/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòõôö]/u', '/[úùûü]/u', '/[ç]/u'],
                    ['a', 'e', 'i', 'o', 'u', 'c'],
                    $v
                );
                if ($unacc !== $v) {
                    $variants[] = $unacc;
                }
            }

            // 4. Sinônimos de mercado comuns
            if (in_array($unaccented, ['relogio', 'relogios', 'smartwatch', 'smartwatches', 'watch'], true)) {
                $variants[] = 'relogio';
                $variants[] = 'relógio';
                $variants[] = 'smartwatch';
                $variants[] = 'watch';
            } elseif (in_array($unaccented, ['fone', 'fones', 'ouvido', 'tws', 'headset'], true)) {
                $variants[] = 'fone';
                $variants[] = 'tws';
                $variants[] = 'ouvido';
            } elseif (in_array($unaccented, ['lampada', 'lampadas', 'luminaria', 'luminarias'], true)) {
                $variants[] = 'lampada';
                $variants[] = 'lâmpada';
                $variants[] = 'luminaria';
                $variants[] = 'luminária';
            } elseif (in_array($unaccented, ['secador', 'secadora', 'secadores', 'escova', 'escovas'], true)) {
                $variants[] = 'secador';
                $variants[] = 'secadora';
                $variants[] = 'escova';
            }

            $variants = array_unique(array_filter($variants, fn($v) => mb_strlen($v, 'UTF-8') >= 2));

            $subClauses = [];
            foreach ($variants as $v) {
                $subClauses[] = "(title LIKE ? OR description LIKE ? OR category LIKE ?)";
                $params[] = '%' . $v . '%';
                $params[] = '%' . $v . '%';
                $params[] = '%' . $v . '%';
            }

            $tokenClauses[] = '(' . implode(' OR ', $subClauses) . ')';
        }

        return '(' . implode(' AND ', $tokenClauses) . ')';
    }

    /**
     * Constrói cláusula SQL inteligente para categoria delegando para a Taxonomia Desacoplada (SOLID / DIP).
     *
     * @param array<int, mixed> $params
     */
    private function buildCategoryClause(string $category, array &$params): string
    {
        return $this->taxonomy->buildSqlFilter($category, $params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM domain_drop_catalog WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updatePrice(int $id, float $salePriceBrl, ?float $costPriceUsd = null): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($costPriceUsd !== null && $costPriceUsd >= 0) {
            $stmt = $this->pdo->prepare("
                UPDATE domain_drop_catalog
                SET sale_price_brl = ?, cost_price_usd = ?, updated_at = ?
                WHERE id = ?
            ");
            return $stmt->execute([$salePriceBrl, $costPriceUsd, $now, $id]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE domain_drop_catalog
            SET sale_price_brl = ?, updated_at = ?
            WHERE id = ?
        ");
        return $stmt->execute([$salePriceBrl, $now, $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE domain_drop_catalog 
            SET status = ?, updated_at = ? 
            WHERE id = ?
        ");

        return $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    public function getCategories(): array
    {
        $canonicals = $this->taxonomy->getCanonicalCategories();
        $stmt = $this->pdo->query("SELECT DISTINCT category FROM domain_drop_catalog WHERE category != '' ORDER BY category ASC");
        $dbCats = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        return array_values(array_unique(array_merge($canonicals, $dbCats)));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): DropshipProductDto
    {
        $images = [];
        if (!empty($row['images'])) {
            $decoded = json_decode((string)$row['images'], true);
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }

        $attributes = [];
        if (!empty($row['attributes'])) {
            $decoded = json_decode((string)$row['attributes'], true);
            if (is_array($decoded)) {
                $attributes = $decoded;
            }
        }

        return new DropshipProductDto(
            aliexpressId: (string)$row['aliexpress_id'],
            title: (string)$row['title'],
            slug: (string)$row['slug'],
            costPriceUsd: (float)($row['cost_price_usd'] ?? 0.0),
            salePriceBrl: (float)($row['sale_price_brl'] ?? 0.0),
            mainImageUrl: (string)$row['main_image_url'],
            images: $images,
            category: (string)($row['category'] ?? 'Geral'),
            description: (string)($row['description'] ?? ''),
            rating: (float)($row['rating'] ?? 4.8),
            ordersCount: (int)($row['orders_count'] ?? 0),
            attributes: $attributes,
            status: (string)($row['status'] ?? 'published'),
            id: (int)$row['id']
        );
    }
}
