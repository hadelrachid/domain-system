<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\ContainerWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\WidgetManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Widgets\TextWidget;
use DomainSystem\Plugins\pages\Repositories\PageRepository;
use DomainSystem\SystemApps\auth\Repositories\UserRepository;
use DomainSystem\SystemApps\Database\Connection;
use PDO;

class TrackBArchitectureAuditTest extends TestCase
{
    /**
     * DeepSeek PR #4: SessionManager consolidado em DomainSystem\Core\Security\SessionManager
     */
    public function testSessionManagerIsConsolidatedUnderCoreSecurityNamespace(): void
    {
        $this->assertTrue(
            class_exists(\DomainSystem\Core\Security\SessionManager::class),
            'SessionManager deve existir em DomainSystem\Core\Security\SessionManager'
        );

        $securitySession = new \DomainSystem\Core\Security\SessionManager();
        $this->assertInstanceOf(SessionManagerInterface::class, $securitySession);

        // Retrocompatibilidade com Core\Http\SessionManager
        $httpSession = new \DomainSystem\Core\Http\SessionManager();
        $this->assertInstanceOf(\DomainSystem\Core\Security\SessionManager::class, $httpSession);
    }

    /**
     * DeepSeek PR #1: Widgets retornam DTO (array estruturado) e DtoRenderer produz HTML
     */
    public function testWidgetReturnsDtoAndDtoRendererProducesHtml(): void
    {
        $widget = new ContainerWidget();
        $dto = $widget->toDto(['width' => '100%', 'direction' => 'row', 'align' => 'center']);

        $this->assertIsArray($dto);
        $this->assertArrayHasKey('styles', $dto);
        $this->assertSame('flex', $dto['styles']['display']);
        $this->assertSame('row', $dto['styles']['flex-direction']);
        $this->assertSame('center', $dto['styles']['align-items']);
        $this->assertArrayNotHasKey('html', $dto);

        $renderer = new \DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer();
        $html = $renderer->render([
            'tag' => 'div',
            'attrs' => ['id' => 'test-box', 'class' => ['flex-widget', 'custom-box']],
            'styles' => ['display' => 'flex', 'width' => '100%'],
            'content' => 'Conteúdo Seguro',
            'children' => [],
        ]);

        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('id="test-box"', $html);
        $this->assertStringContainsString('display: flex', $html);
        $this->assertStringContainsString('Conteúdo Seguro', $html);

        $wm = new WidgetManager();
        $wm->registerWidget(new ContainerWidget());
        $wm->registerWidget(new TextWidget());

        $jsonRenderer = new \DomainSystem\Plugins\flextheme\BuilderFlex\Core\JsonRenderer($wm, $renderer);
        $treeHtml = $jsonRenderer->renderTree([
            [
                'id' => 'node1',
                'type' => 'flex_container',
                'props' => ['direction' => 'column', 'width' => 600],
                'children' => [
                    [
                        'id' => 'node2',
                        'type' => 'flex_text',
                        'props' => ['tag' => 'h2', 'content' => 'Título via DTO'],
                        'children' => [],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('<h2', $treeHtml);
        $this->assertStringContainsString('Título via DTO', $treeHtml);
    }

    /**
     * DeepSeek PR #2: Exemplo legal_cases refatorado para OsExtensionInterface + Repositório por Contrato
     */
    public function testLegalCasesExamplePluginImplementsOsExtensionAndRepositoryContract(): void
    {
        require_once DOMAIN_SYSTEM_ROOT . '/repo_examples/legal_cases/Contracts/LegalCaseRepositoryInterface.php';
        require_once DOMAIN_SYSTEM_ROOT . '/repo_examples/legal_cases/Repositories/SqliteLegalCaseRepository.php';
        require_once DOMAIN_SYSTEM_ROOT . '/repo_examples/legal_cases/Controllers/LegalController.php';
        require_once DOMAIN_SYSTEM_ROOT . '/repo_examples/legal_cases/Plugin.php';

        $plugin = new \DomainSystem\Plugins\legal_cases\Plugin(DOMAIN_SYSTEM_ROOT . '/repo_examples/legal_cases');
        $this->assertInstanceOf(\DomainSystem\Core\Contracts\OsExtensionInterface::class, $plugin);

        $conn = new Connection('sqlite::memory:');
        $conn->getPdo()->exec("CREATE TABLE legal_cases (id INTEGER PRIMARY KEY AUTOINCREMENT, client_name TEXT, case_number TEXT, status TEXT)");

        $repo = new \DomainSystem\Plugins\legal_cases\Repositories\SqliteLegalCaseRepository($conn);
        $this->assertInstanceOf(\DomainSystem\Plugins\legal_cases\Contracts\LegalCaseRepositoryInterface::class, $repo);

        $newId = $repo->create([
            'client_name' => 'Bruce Wayne',
            'case_number' => '0001-GOTHAM',
            'status' => 'Ativo',
        ]);
        $this->assertGreaterThan(0, $newId);

        $found = $repo->findById($newId);
        $this->assertNotNull($found);
        $this->assertSame('Bruce Wayne', $found['client_name']);
    }

    /**
     * DeepSeek PR #3: MigrationRunner executa migrações versionadas de forma idempotente
     */
    public function testMigrationRunnerExecutesAndTracksVersionedMigrationsIdempotently(): void
    {
        $conn = new Connection('sqlite::memory:');
        $runner = new \DomainSystem\Core\Database\MigrationRunner($conn);

        $tempDir = sys_get_temp_dir() . '/ds_migrations_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        $migrationFile = $tempDir . '/2026_10_16_000001_create_sample_items.php';
        file_put_contents($migrationFile, <<<'PHP'
<?php
return [
    'up' => function(\PDO $db) {
        $db->exec("CREATE TABLE sample_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(100) NOT NULL)");
    },
    'down' => function(\PDO $db) {
        $db->exec("DROP TABLE IF EXISTS sample_items");
    }
];
PHP);

        try {
            // 1ª execução: deve rodar a migration
            $firstRun = $runner->runForPlugin('sample_plugin', $tempDir);
            $this->assertSame(['2026_10_16_000001_create_sample_items'], $firstRun['executed']);
            $this->assertEmpty($firstRun['errors']);

            // 2ª execução: idempotente (não deve rodar novamente)
            $secondRun = $runner->runForPlugin('sample_plugin', $tempDir);
            $this->assertEmpty($secondRun['executed']);
            $this->assertEmpty($secondRun['errors']);

            $executedList = $runner->getExecutedMigrations('sample_plugin');
            $this->assertCount(1, $executedList);
            $this->assertSame('2026_10_16_000001_create_sample_items', $executedList[0]['migration']);
        } finally {
            @unlink($migrationFile);
            @rmdir($tempDir);
        }
    }

    /**
     * M-04: Router intercepta HttpResponseException em router.before_dispatch sem precisar de header()/exit
     */
    public function testRouterCatchesHttpResponseExceptionFromBeforeDispatchHook(): void
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $router = new Router($container, $dispatcher);

        $dispatcher->addListener('router.before_dispatch', function (string $uri) {
            if (str_starts_with($uri, '/admin')) {
                throw new \DomainSystem\Core\Exceptions\HttpResponseException(
                    Response::redirect('/login')
                );
            }
        });

        $router->addRoute('GET', '/admin/dashboard', fn() => new Response('secreto'));

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/dashboard']);
        $response = $router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeader('Location'));
    }

    /**
     * N-5: HttpKernel processa POST /logout com proteção CSRF ponta a ponta
     */
    public function testHttpKernelDispatchesPostLogoutWithCsrfProtectionEndToEnd(): void
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();

        $session = new class implements SessionManagerInterface {
            private array $store = ['user_id' => 7, 'csrf_token' => 'valid-csrf-token-123'];
            public bool $destroyed = false;
            public function start(): void {}
            public function getCsrfToken(): string { return $this->store['csrf_token'] ?? ''; }
            public function validateCsrfToken(?string $token): bool { return hash_equals($this->getCsrfToken(), (string)$token); }
            public function get(string $key, $default = null) { return $this->store[$key] ?? $default; }
            public function set(string $key, $value): void { $this->store[$key] = $value; }
            public function remove(string $key): void { unset($this->store[$key]); }
            public function has(string $key): bool { return isset($this->store[$key]); }
            public function destroy(): void { $this->store = []; $this->destroyed = true; }
            public function setFlash(string $type, string $message): void {}
            public function getFlash(): ?array { return null; }
            public function regenerate(): void {}
        };

        $container->singleton(SessionManagerInterface::class, fn() => $session);
        $container->singleton(\DomainSystem\Core\Security\SessionManager::class, fn() => new \DomainSystem\Core\Security\SessionManager());

        $router = new Router($container, $dispatcher);
        $router->addGlobalMiddleware(\DomainSystem\Core\Routing\Middlewares\CsrfMiddleware::class);
        $router->addRoute('POST', '/logout', function () use ($session) {
            $session->destroy();
            return Response::redirect('/login');
        });

        $httpKernel = new \DomainSystem\Core\Http\HttpKernel($router, $container);

        // 1. Sem CSRF -> 403
        $badReq = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/logout']);
        $badRes = $httpKernel->handle($badReq);
        $this->assertSame(403, $badRes->getStatusCode());
        $this->assertFalse($session->destroyed);

        // 2. Com CSRF válido -> 302 e sessão destruída
        $goodReq = new Request([], ['csrf_token' => 'valid-csrf-token-123'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/logout']);
        $goodRes = $httpKernel->handle($goodReq);
        $this->assertSame(302, $goodRes->getStatusCode());
        $this->assertTrue($session->destroyed);
    }

    /**
     * H-02: Ring 0 (src/Core) não referencia diretamente classes concretas de DomainSystem\SystemApps\*
     */
    public function testCoreRingZeroDoesNotDependDirectlyOnSystemAppsClasses(): void
    {
        $coreDir = DOMAIN_SYSTEM_ROOT . '/src/Core';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($coreDir, \FilesystemIterator::SKIP_DOTS));
        $violations = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            // Remove comentários para checar apenas código executável
            $stripped = preg_replace('!/\*.*?\*/!s', '', $content);
            $stripped = preg_replace('!//.*$!m', '', $stripped);

            if (preg_match('/DomainSystem\\\\SystemApps\\\\/', $stripped)) {
                $violations[] = str_replace('\\', '/', substr($file->getPathname(), strlen(DOMAIN_SYSTEM_ROOT) + 1));
            }
        }

        $this->assertEmpty(
            $violations,
            "Arquivos de src/Core (Ring 0) não devem acoplar diretamente a DomainSystem\\SystemApps\\*:\n" . implode("\n", $violations)
        );
    }

    /**
     * M-08: PageFrontController usa PageTreeRendererInterface injetada via DI (sem Service Locator)
     */
    public function testPageFrontControllerUsesInjectedPageTreeRendererInterface(): void
    {
        $pageRepo = $this->createMock(\DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface::class);
        $pageRepo->method('findBySlug')->with('home-json')->willReturn([
            'id' => 1,
            'title' => 'Home JSON',
            'slug' => 'home-json',
            'content' => json_encode([['id' => 'n1', 'type' => 'flex_text', 'props' => ['content' => 'Olá DI']]]),
            'theme' => null,
            'template_file' => null,
        ]);

        $shortcodes = new \DomainSystem\Core\Theme\ShortcodeManager(new Container());
        $seo = $this->createMock(\DomainSystem\Plugins\seo\Contracts\SeoManagerInterface::class);
        $theme = $this->createMock(\DomainSystem\Core\Contracts\ThemeManagerInterface::class);

        $treeRenderer = new class implements \DomainSystem\Core\Contracts\PageTreeRendererInterface {
            public function renderTree(array $tree): string
            {
                return '<section class="rendered-via-contract">Olá DI</section>';
            }
        };

        $controller = new \DomainSystem\Plugins\pages\Controllers\PageFrontController(
            $pageRepo,
            $shortcodes,
            $seo,
            $theme,
            $treeRenderer
        );

        $response = $controller->show('home-json');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('rendered-via-contract', $response->getContent());
    }

    /**
     * M-09: PageRepository e UserRepository rejeitam colunas fora da whitelist contra SQL Injection em chaves
     */
    public function testPageAndUserRepositoriesRejectNonWhitelistedColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT, title TEXT, content TEXT, theme TEXT, template_file TEXT, created_at TEXT)");

        $pageRepo = new PageRepository($pdo);

        $this->expectException(\InvalidArgumentException::class);
        $pageRepo->create([
            'title' => 'Página Teste',
            'slug' => 'pagina-teste',
            'malicious_col); DROP TABLE pages; --' => 'boom',
        ]);
    }

    public function testUserRepositoryRejectsNonWhitelistedColumnsOnUpdateProfile(): void
    {
        $conn = new Connection('sqlite::memory:');
        $conn->getPdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT, role TEXT)");

        $userRepo = new UserRepository($conn);

        $this->expectException(\InvalidArgumentException::class);
        $userRepo->updateProfile(1, [
            'role = "admin", name' => 'Hacker',
        ]);
    }

    /**
     * Regressão Container::singleton($class, $class): garante que registrar uma classe concreta
     * como singleton de si mesma instancia via autowire sem entrar em recursão infinita.
     */
    public function testContainerSelfBoundSingletonDoesNotRecurseInfinitely(): void
    {
        $container = new Container();
        $container->singleton(
            \DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer::class,
            \DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer::class
        );

        $first = $container->make(\DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer::class);
        $second = $container->make(\DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer::class);

        $this->assertInstanceOf(\DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer::class, $first);
        $this->assertSame($first, $second);
    }
}
