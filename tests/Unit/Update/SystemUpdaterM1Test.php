<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\SystemApps\SystemUpdater\Controllers\UpdateController;
use DomainSystem\SystemApps\SystemUpdater\Plugin;
use DomainSystem\SystemApps\SystemUpdater\Widgets\UpdateWidgetProvider;
use PHPUnit\Framework\TestCase;

final class SystemUpdaterM1Test extends TestCase
{
    private const VALID_HASH = 'a948904f2f0f479b8f8197694b30184b0d2ed1c1cd2a1ec0fb85d299a192a447';

    private string $tempDir;
    private UpdateTarget $coreTarget;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir()) . '/ds_m1_updater_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->coreTarget = new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: '2.3.0',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testControllerEscapesXssInChangelogAndRejectsInvalidCsrfAndNonAdmin(): void
    {
        $releaseWithXss = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: "Correção importante <script>alert('xss')</script>",
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 1000,
            sha256: self::VALID_HASH,
        );

        $checkResult = new UpdateCheckResult(
            status: UpdateStatus::Available,
            target: $this->coreTarget,
            release: $releaseWithXss,
            reasons: ['Nova versão 2.4.0 disponível.'],
            checkedAt: '2026-10-19T12:00:00Z',
        );

        $checker = new class ($checkResult) implements UpdateCheckerInterface {
            public bool $lastForce = false;
            public function __construct(private readonly UpdateCheckResult $res) {}
            public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
            {
                $this->lastForce = $force;
                return $this->res;
            }
        };

        $resolver = new class ($this->coreTarget) implements UpdateTargetResolverInterface {
            public function __construct(private readonly UpdateTarget $target) {}
            public function core(): UpdateTarget
            {
                return $this->target;
            }
        };

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
                $viewFile = ($pluginViewsDir ?? '') . '/' . $template . '.php';
                extract($args, EXTR_SKIP);
                ob_start();
                include $viewFile;
                return (string)ob_get_clean();
            }
        };

        $adminSession = $this->createFakeSession('admin', 'csrf-secret-123');
        $stateStore = new JsonUpdateStateStore($this->tempDir);

        $controller = new UpdateController(
            theme: $theme,
            session: $adminSession,
            checker: $checker,
            targetResolver: $resolver,
            stateStore: $stateStore,
        );

        // 1. GET /admin/settings/updates renderiza view com changelog escapado contra XSS
        $response = $controller->index(new Request(['checked' => '1']));
        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('v2.3.0', $html);
        $this->assertStringContainsString('v2.4.0', $html);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringContainsString("&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", $html);

        // 2. POST /admin/settings/updates/core/check com CSRF inválido -> 403
        $badCsrfResponse = $controller->check(new Request([], ['csrf_token' => 'token-errado']));
        $this->assertSame(403, $badCsrfResponse->getStatusCode());

        // 3. POST /admin/settings/updates/core/check com CSRF válido -> 302 e $force = true
        $goodCsrfResponse = $controller->check(new Request([], ['csrf_token' => 'csrf-secret-123']));
        $this->assertSame(302, $goodCsrfResponse->getStatusCode());
        $this->assertTrue($checker->lastForce);

        // 4. Papel não-admin -> 403
        $subscriberController = new UpdateController(
            theme: $theme,
            session: $this->createFakeSession('subscriber', 'csrf-secret-123'),
            checker: $checker,
            targetResolver: $resolver,
            stateStore: $stateStore,
        );
        $this->assertSame(403, $subscriberController->index()->getStatusCode());
        $this->assertSame(403, $subscriberController->check(new Request([], ['csrf_token' => 'csrf-secret-123']))->getStatusCode());
    }

    public function testWidgetRendersBadgeWhenUpdateAvailableAndCleanStatusWhenUpToDate(): void
    {
        $resolver = new class ($this->coreTarget) implements UpdateTargetResolverInterface {
            public function __construct(private readonly UpdateTarget $target) {}
            public function core(): UpdateTarget { return $this->target; }
        };

        $release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Novidades',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 500,
            sha256: self::VALID_HASH,
        );

        $availableResult = new UpdateCheckResult(
            UpdateStatus::Available,
            $this->coreTarget,
            $release,
            [],
            '2026-10-19T12:00:00Z',
        );

        $availableWidget = new UpdateWidgetProvider(
            new class ($availableResult) implements UpdateCheckerInterface {
                public function __construct(private readonly UpdateCheckResult $r) {}
                public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult { return $this->r; }
            },
            $resolver,
        );

        $htmlAvailable = $availableWidget->renderWidget(UpdateWidgetProvider::WIDGET_ID);
        $this->assertStringContainsString('Nova versão disponível', $htmlAvailable);
        $this->assertStringContainsString('v2.4.0', $htmlAvailable);

        $upToDateResult = new UpdateCheckResult(
            UpdateStatus::UpToDate,
            $this->coreTarget,
            $release,
            [],
            '2026-10-19T12:00:00Z',
        );

        $upToDateWidget = new UpdateWidgetProvider(
            new class ($upToDateResult) implements UpdateCheckerInterface {
                public function __construct(private readonly UpdateCheckResult $r) {}
                public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult { return $this->r; }
            },
            $resolver,
        );

        $htmlUpToDate = $upToDateWidget->renderWidget(UpdateWidgetProvider::WIDGET_ID);
        $this->assertStringContainsString('Sistema atualizado', $htmlUpToDate);
        $this->assertStringNotContainsString('Nova versão disponível', $htmlUpToDate);
    }

    public function testNotificationPushesOnlyOncePerVersion(): void
    {
        $pushed = [];
        $notifications = new class ($pushed) implements NotificationManagerInterface {
            /** @var list<array{message: string, type: string, source: ?string, context: array<string, mixed>}> */
            public array $items = [];
            public function __construct(array &$ref)
            {
                $this->items = &$ref;
            }
            public function push(string $message, string $type = 'info', ?string $source = null, array $context = []): void
            {
                $this->items[] = ['message' => $message, 'type' => $type, 'source' => $source, 'context' => $context];
            }
            public function getUnread(): array { return $this->items; }
            public function markAsRead(): void {}
            public function getAll(): array { return $this->items; }
            public function clear(): void { $this->items = []; }
        };

        $release240 = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'OK',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 500,
            sha256: self::VALID_HASH,
        );

        $result240 = new UpdateCheckResult(
            UpdateStatus::Available,
            $this->coreTarget,
            $release240,
            [],
            '2026-10-19T12:00:00Z',
        );

        $stateStore = new JsonUpdateStateStore($this->tempDir);
        $resolver = new class ($this->coreTarget) implements UpdateTargetResolverInterface {
            public function __construct(private readonly UpdateTarget $target) {}
            public function core(): UpdateTarget { return $this->target; }
        };

        $widget = new UpdateWidgetProvider(
            new class ($result240) implements UpdateCheckerInterface {
                public function __construct(private readonly UpdateCheckResult $r) {}
                public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult { return $this->r; }
            },
            $resolver,
            $stateStore,
            null,
            $notifications,
        );

        // 1ª renderização detecta v2.4.0 e notifica
        $widget->renderWidget(UpdateWidgetProvider::WIDGET_ID);
        $this->assertCount(1, $notifications->items);
        $this->assertSame('2.4.0', $notifications->items[0]['context']['version']);
        $this->assertSame('2.4.0', $stateStore->load($this->coreTarget)->context['notified_version'] ?? null);

        // 2ª renderização com a mesma v2.4.0 NÃO repete a notificação
        $widget->renderWidget(UpdateWidgetProvider::WIDGET_ID);
        $this->assertCount(1, $notifications->items);
    }

    public function testPluginActivateRegistersSystemUpdateCapability(): void
    {
        $registered = [];
        $runtime = new class ($registered) implements OsRuntimeInterface {
            /** @var list<array{slug: string, context: string}> */
            public array $caps = [];
            public function __construct(array &$ref) { $this->caps = &$ref; }
            public function getLink(string $linkName) { return null; }
            public function contributeTo(string $slotName, mixed $payload): void {}
            public function onHook(string $hookName, callable $callback, int $priority = 0): void {}
            public function dispatchHook(string $hookName, mixed ...$payload): void {}
            public function applyFilter(string $filterName, mixed $value, mixed ...$args): mixed { return $value; }
            public function bind(string $abstract, callable|string $concrete): void {}
            public function singleton(string $abstract, callable|string $concrete): void {}
            public function make(string $class) { return null; }
            public function registerCapability(string $slug, string $context = ''): void
            {
                $this->caps[] = ['slug' => $slug, 'context' => $context];
            }
        };

        $plugin = new Plugin(dirname(__DIR__, 3) . '/src/SystemApps/SystemUpdater');
        $plugin->activate($runtime);

        $this->assertSame([['slug' => 'system.update', 'context' => 'SystemUpdater']], $runtime->caps);
    }

    private function createFakeSession(string $role, string $csrfToken): SessionManagerInterface
    {
        return new class ($role, $csrfToken) implements SessionManagerInterface {
            public function __construct(
                private readonly string $role,
                private readonly string $csrf,
            ) {
            }
            public function start(): void {}
            public function getCsrfToken(): string { return $this->csrf; }
            public function validateCsrfToken(?string $token): bool
            {
                return $token !== null && hash_equals($this->csrf, $token);
            }
            public function get(string $key, $default = null)
            {
                if ($key === 'user_role') {
                    return $this->role;
                }
                return $default;
            }
            public function set(string $key, $value): void {}
            public function remove(string $key): void {}
            public function has(string $key): bool { return $key === 'user_role'; }
            public function destroy(): void {}
            public function setFlash(string $type, string $message): void {}
            public function getFlash(): ?array { return null; }
            public function regenerate(): void {}
        };
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
