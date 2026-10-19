<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\MaintenancePolicy;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Monitoring\RequestMetricsRecorder;
use DomainSystem\Core\Plugin\Services\ProcessRegistry;
use DomainSystem\Plugins\academy\Controllers\CourseFrontController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseNavigationService;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;
use DomainSystem\Plugins\settings\Controllers\SettingsController;
use DomainSystem\SystemApps\Database\Connection;
use DomainSystem\SystemApps\installer\Contracts\InstallerServiceInterface;
use DomainSystem\SystemApps\installer\Controllers\SetupController;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use DomainSystem\SystemApps\nav_menus\Plugin as NavMenusPlugin;
use DomainSystem\SystemApps\SystemMonitor\Controllers\MonitorController;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class TrackAStabilizationAuditTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_track_a_' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupDir($this->tempDir);
        parent::tearDown();
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->cleanupDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * N-6: Previne bypass por query string (/?x=/login) ou falso prefixo (/blog/admin-dicas)
     * e integra SiteStateInterface::isMaintenance().
     */
    public function testMaintenancePolicyBlocksQueryStringAndSubstringBypassesAndHonorsSiteState(): void
    {
        // 1. Bypass clássico via query string (/?x=/login ou /cursos?next=/admin)
        $normQueryBypass = MaintenancePolicy::normalizePath('/?x=/login', '/domain-system', '/domain-system/public');
        $this->assertSame('/', $normQueryBypass);
        $this->assertFalse(MaintenancePolicy::isExemptPath($normQueryBypass));
        $this->assertTrue(MaintenancePolicy::shouldBlock($normQueryBypass, true));

        // 2. Bypass via substring no meio da URL (/blog/admin-dicas ou /login-fake)
        $normSubstring = MaintenancePolicy::normalizePath('/blog/admin-dicas');
        $this->assertSame('/blog/admin-dicas', $normSubstring);
        $this->assertFalse(MaintenancePolicy::isExemptPath($normSubstring));
        $this->assertTrue(MaintenancePolicy::shouldBlock($normSubstring, true));

        $normFakePrefix = MaintenancePolicy::normalizePath('/login-fake');
        $this->assertFalse(MaintenancePolicy::isExemptPath($normFakePrefix));
        $this->assertTrue(MaintenancePolicy::shouldBlock($normFakePrefix, true));

        // 3. Rotas legítimas com ou sem subpasta do XAMPP são liberadas
        $normAdminXampp = MaintenancePolicy::normalizePath('/domain-system/admin/monitor?tab=1', '/domain-system');
        $this->assertSame('/admin/monitor', $normAdminXampp);
        $this->assertTrue(MaintenancePolicy::isExemptPath($normAdminXampp));
        $this->assertFalse(MaintenancePolicy::shouldBlock($normAdminXampp, true));

        $this->assertTrue(MaintenancePolicy::isExemptPath('/login'));
        $this->assertTrue(MaintenancePolicy::isExemptPath('/logout'));
        $this->assertTrue(MaintenancePolicy::isExemptPath('/api/os/monitor/stack'));
        $this->assertTrue(MaintenancePolicy::isExemptPath('/setup/database'));

        // 4. SiteStateInterface com status = maintenance bloqueia visitantes mas libera role admin
        $siteStateMaint = $this->createMock(SiteStateInterface::class);
        $siteStateMaint->method('isMaintenance')->willReturn(true);

        $this->assertTrue(MaintenancePolicy::shouldBlock('/cursos', false, $siteStateMaint, 'guest'));
        $this->assertFalse(MaintenancePolicy::shouldBlock('/cursos', false, $siteStateMaint, 'admin'));
    }

    /**
     * H-03: Reset de fábrica exige reautenticação com senha do admin, usa SessionManagerInterface::destroy()
     * e apaga apenas tabelas gerenciadas (preservando tabelas de terceiros no mesmo banco).
     */
    public function testFactoryResetRequiresAdminPasswordAndOnlyDropsManagedTables(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabelas do sistema + uma tabela externa de outra aplicação no mesmo schema
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT, role TEXT)");
        $pdo->exec("CREATE TABLE settings (key_name TEXT PRIMARY KEY, key_value TEXT)");
        $pdo->exec("CREATE TABLE external_erp_clients (id INTEGER PRIMARY KEY AUTOINCREMENT, company TEXT)");

        $hash = password_hash('AdminSecret#2026', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Rachid Admin', 'admin@example.com', $hash]);
        $adminId = (int)$pdo->lastInsertId();

        $pdo->exec("INSERT INTO external_erp_clients (company) VALUES ('Acme Corp')");

        $connMock = $this->createMock(Connection::class);
        $connMock->method('getPdo')->willReturn($pdo);

        $themeMock = $this->createMock(ThemeManagerInterface::class);
        $settingRepoMock = $this->createMock(SettingRepositoryInterface::class);

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('get')->willReturnCallback(function (string $key, $default = null) use ($adminId) {
            if ($key === 'user_id') {
                return $adminId;
            }
            if ($key === 'user_role') {
                return 'admin';
            }
            return $default;
        });

        // O método destroy() da sessão só deve ser chamado uma única vez (no sucesso)
        $sessionMock->expects($this->once())->method('destroy');

        $controller = new SettingsController($themeMock, $settingRepoMock, $connMock, $sessionMock, $this->tempDir);

        // 1. Sem senha -> 403
        $resNoPass = $controller->executeFactoryReset(new Request([], []));
        $this->assertSame(403, $resNoPass->getStatusCode());
        $dataNoPass = json_decode($resNoPass->getContent(), true);
        $this->assertFalse($dataNoPass['success']);

        // 2. Com senha incorreta -> 403 e tabelas intactas
        $resWrongPass = $controller->executeFactoryReset(new Request([], ['admin_password' => 'SenhaErrada123']));
        $this->assertSame(403, $resWrongPass->getStatusCode());
        $tablesBefore = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('users', $tablesBefore);
        $this->assertContains('settings', $tablesBefore);
        $this->assertContains('external_erp_clients', $tablesBefore);

        // 3. Com senha correta -> 200, tabelas do sistema removidas, tabela externa preservada!
        $resOk = $controller->executeFactoryReset(new Request([], ['admin_password' => 'AdminSecret#2026']));
        $this->assertSame(200, $resOk->getStatusCode());
        $dataOk = json_decode($resOk->getContent(), true);
        $this->assertTrue($dataOk['success']);

        $tablesAfter = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotContains('users', $tablesAfter);
        $this->assertNotContains('settings', $tablesAfter);
        $this->assertContains('external_erp_clients', $tablesAfter, 'Tabelas não gerenciadas pelo Domain System jamais devem ser apagadas');
    }

    /**
     * N-3: SetupController deve exigir InstallerServiceInterface sem fallback concreto no construtor.
     */
    public function testSetupControllerRequiresInstallerServiceInterface(): void
    {
        $ref = new ReflectionClass(SetupController::class);
        $ctor = $ref->getConstructor();
        $this->assertNotNull($ctor);

        $params = $ctor->getParameters();
        $this->assertCount(3, $params);

        $serviceParam = $params[2];
        $this->assertFalse($serviceParam->isOptional(), 'InstallerServiceInterface não deve ter fallback opcional');
        $this->assertSame(InstallerServiceInterface::class, $serviceParam->getType()?->getName());
    }

    /**
     * N-4: nav_menus\Plugin::activate() resolve MenuRepositoryInterface via container e não engole exceções.
     */
    public function testNavMenusActivateUsesContainerAndPropagatesErrors(): void
    {
        $plugin = new NavMenusPlugin(dirname(__DIR__, 2) . '/src/SystemApps/nav_menus');

        $repoMock = $this->createMock(MenuRepositoryInterface::class);
        $repoMock->expects($this->once())->method('ensureTables')->willThrowException(new RuntimeException('Falha DDL simulada'));

        $runtimeMock = $this->createMock(OsRuntimeInterface::class);
        $runtimeMock->expects($this->once())->method('bind')->with(MenuRepositoryInterface::class, \DomainSystem\SystemApps\nav_menus\Repositories\PdoMenuRepository::class);
        $runtimeMock->expects($this->once())->method('make')->with(MenuRepositoryInterface::class)->willReturn($repoMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falha DDL simulada');
        $plugin->activate($runtimeMock);
    }

    /**
     * M-01: renderThemeFile exige separador de diretório após realpath($themePath) para impedir
     * leitura de pasta irmã com mesmo prefixo (ex: theme_active vs theme_active-evil).
     */
    public function testCourseFrontControllerRejectsSiblingThemeDirectoryPrefix(): void
    {
        $activeThemeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'theme_active';
        $evilSiblingDir = $this->tempDir . DIRECTORY_SEPARATOR . 'theme_active-evil';
        mkdir($activeThemeDir, 0755, true);
        mkdir($evilSiblingDir, 0755, true);

        file_put_contents($evilSiblingDir . DIRECTORY_SEPARATOR . 'pwn.php', '<?php echo "PWNED_SIBLING";');

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $courseRepo = new PdoCourseRepository($pdo);
        $structureRepo = new PdoCourseStructureRepository($pdo);
        $navService = new CourseNavigationService($courseRepo, $structureRepo, '');
        $catalogRenderer = new CourseCatalogRenderer($courseRepo);

        $themeMock = $this->createMock(ThemeManagerInterface::class);
        $themeMock->method('getActiveThemePath')->willReturn($activeThemeDir);

        $controller = new CourseFrontController($courseRepo, $structureRepo, $navService, $catalogRenderer, null, $themeMock);

        $courseId = $courseRepo->create([
            'title'  => 'Curso Teste M01',
            'slug'   => 'curso-m01',
            'status' => 'published',
        ]);
        $modId = $structureRepo->createModule($courseId, 'Módulo 1', 1);
        $structureRepo->createLesson([
            'course_id'   => $courseId,
            'module_id'   => $modId,
            'title'       => 'Aula Traverse',
            'slug'        => 'aula-traverse',
            'source_type' => 'theme_file',
            'source'      => '../theme_active-evil/pwn.php',
            'status'      => 'published',
        ]);

        $response = $controller->showLesson('curso-m01', 'aula-traverse');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('PWNED_SIBLING', $response->getContent());
        $this->assertStringContainsString('Arquivo do tema não encontrado ou inacessível', $response->getContent());
    }

    /**
     * H-04 & M-10: syncCourseStructure preserva IDs existentes de módulos e aulas,
     * deduplica slugs no mesmo curso e rejeita status/source_type fora da whitelist.
     */
    public function testCourseStructureSyncPreservesIdsAndValidatesLessonInputs(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $structureRepo = new PdoCourseStructureRepository($pdo);

        $courseId = 42;
        $structureRepo->replaceCourseStructure($courseId, [
            [
                'title'   => 'Módulo Base',
                'lessons' => [
                    [
                        'title'       => 'Aula 01 Intro',
                        'slug'        => 'aula-01-intro',
                        'source_type' => 'content',
                        'content'     => '<p>Texto original da aula 1</p>',
                        'status'      => 'published',
                    ],
                    [
                        'title'       => 'Aula 02 Config',
                        'slug'        => 'aula-02-config',
                        'source_type' => 'content',
                        'content'     => '<p>Texto original da aula 2</p>',
                        'status'      => 'draft',
                    ],
                ],
            ],
        ]);

        $initialModules = $structureRepo->getModules($courseId);
        $initialLessons = $structureRepo->getLessons($courseId);
        $this->assertCount(1, $initialModules);
        $this->assertCount(2, $initialLessons);

        $mod1Id = (int)$initialModules[0]['id'];
        $lesson1Id = (int)$initialLessons[0]['id'];
        $lesson2Id = (int)$initialLessons[1]['id'];

        // 1. Atualiza estrutura preservando IDs (mesmo passando IDs explícitos + adicionando uma 3ª aula com slug repetido)
        $structureRepo->replaceCourseStructure($courseId, [
            [
                'id'      => $mod1Id,
                'title'   => 'Módulo Base Renomeado',
                'lessons' => [
                    [
                        'id'          => $lesson1Id,
                        'title'       => 'Aula 01 Intro Atualizada',
                        'slug'        => 'aula-01-intro',
                        'source_type' => 'content',
                        'content'     => '<p>Texto atualizado</p>',
                        'status'      => 'published',
                    ],
                    [
                        'id'          => $lesson2Id,
                        'title'       => 'Aula 02 Config Publicada',
                        'slug'        => 'aula-02-config',
                        'source_type' => 'content',
                        'status'      => 'published',
                    ],
                    [
                        'title'       => 'Nova Aula com Slug Duplicado',
                        'slug'        => 'aula-01-intro',
                        'source_type' => 'content',
                        'content'     => '<p>Aula 3</p>',
                        'status'      => 'published',
                    ],
                ],
            ],
        ]);

        $updatedModules = $structureRepo->getModules($courseId);
        $updatedLessons = $structureRepo->getLessons($courseId);

        $this->assertCount(1, $updatedModules);
        $this->assertSame($mod1Id, (int)$updatedModules[0]['id'], 'O ID do módulo deve ser preservado');
        $this->assertSame('Módulo Base Renomeado', $updatedModules[0]['title']);

        $this->assertCount(3, $updatedLessons);
        $this->assertSame($lesson1Id, (int)$updatedLessons[0]['id'], 'O ID da aula 1 deve ser preservado');
        $this->assertSame($lesson2Id, (int)$updatedLessons[1]['id'], 'O ID da aula 2 deve ser preservado');
        // Conteúdo da aula 2 foi preservado mesmo não sendo reenviado no payload
        $this->assertSame('<p>Texto original da aula 2</p>', $updatedLessons[1]['content']);
        // Slug duplicado foi desambiguado automaticamente
        $this->assertSame('aula-01-intro-1', $updatedLessons[2]['slug']);

        // 2. Status inválido deve lançar InvalidArgumentException e fazer rollback
        try {
            $structureRepo->replaceCourseStructure($courseId, [
                [
                    'id'      => $mod1Id,
                    'title'   => 'Módulo Corrompido',
                    'lessons' => [
                        [
                            'title'       => 'Aula Inválida',
                            'slug'        => 'aula-invalida',
                            'source_type' => 'content',
                            'status'      => 'hacked_status',
                        ],
                    ],
                ],
            ]);
            $this->fail('Deveria rejeitar status de aula inválido');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Status de aula inválido', $e->getMessage());
        }

        // Verifica que o rollback manteve as 3 aulas intactas
        $this->assertCount(3, $structureRepo->getLessons($courseId));
    }

    /**
     * H-05 & M-05: MonitorController separa Limpar Logs de Reiniciar Ciclo do Kernel
     * e agrega picos reais de memória via RequestMetricsRecorder.
     */
    public function testMonitorControllerSeparatesClearLogsFromRestartKernelAndAggregatesMetrics(): void
    {
        $tempRoot = $this->tempDir . '/monitor_root';
        mkdir($tempRoot . '/temp', 0755, true);

        ProcessRegistry::killSingleton($tempRoot);
        $registry = new ProcessRegistry($tempRoot);
        $registry->startProcess('database', true);
        $initialSingletonId = $registry->getSingletonId();
        $this->assertStringStartsWith('KRN-', $initialSingletonId);

        file_put_contents($tempRoot . '/temp/error_logs.json', json_encode([['message' => 'Erro teste']]));

        $recorder = new RequestMetricsRecorder($tempRoot);
        $recorder->record('/cursos/arquitetura', 'GET', 54.25, 32.0, 120.5);

        $themeMock = $this->createMock(ThemeManagerInterface::class);
        $notifMock = $this->createMock(NotificationManagerInterface::class);

        $controller = new MonitorController($themeMock, $notifMock, $recorder, $tempRoot);

        // 1. getStackApi inclui o pico recente real (54.25 MB)
        $stackRes = $controller->getStackApi();
        $payload = json_decode($stackRes->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame(54.25, $payload['system_memory']['recent_peak_mb']);
        $this->assertGreaterThanOrEqual(54.25, $payload['system_memory']['peak_mb']);

        // 2. clear() apaga logs e métricas, MAS preserva o ciclo do Kernel Singleton!
        $clearRes = $controller->clear();
        $this->assertSame(302, $clearRes->getStatusCode());
        $this->assertFileDoesNotExist($tempRoot . '/temp/error_logs.json');
        $this->assertSame(0.0, $recorder->getMaxRecentPeakMb());
        $this->assertSame($initialSingletonId, ProcessRegistry::getActiveSingletonId(), 'Limpar logs não deve matar o Kernel Singleton');

        // 3. restartKernel() encerra o ciclo do Kernel Singleton e gera um novo ID no próximo boot
        $restartRes = $controller->restartKernel();
        $this->assertSame(302, $restartRes->getStatusCode());
        $this->assertNull(ProcessRegistry::getActiveSingletonId());

        $newRegistry = new ProcessRegistry($tempRoot);
        $newRegistry->startProcess('database', true);
        $this->assertNotSame($initialSingletonId, $newRegistry->getSingletonId());
    }
}
