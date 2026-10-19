<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\RouterInterface;
use DomainSystem\Core\Contracts\SchemaDoctorInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Infrastructure\CoreHealthCheck;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\SystemApps\SystemUpdater\Controllers\UpdateController;
use PDO;
use PHPUnit\Framework\TestCase;

final class CoreHealthCheckTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-health-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir . '/config', 0755, true);
        file_put_contents($this->tempDir . '/config/plugins.json', '{"SystemUpdater":true}');
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    private function makeContainer(bool $includeAllContracts = true): ContainerInterface
    {
        return new class ($includeAllContracts) implements ContainerInterface {
            public function __construct(private bool $all)
            {
            }

            public function bind(string $abstract, callable|string $concrete): void
            {
            }

            public function singleton(string $abstract, callable|string $concrete): void
            {
            }

            public function has(string $abstract): bool
            {
                if (!$this->all && $abstract === SchemaDoctorInterface::class) {
                    return false;
                }
                return in_array($abstract, [
                    RouterInterface::class,
                    SessionManagerInterface::class,
                    DatabaseConnectionInterface::class,
                    SchemaDoctorInterface::class,
                ], true);
            }

            public function make(string $abstract): mixed
            {
                if ($abstract === DatabaseConnectionInterface::class) {
                    return new class implements DatabaseConnectionInterface {
                        public function getPdo(): PDO
                        {
                            return new PDO('sqlite::memory:');
                        }
                    };
                }
                return new \stdClass();
            }
        };
    }

    private function makeInstalledState(string $version): InstalledStateProviderInterface
    {
        return new class ($version) implements InstalledStateProviderInterface {
            public function __construct(private string $ver)
            {
            }

            public function coreVersion(): string
            {
                return $this->ver;
            }

            public function plugins(): array
            {
                return [];
            }
        };
    }

    public function testCoreHealthCheckOkMissingContractAndVersionMismatch(): void
    {
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.4.0');

        // 1. Tudo OK
        $okCheck = new CoreHealthCheck(
            $this->tempDir,
            $this->makeContainer(true),
            $this->makeInstalledState('2.4.0'),
        );
        $reportOk = $okCheck->run($target);
        $this->assertTrue($reportOk->ok);

        // 2. Contrato ausente
        $missingContractCheck = new CoreHealthCheck(
            $this->tempDir,
            $this->makeContainer(false),
            $this->makeInstalledState('2.4.0'),
        );
        $reportMissing = $missingContractCheck->run($target);
        $this->assertFalse($reportMissing->ok);

        // 3. Versão divergente
        $versionMismatchCheck = new CoreHealthCheck(
            $this->tempDir,
            $this->makeContainer(true),
            $this->makeInstalledState('2.3.0'),
        );
        $reportMismatch = $versionMismatchCheck->run($target);
        $this->assertFalse($reportMismatch->ok);
    }

    public function testHealthEndpointPersistsHealthReportInUpdateState(): void
    {
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.3.0');
        $stateStore = new JsonUpdateStateStore($this->tempDir . '/state.json');

        $release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Notes',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/pkg.zip',
            assetName: 'pkg.zip',
            sizeBytes: 100,
            sha256: str_repeat('a', 64),
        );

        $initialState = new UpdateState(
            updateId: 'upd-123',
            target: $target,
            step: UpdateStep::SchemaVerified,
            updatedAt: '2026-10-19T12:00:00Z',
            release: $release,
            context: ['awaiting' => 'health'],
        );
        $stateStore->save($initialState);

        $healthCheck = new CoreHealthCheck(
            $this->tempDir,
            $this->makeContainer(true),
            $this->makeInstalledState('2.4.0'),
            $stateStore,
        );

        $theme = new class implements ThemeManagerInterface {
            public function setActiveThemePath(string $path): void {}
            public function getActiveThemePath(): string { return ''; }
            public function get_header(array $args = []): string { return ''; }
            public function get_footer(array $args = []): string { return ''; }
            public function getAvailableThemes(): array { return []; }
            public function getAvailableTemplates(?string $themeName = null): array { return []; }
            public function getAvailablePages(?string $themeName = null): array { return []; }
            public function render(string $template, array $args = [], ?string $pluginViewsDir = null): string
            {
                return '';
            }
        };

        $session = new class implements SessionManagerInterface {
            public function start(): void {}
            public function getCsrfToken(): string { return 'tok'; }
            public function validateCsrfToken(?string $token): bool { return true; }
            public function get(string $key, $default = null) { return $key === 'user_role' ? 'admin' : $default; }
            public function set(string $key, $value): void {}
            public function remove(string $key): void {}
            public function has(string $key): bool { return true; }
            public function destroy(): void {}
            public function setFlash(string $type, string $message): void {}
            public function getFlash(): ?array { return null; }
            public function regenerate(): void {}
        };

        $checker = new class ($target) implements UpdateCheckerInterface {
            public function __construct(private UpdateTarget $t) {}
            public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
            {
                return new UpdateCheckResult(UpdateStatus::UpToDate, $this->t, null, [], '2026-10-19T12:00:00Z');
            }
        };

        $resolver = new class ($target) implements UpdateTargetResolverInterface {
            public function __construct(private UpdateTarget $t) {}
            public function core(): UpdateTarget { return $this->t; }
            public function plugins(): array { return []; }
        };

        $controller = new UpdateController(
            theme: $theme,
            session: $session,
            checker: $checker,
            targetResolver: $resolver,
            stateStore: $stateStore,
            healthCheck: $healthCheck,
        );

        $request = new Request(['update_id' => 'upd-123'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/settings/updates/core/health']);
        $response = $controller->health($request);

        $this->assertSame(200, $response->getStatusCode());
        $savedState = $stateStore->load($target);
        $this->assertArrayHasKey('health_report', $savedState->context);
        $this->assertTrue($savedState->context['health_report']['ok']);
        $this->assertArrayNotHasKey('awaiting', $savedState->context);
    }
}
