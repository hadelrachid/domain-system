<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\CategoryRegistryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;
use DomainSystem\Plugins\domain_drop\DTO\DepartmentDto;
use DomainSystem\Plugins\domain_drop\Repositories\DomainDropSchemaEnsurer;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use PDO;
use PHPUnit\Framework\TestCase;

class CategoryContractDecouplingTest extends TestCase
{
    private PDO $pdo;
    private CategoryTaxonomyService $taxonomy;
    private PdoDropshipCatalogRepository $catalogRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        DomainDropSchemaEnsurer::ensure($this->pdo);

        $this->taxonomy = new CategoryTaxonomyService();
        $this->catalogRepo = new PdoDropshipCatalogRepository($this->pdo, $this->taxonomy);
    }

    public function testDepartmentRegistryContractImplementsInterfacesAndProvidesDtos(): void
    {
        $this->assertInstanceOf(CategoryRegistryInterface::class, $this->taxonomy);
        $this->assertInstanceOf(CategoryTaxonomyInterface::class, $this->taxonomy);

        $departments = $this->taxonomy->getAllDepartments();
        $this->assertCount(11, $departments);

        $this->assertArrayHasKey('auto', $departments);
        $this->assertArrayHasKey('fashion', $departments);
        $this->assertArrayHasKey('lingerie', $departments);
        $this->assertArrayHasKey('fitness', $departments);
        $this->assertArrayHasKey('tools', $departments);

        $autoDept = $departments['auto'];
        $this->assertInstanceOf(DepartmentDto::class, $autoDept);
        $this->assertSame('auto', $autoDept->code);
        $this->assertSame('Peças & Acessórios Automotivos', $autoDept->name);
        $this->assertSame('🚗', $autoDept->icon);
        $this->assertContains(34, $autoDept->aliExpressCategoryIds);
        $this->assertContains('Peças & Acessórios Automotivos', $autoDept->databaseCategories);

        // JSON contract export
        $json = $this->taxonomy->toDepartmentJson();
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertCount(11, $decoded);
    }

    public function testZeroContaminationBetweenAutoPartsAndClothing(): void
    {
        // 1. Roupas e Vestuário
        $this->insertItem(1, 'Calça Jeans Masculina Skinny Stretch', 'Roupas, Bolsas & Calçados');
        $this->insertItem(2, 'Vestido Feminino Floral Verão Elegante', 'Roupas, Bolsas & Calçados');
        $this->insertItem(3, 'Tops De Academia Feminino, Tecido De Peça Única Sem Costuras', 'Moda Fitness, Leggings & Academia');

        // 2. Roupas Íntimas
        $this->insertItem(4, 'Kit 4 Calcinhas Renda Sem Costura', 'Roupa Íntima & Pijamas');
        $this->insertItem(5, 'Sutiã Sustentação Push Up Confortável', 'Roupa Íntima & Pijamas');

        // 3. Peças Automotivas
        $this->insertItem(6, 'Suporte Celular Veicular Magnético 360 Para Carro', 'Peças & Acessórios Automotivos');
        $this->insertItem(7, 'Palheta Para-brisa Automotiva Silicone Universal', 'Peças & Acessórios Automotivos');

        // 4. Ferramentas
        $this->insertItem(8, 'Kit Jogo de Chaves e Alicate Catraca 46 Peças', 'Ferramentas & Utilidades');

        // Teste A: Busca estrita pela categoria 'Peças & Acessórios Automotivos' (ou código 'auto')
        $autoProducts = $this->catalogRepo->list(['category' => 'Peças & Acessórios Automotivos']);
        $autoIds = array_map(fn($p) => (int)$p->id, $autoProducts);

        $this->assertCount(2, $autoProducts, 'Deve conter estritamente os 2 itens automotivos');
        $this->assertContains(6, $autoIds);
        $this->assertContains(7, $autoIds);
        // PROVA DE FOGO: Nenhuma roupa ("peça única", "calça", "vestido") pode vazar para Auto!
        $this->assertNotContains(1, $autoIds, 'Calça Jeans NUNCA pode vazar para Peças Automotivas');
        $this->assertNotContains(2, $autoIds, 'Vestido NUNCA pode vazar para Peças Automotivas');
        $this->assertNotContains(3, $autoIds, 'Top de Peça Única NUNCA pode vazar para Peças Automotivas');
        $this->assertNotContains(4, $autoIds, 'Lingerie NUNCA pode vazar para Peças Automotivas');
        $this->assertNotContains(8, $autoIds, 'Ferramentas de 46 Peças NUNCA pode vazar para Peças Automotivas');

        // Teste B: Busca estrita pela categoria 'Roupas, Bolsas & Calçados' (ou código 'fashion')
        $fashionProducts = $this->catalogRepo->list(['category' => 'Roupas, Bolsas & Calçados']);
        $fashionIds = array_map(fn($p) => (int)$p->id, $fashionProducts);

        $this->assertCount(2, $fashionProducts, 'Deve conter estritamente os 2 itens de moda');
        $this->assertContains(1, $fashionIds);
        $this->assertContains(2, $fashionIds);
        // PROVA DE FOGO: Nenhuma peça automotiva nem lingerie pode vazar para Roupas!
        $this->assertNotContains(4, $fashionIds, 'Calcinha/Lingerie NUNCA deve vazar para Roupas Gerais');
        $this->assertNotContains(5, $fashionIds, 'Sutiã NUNCA deve vazar para Roupas Gerais');
        $this->assertNotContains(6, $fashionIds, 'Suporte Veicular NUNCA deve vazar para Roupas');
        $this->assertNotContains(8, $fashionIds, 'Ferramentas NUNCA deve vazar para Roupas');

        // Teste C: Busca estrita pela categoria 'Roupa Íntima & Pijamas' (ou código 'lingerie')
        $lingerieProducts = $this->catalogRepo->list(['category' => 'Roupa Íntima & Pijamas']);
        $lingerieIds = array_map(fn($p) => (int)$p->id, $lingerieProducts);

        $this->assertCount(2, $lingerieProducts, 'Deve conter estritamente os 2 itens de roupa íntima');
        $this->assertContains(4, $lingerieIds);
        $this->assertContains(5, $lingerieIds);
        $this->assertNotContains(1, $lingerieIds, 'Calça Jeans NUNCA deve vazar para Lingeries');
        $this->assertNotContains(6, $lingerieIds, 'Suporte Veicular NUNCA deve vazar para Lingeries');
    }

    private function insertItem(int $id, string $title, string $category): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_catalog (id, aliexpress_id, title, slug, cost_price_usd, sale_price_brl, main_image_url, category, status, rating, orders_count, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published', 4.8, 100, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $id,
            'ali_' . $id,
            $title,
            'slug-' . $id,
            10.0,
            50.0,
            'https://example.com/img.jpg',
            $category
        ]);
    }
}
