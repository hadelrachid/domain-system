<?php

declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use DomainSystem\Plugins\domain_drop\Repositories\DomainDropSchemaEnsurer;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;

/**
 * Testes Unitários de Relevância Semântica e Zero-Contaminação em Outros Vendedores / Ofertas Similares.
 */
class DomainDropSimilarOffersTest extends TestCase
{
    private PDO $pdo;
    private PdoDropshipCatalogRepository $repo;

    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) {
            $this->markTestSkipped('Plugin agregado [domain_drop] não está presente no checkout.');
            return;
        }
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        DomainDropSchemaEnsurer::resetCache();
        DomainDropSchemaEnsurer::ensure($this->pdo);

        $taxonomy = new CategoryTaxonomyService();
        $this->repo = new PdoDropshipCatalogRepository($this->pdo, $taxonomy);
    }

    private function insertProduct(int $id, string $title, string $category, float $price, string $status = 'published'): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_catalog (id, aliexpress_id, title, slug, cost_price_usd, sale_price_brl, category, status, main_image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'https://ae01.alicdn.com/kf/img.jpg')
        ");
        $stmt->execute([
            $id,
            (string)(1005000000000 + $id),
            $title,
            'slug-' . $id,
            $price / 5.5,
            $price,
            $category,
            $status
        ]);
    }

    public function testSimilarOffersPrioritizesSameProductKeywordsAndBlocksUnrelatedNiches(): void
    {
        // Produtos no catálogo
        $this->insertProduct(1, 'Calça Jeans Masculina Skinny Azul', 'Moda, Roupas & Vestuário', 119.90);
        $this->insertProduct(2, 'Calça Jeans Feminina Cintura Alta', 'Moda, Roupas & Vestuário', 99.90);
        $this->insertProduct(3, 'Calça Moletom Conforto Flanelada', 'Moda, Roupas & Vestuário', 79.90);
        $this->insertProduct(4, 'Vestido Longo Estampado Floral', 'Moda, Roupas & Vestuário', 89.90);
        
        // Produtos de nichos totalmente diferentes
        $this->insertProduct(5, 'Smart TV 55 Polegadas 4K UHD', 'Smartphones, TVs & Projetores 4K', 2499.00);
        $this->insertProduct(6, 'Ração Seca para Cães Adultos 15kg', 'Pet Shop & Cuidados Animais', 149.90);
        $this->insertProduct(7, 'Jogo de Chaves e Ferramentas 46 Peças', 'Ferramentas & Construção', 69.90);

        // Busca ofertas similares para o produto 1 (Calça Jeans)
        $similar = $this->repo->findSimilarOffers(
            excludeId: 1,
            category: 'Moda, Roupas & Vestuário',
            title: 'Calça Jeans Masculina Skinny Azul',
            limit: 4,
            targetPrice: 119.90
        );

        $this->assertNotEmpty($similar, 'Deve encontrar ofertas similares');
        $this->assertLessThanOrEqual(4, count($similar));

        $similarIds = array_map(fn($p) => (int)$p->id, $similar);
        $this->assertNotContains(1, $similarIds, 'Nunca inclui o próprio produto');
        
        // PROVA DE ZERO-CONTAMINAÇÃO: TV, Pet Shop e Ferramentas NUNCA devem aparecer na página de Calça
        $this->assertNotContains(5, $similarIds, 'Smart TV NUNCA deve aparecer como similar de Calça Jeans');
        $this->assertNotContains(6, $similarIds, 'Ração Pet NUNCA deve aparecer como similar de Calça Jeans');
        $this->assertNotContains(7, $similarIds, 'Ferramentas NUNCA devem aparecer como similar de Calça Jeans');

        // Itens de calça devem ser priorizados pelas palavras-chave "calça" e "jeans"
        $this->assertContains(2, $similarIds, 'Calça Jeans Feminina deve ser recomendada');
        $this->assertContains(3, $similarIds, 'Calça Moletom deve ser recomendada');
    }

    public function testZeroContaminationFallbackWhenNicheHasFewerItems(): void
    {
        // Catálogo com apenas 1 item de moda alternativo
        $this->insertProduct(10, 'Calça Alfaiataria Masculina Preta', 'Moda, Roupas & Vestuário', 150.00);
        $this->insertProduct(11, 'Bermuda Jeans Masculina Casual', 'Moda, Roupas & Vestuário', 80.00);
        
        // Catálogo com vários outros itens de outros nichos
        $this->insertProduct(20, 'Fone de Ouvido Bluetooth Sem Fio', 'Eletrônicos & Gadget', 59.90);
        $this->insertProduct(21, 'Smartwatch Relógio Inteligente D20', 'Relógios & Smartwatches', 49.90);
        $this->insertProduct(22, 'Cama Almofadada para Cachorro', 'Pet Shop & Cuidados Animais', 79.90);

        // Pede 4 itens similares para o produto 10
        $similar = $this->repo->findSimilarOffers(
            excludeId: 10,
            category: 'Moda, Roupas & Vestuário',
            title: 'Calça Alfaiataria Masculina Preta',
            limit: 4,
            targetPrice: 150.00
        );

        // Deve retornar APENAS o 1 item de moda existente (Bermuda Jeans), NÃO preencher com Fone, Smartwatch ou Pet!
        $this->assertCount(1, $similar, 'Deve retornar estritamente 1 item e JAMAIS vazar itens de outros nichos para preencher o limite');
        $this->assertSame(11, (int)$similar[0]->id, 'O único item retornado deve ser a Bermuda Jeans da mesma família');
    }

    /**
     * Teste dos 3 Filtros de Sócrates no caso real relatado pelo usuário:
     * Produto: "Tops De Academia Feminino, Tecido De Peça Única Sem Costuras"
     * Contaminantes que NUNCA podem aparecer:
     * - "Suporte Ajustável Para Notebook – Base Dobrável Em Alumínio Até"
     * - "Kit 2 Prateleiras Suporte Com Alto Adesivos Para Parede Banheiro"
     */
    public function testSocratesThreeFiltersIsolateTopsDeAcademiaFromNotebookAndBathroomSupports(): void
    {
        // 1. O produto da página
        $this->insertProduct(100, 'Tops De Academia Feminino, Tecido De Peça Única Sem Costuras', 'Moda, Roupas & Vestuário', 39.90);

        // 2. Ofertas legítimas do mesmo nicho e tipo
        $this->insertProduct(101, 'Top Fitness Nadador Feminino Academia Sem Bojo', 'Moda Fitness, Leggings & Academia', 35.00);
        $this->insertProduct(102, 'Top Cropped Academia Alças Duplas Conforto', 'Moda, Roupas & Vestuário', 42.00);
        $this->insertProduct(103, 'Calça Legging Fitness Feminina Cintura Alta', 'Moda Fitness, Leggings & Academia', 59.90);

        // 3. Os contaminantes relatados pelo usuário
        $this->insertProduct(200, 'Suporte Ajustável Para Notebook – Base Dobrável Em Alumínio Até', 'Eletrônicos & Gadget', 49.90);
        $this->insertProduct(201, 'Kit 2 Prateleiras Suporte Com Alto Adesivos Para Parede Banheiro', 'Casa Inteligente & Organização', 32.50);

        // Aplicação dos 3 Filtros de Sócrates
        $similar = $this->repo->findSimilarOffers(
            excludeId: 100,
            category: 'Moda, Roupas & Vestuário',
            title: 'Tops De Academia Feminino, Tecido De Peça Única Sem Costuras',
            limit: 4,
            targetPrice: 39.90
        );

        $this->assertNotEmpty($similar);
        $similarIds = array_map(fn($p) => (int)$p->id, $similar);

        // 1º FILTRO (Categoria): Contaminantes de notebook e banheiro BARRADOS com 100% de eficácia
        $this->assertNotContains(200, $similarIds, 'Suporte de Notebook é 100% bloqueado pelo 1º Filtro de Sócrates');
        $this->assertNotContains(201, $similarIds, 'Prateleira de Banheiro é 100% bloqueada pelo 1º Filtro de Sócrates');

        // 2º FILTRO (Semântica): Tops de Academia são priorizados no topo
        $this->assertSame(101, $similarIds[0], 'O Top Nadador é a oferta mais relevante e acessível (Filtros 2 e 3)');
        $this->assertContains(102, $similarIds, 'O Top Cropped deve estar entre os recomendados');
    }

    public function testSocratesRejectsLegacyGeralCategoriesAndZeroSemanticMatches(): void
    {
        // Produto alvo
        $this->insertProduct(300, 'Top Feminino Alcinha Academia', 'Geral', 29.90);

        // Itens no banco com categoria legada 'Geral'
        $this->insertProduct(301, 'Suporte Dobravel Alumínio Celular', 'Geral', 25.00);
        $this->insertProduct(302, 'Ração Pet Gatos 1kg', 'Geral', 19.90);
        $this->insertProduct(303, 'Top Nadador Suplex Feminino', 'Moda, Roupas & Vestuário', 28.00);

        $similar = $this->repo->findSimilarOffers(
            excludeId: 300,
            category: 'Geral',
            title: 'Top Feminino Alcinha Academia',
            limit: 4,
            targetPrice: 29.90
        );

        $similarIds = array_map(fn($p) => (int)$p->id, $similar);

        // Somente o Top Nadador deve passar, jamais Suporte ou Ração mesmo que antigos no banco com 'Geral'
        $this->assertContains(303, $similarIds, 'Top Nadador deve passar pela semântica');
        $this->assertNotContains(301, $similarIds, 'Suporte com categoria Geral NUNCA deve passar');
        $this->assertNotContains(302, $similarIds, 'Ração com categoria Geral NUNCA deve passar');
    }
}
