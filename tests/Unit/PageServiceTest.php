<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Core\Contracts\PageLookupInterface;
use DomainSystem\Plugins\pages\Services\PageSlugHelper;
use DomainSystem\Plugins\pages\Services\DefaultPagesSeeder;
use DomainSystem\Plugins\pages\Repositories\PageRepository;
use DomainSystem\Plugins\pages\Controllers\PageFrontController;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class PageServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("
            CREATE TABLE pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                content TEXT,
                theme TEXT,
                template_file TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function testPortugueseSlugGenerationTransliteratesAccents(): void
    {
        $testCases = [
            'Início' => 'inicio',
            'Sobre Nós' => 'sobre-nos',
            'Termos e Usos' => 'termos-e-usos',
            'Política de Privacidade' => 'politica-de-privacidade',
            'Programação & Design' => 'programacao-design',
        ];

        foreach ($testCases as $title => $expectedSlug) {
            $slug = PageSlugHelper::slugify($title);
            $this->assertEquals($expectedSlug, $slug, "Falha na geração do slug para '{$title}'");
        }
    }

    public function testDefaultThemeSatisfiesContractAndExposesTemplates(): void
    {
        $themesDir = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes';
        $themeManager = new ThemeManager($themesDir . '/default');

        $themes = $themeManager->getAvailableThemes();
        $this->assertArrayHasKey('default', $themes, "O tema 'default' deve ser reconhecido como tema válido pelo contrato.");

        $templates = $themeManager->getAvailableTemplates('default');
        $this->assertContains('home.php', $templates, "O tema 'default' deve possuir o template home.php");
        $this->assertContains('blog.php', $templates, "O tema 'default' deve possuir o template blog.php");
        $this->assertContains('sobre.php', $templates, "O tema 'default' deve possuir o template sobre.php");
        $this->assertContains('termos.php', $templates, "O tema 'default' deve possuir o template termos.php");
        $this->assertContains('privacidade.php', $templates, "O tema 'default' deve possuir o template privacidade.php");
        $this->assertContains('academy.php', $templates, "O tema 'default' deve possuir o template academy.php");
    }

    public function testDefaultPagesSeederCreatesAllRequiredPagesWithTemplates(): void
    {
        $inserted = DefaultPagesSeeder::seed($this->pdo);
        $this->assertEquals(6, $inserted, "Deve semear exatamente 6 páginas padrão.");

        $stmt = $this->pdo->query("SELECT slug, title, theme, template_file FROM pages ORDER BY slug ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pagesBySlug = [];
        foreach ($rows as $r) {
            $pagesBySlug[$r['slug']] = $r;
        }

        $expectedPages = [
            'inicio' => ['title' => 'Início', 'template' => 'templates/home.php'],
            'blog' => ['title' => 'Blog', 'template' => 'templates/blog.php'],
            'sobre' => ['title' => 'Sobre', 'template' => 'templates/sobre.php'],
            'termos' => ['title' => 'Termos e Usos', 'template' => 'templates/termos.php'],
            'privacidade' => ['title' => 'Privacidade', 'template' => 'templates/privacidade.php'],
            'tutoriais' => ['title' => 'Tutoriais', 'template' => 'templates/academy.php'],
        ];

        foreach ($expectedPages as $slug => $expected) {
            $this->assertArrayHasKey($slug, $pagesBySlug, "A página '{$slug}' deve existir no banco.");
            $this->assertEquals($expected['title'], $pagesBySlug[$slug]['title']);
            $this->assertEquals('default', $pagesBySlug[$slug]['theme']);
            $this->assertEquals($expected['template'], $pagesBySlug[$slug]['template_file']);
        }
    }

    public function testDefaultPagesSeederIsIdempotent(): void
    {
        $firstRun = DefaultPagesSeeder::seed($this->pdo);
        $this->assertEquals(6, $firstRun);

        $secondRun = DefaultPagesSeeder::seed($this->pdo);
        $this->assertEquals(0, $secondRun, "Segunda execução do seeder não deve duplicar registros.");

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
        $this->assertEquals(6, $count);
    }

    public function testPageRepositoryImplementsPageLookupInterface(): void
    {
        DefaultPagesSeeder::seed($this->pdo);
        $repo = new PageRepository($this->pdo);

        $this->assertInstanceOf(PageLookupInterface::class, $repo);

        $selectable = $repo->getSelectablePages();
        $this->assertCount(6, $selectable);

        $first = $selectable[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('title', $first);
        $this->assertArrayHasKey('slug', $first);
    }

    public function testPageFrontControllerFallsBackSafelyWhenThemeIsNonexistent(): void
    {
        // Cria uma página apontando para tema inexistente 'rachidd'
        $this->pdo->exec("
            INSERT INTO pages (slug, title, content, theme, template_file)
            VALUES ('sobre-teste', 'Sobre Nós', 'Conteúdo alternativo', 'rachidd', 'templates/sobre.php')
        ");

        $repo = new PageRepository($this->pdo);
        $shortcodes = new ShortcodeManager();
        $seo = $this->createMock(SeoManagerInterface::class);
        $themeManager = new ThemeManager(DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes/default');

        $controller = new PageFrontController($repo, $shortcodes, $seo, $themeManager);

        $response = $controller->show('sobre-teste');

        $this->assertInstanceOf(\DomainSystem\Core\Http\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        // Deve renderizar com sucesso via fallback do SafeThemeResolver para 'default'
        $this->assertStringContainsString('Sobre', $response->getContent());
    }
}
