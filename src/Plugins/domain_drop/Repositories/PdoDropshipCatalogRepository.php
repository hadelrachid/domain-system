<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use PDO;

/**
 * Repositório PDO para o Catálogo de Produtos Minerados do DomainDrop (DIP / SRP).
 */
class PdoDropshipCatalogRepository implements DropshipCatalogRepositoryInterface
{
    private PDO $pdo;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
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
            $where[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = '%' . $criteria['search'] . '%';
            $params[] = '%' . $criteria['search'] . '%';
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
            } elseif ($sortVal === 'price_asc') {
                $orderBy = "sale_price_brl ASC, id DESC";
            } elseif ($sortVal === 'price_desc') {
                $orderBy = "sale_price_brl DESC, id DESC";
            } elseif ($sortVal === 'rating') {
                $orderBy = "rating DESC, id DESC";
            }
        }

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

        $sql = "SELECT COUNT(*) FROM domain_drop_catalog";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Constrói cláusula SQL inteligente para categoria com tolerância a apelidos e termos semânticos.
     *
     * @param array<int, mixed> $params
     */
    private function buildCategoryClause(string $category, array &$params): string
    {
        $raw = trim($category);
        $lower = mb_strtolower($raw, 'UTF-8');

        return match ($lower) {
            'tecnologia', 'tecnologia & gadgets', 'gadgets', 'eletrônicos', 'eletrônicos & gadget', 'eletronicos', 'eletronicos & gadget' =>
                "(category IN ('Tecnologia & Gadgets', 'Eletrônicos & Gadget', 'Eletrônicos') OR category LIKE '%Tecnologia%' OR category LIKE '%Eletrônicos%' OR category LIKE '%Eletronicos%' OR category LIKE '%Gadget%')",

            'smartwatches', 'smartwatch', 'smartwatches & áudio', 'smartwatches & audio', 'áudio', 'audio' =>
                "(category IN ('Smartwatches & Áudio', 'Smartwatches & Audio') OR category LIKE '%Smartwatch%' OR category LIKE '%Áudio%' OR category LIKE '%Audio%' OR title LIKE '%Smartwatch%' OR title LIKE '%Relógio%' OR title LIKE '%Watch%' OR title LIKE '%Fone%' OR title LIKE '%Tws%')",

            'casa', 'casa inteligente', 'casa inteligente & organização', 'casa inteligente & organizacao', 'casa & organização' =>
                "(category IN ('Casa Inteligente', 'Casa Inteligente & Organização') OR category LIKE '%Casa%')",

            'acessorios', 'acessórios', 'acessórios virais', 'acessorios virais', 'ferramentas', 'ferramentas & utilidades' =>
                "(category IN ('Acessórios Virais', 'Ferramentas & Utilidades', 'Acessórios para Veículos') OR category LIKE '%Acessório%' OR category LIKE '%Acessorio%' OR category LIKE '%Ferramenta%')",

            'beleza', 'saúde', 'saude', 'saúde & cuidados', 'saude & cuidados', 'beleza, cuidados & estilo', 'estilo' =>
                "(category IN ('Saúde & Cuidados', 'Beleza, Cuidados & Estilo') OR category LIKE '%Beleza%' OR category LIKE '%Saúde%' OR category LIKE '%Saude%' OR category LIKE '%Cuidado%')",

            'moda', 'roupas', 'vestuário', 'vestuario', 'moda, roupas & vestuário', 'moda, roupas & vestuario', 'moda & vestuário' =>
                "(category IN ('Moda, Roupas & Vestuário', 'Roupas', 'Vestuário') OR category LIKE '%Moda%' OR category LIKE '%Roupa%' OR category LIKE '%Vestuário%' OR category LIKE '%Vestuario%')",

            'lingerie', 'lingeries', 'moda íntima', 'moda intima', 'moda íntima, lingeries & pijamas', 'moda intima, lingeries & pijamas', 'pijamas' =>
                "(category IN ('Moda Íntima, Lingeries & Pijamas', 'Lingeries') OR category LIKE '%Lingerie%' OR category LIKE '%Íntima%' OR category LIKE '%Intima%')",

            'sex shop', 'sexshop', 'adulto', 'adultos', 'produtos adultos', 'sex shop & produtos adultos (+18)', '+18' =>
                "(category IN ('Sex Shop & Produtos Adultos (+18)', 'Sex Shop') OR category LIKE '%Sex Shop%' OR category LIKE '%Adulto%' OR category LIKE '%+18%')",

            'pets', 'pet shop', 'pet shop & pets virais' =>
                "(category IN ('Pet Shop & Pets Virais', 'Pet Shop') OR category LIKE '%Pet%')",

            'veiculos', 'veículos', 'acessórios para veículos', 'acessorios para veiculos', 'automotivo' =>
                "(category IN ('Acessórios para Veículos') OR category LIKE '%Veículo%' OR category LIKE '%Veiculo%' OR category LIKE '%Auto%')",

            default => (function() use ($raw, &$params) {
                $params[] = $raw;
                $params[] = '%' . $raw . '%';
                return "(category = ? OR category LIKE ?)";
            })()
        };
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

    public function getCategories(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT category FROM domain_drop_catalog WHERE category != '' ORDER BY category ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
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
