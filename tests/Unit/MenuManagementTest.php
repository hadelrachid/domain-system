<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use DomainSystem\SystemApps\nav_menus\Repositories\PdoMenuRepository;
use DomainSystem\SystemApps\nav_menus\Services\MenuRenderer;
use DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController;
use DomainSystem\Core\Contracts\PageLookupInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;

class MenuManagementTest extends TestCase
{
    private PDO $pdo;
    private PdoMenuRepository $menuRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->menuRepo = new PdoMenuRepository($this->pdo);
    }

    public function testEnsureTablesCreatesTablesAndSeedsDefaultHeaderAndFooter(): void
    {
        $this->menuRepo->ensureTables();

        $menus = $this->menuRepo->getMenus();
        $this->assertCount(2, $menus, "Deve ter 2 menus criados no auto-seed (header e footer).");

        $headerMenu = $this->menuRepo->getMenuByLocation('header');
        $this->assertNotNull($headerMenu);
        $this->assertEquals('Menu Principal', $headerMenu['name']);

        $footerMenu = $this->menuRepo->getMenuByLocation('footer');
        $this->assertNotNull($footerMenu);
        $this->assertEquals('Menu do Rodapé', $footerMenu['name']);
    }

    public function testSaveMenuCreatesAndRejectsDuplicateLocation(): void
    {
        $this->menuRepo->ensureTables();

        // Tenta criar outro menu com location 'header'
        $result = $this->menuRepo->saveMenu('Outro Header', 'header');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Já existe um menu atribuído a esta localização', $result['message']);

        // Cria um menu com nova localização
        $resultCustom = $this->menuRepo->saveMenu('Menu Lateral', 'sidebar');
        $this->assertTrue($resultCustom['success']);
        $this->assertArrayHasKey('id', $resultCustom);

        // Atualiza o menu
        $updateResult = $this->menuRepo->saveMenu('Sidebar Principal', 'sidebar', $resultCustom['id']);
        $this->assertTrue($updateResult['success']);

        $saved = $this->menuRepo->getMenuById($resultCustom['id']);
        $this->assertEquals('Sidebar Principal', $saved['name']);
    }

    public function testReplaceMenuItemsIsTransactional(): void
    {
        $this->menuRepo->ensureTables();
        $header = $this->menuRepo->getMenuByLocation('header');
        $this->assertNotNull($header);
        $menuId = (int)$header['id'];

        $initialItems = [
            ['title' => 'Início', 'url' => '/', 'page_id' => null],
            ['title' => 'Blog', 'url' => null, 'page_id' => 10],
            ['title' => 'Sobre', 'url' => null, 'page_id' => 11],
        ];

        $saved = $this->menuRepo->replaceMenuItems($menuId, $initialItems);
        $this->assertTrue($saved);

        $items = $this->menuRepo->getMenuItems($menuId);
        $this->assertCount(3, $items);
        $this->assertEquals('Início', $items[0]['title']);
        $this->assertEquals('Blog', $items[1]['title']);
        $this->assertEquals('Sobre', $items[2]['title']);

        // Substituição atômica com novos itens
        $updatedItems = [
            ['title' => 'Home', 'url' => '/', 'page_id' => null],
            ['title' => 'Contato', 'url' => '/contato', 'page_id' => null],
        ];

        $savedUpdated = $this->menuRepo->replaceMenuItems($menuId, $updatedItems);
        $this->assertTrue($savedUpdated);

        $itemsAfter = $this->menuRepo->getMenuItems($menuId);
        $this->assertCount(2, $itemsAfter);
        $this->assertEquals('Home', $itemsAfter[0]['title']);
        $this->assertEquals('Contato', $itemsAfter[1]['title']);
    }

    public function testMenuRendererRendersHtmlWithDynamicPageSlugs(): void
    {
        $this->menuRepo->ensureTables();
        $header = $this->menuRepo->getMenuByLocation('header');
        $menuId = (int)$header['id'];

        // Mock do PageLookupInterface
        $pageLookup = new class implements PageLookupInterface {
            public function getSelectablePages(): array
            {
                return [
                    ['id' => 1, 'title' => 'Início', 'slug' => 'inicio'],
                    ['id' => 2, 'title' => 'Blog', 'slug' => 'blog'],
                    ['id' => 3, 'title' => 'Sobre', 'slug' => 'sobre'],
                ];
            }
        };

        $this->menuRepo->replaceMenuItems($menuId, [
            ['title' => 'Página Inicial', 'url' => '/', 'page_id' => 1],
            ['title' => 'Nosso Blog', 'url' => null, 'page_id' => 2],
            ['title' => 'Link Externo', 'url' => 'https://externo.com', 'page_id' => null],
        ]);

        $renderer = new MenuRenderer($this->menuRepo, $pageLookup);
        $html = $renderer->render('header');

        $this->assertStringContainsString('Página Inicial', $html);
        $this->assertStringContainsString('/inicio', $html);
        $this->assertStringContainsString('Nosso Blog', $html);
        $this->assertStringContainsString('/blog', $html);
        $this->assertStringContainsString('https://externo.com', $html);
        $this->assertStringContainsString('Link Externo', $html);
    }

    public function testMenuAdminControllerUsesPageLookupWithoutSqlDirectCoupling(): void
    {
        $this->menuRepo->ensureTables();

        $pageLookup = new class implements PageLookupInterface {
            public function getSelectablePages(): array
            {
                return [
                    ['id' => 1, 'title' => 'Início', 'slug' => 'inicio'],
                    ['id' => 2, 'title' => 'Blog', 'slug' => 'blog'],
                    ['id' => 3, 'title' => 'Sobre', 'slug' => 'sobre'],
                    ['id' => 4, 'title' => 'Privacidade', 'slug' => 'privacidade'],
                    ['id' => 5, 'title' => 'Termos e Usos', 'slug' => 'termos'],
                ];
            }
        };

        $themeManager = $this->createMock(ThemeManagerInterface::class);
        $themeManager->method('render')->willReturnCallback(function($view, $data) {
            $this->assertArrayHasKey('pages', $data);
            // 5 páginas dinâmicas + 1 Home sintética = 6 páginas
            $this->assertCount(6, $data['pages']);
            $this->assertEquals('Início (Home)', $data['pages'][0]['title']);
            $this->assertEquals('Início', $data['pages'][1]['title']);
            return '<html>Menus Rendered</html>';
        });

        $controller = new MenuAdminController($this->menuRepo, $themeManager, $pageLookup);
        $request = new Request();
        $response = $controller->index($request);

        $this->assertInstanceOf(\DomainSystem\Core\Http\Responses\ViewResponse::class, $response);
        $this->assertEquals('<html>Menus Rendered</html>', $response->getContent());
    }

    public function testMenuAdminControllerWorksGracefullyWhenPageLookupIsNull(): void
    {
        $this->menuRepo->ensureTables();

        $themeManager = $this->createMock(ThemeManagerInterface::class);
        $themeManager->method('render')->willReturnCallback(function($view, $data) {
            $this->assertArrayHasKey('pages', $data);
            // Apenas a Home sintética
            $this->assertCount(1, $data['pages']);
            $this->assertEquals('Início (Home)', $data['pages'][0]['title']);
            return '<html>OK</html>';
        });

        $controller = new MenuAdminController($this->menuRepo, $themeManager, null);
        $request = new Request();
        $response = $controller->index($request);

        $this->assertInstanceOf(\DomainSystem\Core\Http\Responses\ViewResponse::class, $response);
    }
}
