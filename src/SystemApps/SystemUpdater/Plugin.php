<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
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
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
        $os->listenHook('dashboard.register_widgets');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $rootPath = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        UpdateServiceBinder::bind($runtime, $rootPath);

        $runtime->onHook('router.register', static function (Router $router): void {
            $router->addRoute('GET', '/admin/settings/updates', [UpdateController::class, 'index'], 'SystemUpdater', ['admin']);
            $router->addRoute('POST', '/admin/settings/updates/core/check', [UpdateController::class, 'check'], 'SystemUpdater', ['admin']);
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

        $widgetProviderFactory = static function () use ($runtime): UpdateWidgetProvider {
            /** @var UpdateCheckerInterface $checker */
            $checker = $runtime->make(UpdateCheckerInterface::class);
            /** @var UpdateTargetResolverInterface $resolver */
            $resolver = $runtime->make(UpdateTargetResolverInterface::class);
            /** @var UpdateStateStoreInterface $stateStore */
            $stateStore = $runtime->make(UpdateStateStoreInterface::class);
            /** @var UpdateNotifier $notifier */
            $notifier = $runtime->make(UpdateNotifier::class);

            return new UpdateWidgetProvider($checker, $resolver, $stateStore, $notifier);
        };

        try {
            $registry = $runtime->getLink('core.dashboard.widgets');
            if (is_object($registry) && method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($widgetProviderFactory());
            }
        } catch (Throwable) {
        }

        $runtime->onHook('dashboard.register_widgets', static function ($registry) use ($widgetProviderFactory): void {
            if (is_object($registry) && method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($widgetProviderFactory());
            }
        });
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        $runtime->registerCapability('system.update', 'SystemUpdater');
    }
}
