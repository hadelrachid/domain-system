<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Update\Infrastructure\UpdateServiceBinder;
use DomainSystem\Core\Update\Service\UpdateNotifier;
use DomainSystem\SystemApps\SystemUpdater\Controllers\UpdateController;
use DomainSystem\SystemApps\SystemUpdater\Widgets\UpdateWidgetProvider;
use Throwable;

final class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void
    {
    }

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.session');
        $os->requireLink('core.dashboard.widgets');
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
        $os->listenHook('dashboard.register_widgets');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $rootPath = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        UpdateServiceBinder::bind($runtime, $rootPath);

        $runtime->bind(UpdateController::class, static function ($c): UpdateController {
            return new UpdateController(
                theme: $c->make(ThemeManagerInterface::class),
                session: $c->make(SessionManagerInterface::class),
                checker: $c->make(UpdateCheckerInterface::class),
                targetResolver: $c->make(UpdateTargetResolverInterface::class),
                stateStore: $c->make(UpdateStateStoreInterface::class),
                notifications: self::resolveOptional($c, NotificationManagerInterface::class),
                notifier: self::resolveOptional($c, UpdateNotifier::class),
                healthCheck: self::resolveOptional($c, HealthCheckInterface::class),
                coreUpdater: self::resolveOptional($c, CoreUpdaterInterface::class),
                backup: self::resolveOptional($c, BackupInterface::class),
                databaseBackup: self::resolveOptional($c, DatabaseBackupInterface::class),
            );
        });

        $runtime->singleton(UpdateWidgetProvider::class, static function ($c): UpdateWidgetProvider {
            return new UpdateWidgetProvider(
                checker: $c->make(UpdateCheckerInterface::class),
                targetResolver: $c->make(UpdateTargetResolverInterface::class),
                stateStore: self::resolveOptional($c, UpdateStateStoreInterface::class),
                notifier: self::resolveOptional($c, UpdateNotifier::class),
                notifications: self::resolveOptional($c, NotificationManagerInterface::class),
            );
        });

        $runtime->onHook('router.register', static function (Router $router): void {
            $router->addRoute('GET', '/admin/settings/updates', [UpdateController::class, 'index'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/check', [UpdateController::class, 'check'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/begin', [UpdateController::class, 'begin'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/advance', [UpdateController::class, 'advance'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/rollback', [UpdateController::class, 'rollback'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/backups/prune', [UpdateController::class, 'pruneBackups'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/backups/prune', [UpdateController::class, 'pruneBackups'], 'SystemUpdater', ['admin']);
            $router->addRoute('GET', '/admin/settings/updates/core/health', [UpdateController::class, 'health'], 'SystemUpdater', ['admin']);
        });

        $sessionManager = $runtime->getLink('core.session');
        $runtime->onHook('admin.menu', static function (array $menu) use ($sessionManager): array {
            $role = strtolower((string)$sessionManager->get('user_role', 'admin'));
            if ($role === 'admin') {
                $menu[] = [
                    'title' => 'Atualizações',
                    'url'   => '/admin/settings/updates',
                    'icon'  => '🔄',
                ];
            }
            return $menu;
        }, 98);

        try {
            $registry = $runtime->getLink('core.dashboard.widgets');
            if (is_object($registry) && method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($runtime->make(UpdateWidgetProvider::class));
            }
        } catch (Throwable) {
        }

        $runtime->onHook('dashboard.register_widgets', static function ($registry) use ($runtime): void {
            if (is_object($registry) && method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($runtime->make(UpdateWidgetProvider::class));
            }
        });
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        $runtime->registerCapability('system.update', 'SystemUpdater');
    }

    private static function resolveOptional(mixed $container, string $abstract): mixed
    {
        if (!is_object($container) || !method_exists($container, 'make')) {
            return null;
        }
        try {
            if (method_exists($container, 'has') && !$container->has($abstract)) {
                return null;
            }
            return $container->make($abstract);
        } catch (Throwable) {
            return null;
        }
    }
}
