<?php

namespace DomainSystem\Core\Providers;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;

class CoreServiceProvider
{
    public function register(ContainerInterface $container, EventDispatcherInterface $dispatcher, string $basePath): void
    {
        // 1. Session Manager
        $container->singleton(\DomainSystem\Core\Http\SessionManager::class, function() {
            $session = new \DomainSystem\Core\Http\SessionManager();
            $session->start();
            return $session;
        });

        // 2. Plugin Subsystem
        $container->singleton(\DomainSystem\Core\Plugin\PluginManager::class, function($c) use ($basePath, $dispatcher) {
            $stateManager = new \DomainSystem\Core\Plugin\Services\PluginStateManager($basePath);
            $discoverer = new \DomainSystem\Core\Plugin\Services\PluginDiscoverer($c, $dispatcher, $stateManager);
            $bootstrapper = new \DomainSystem\Core\Plugin\Services\PluginBootstrapper($c, $dispatcher, $stateManager, $basePath);
            $installer = new \DomainSystem\Core\Plugin\Services\PluginInstaller($basePath, $stateManager);
            
            return new \DomainSystem\Core\Plugin\PluginManager(
                $c, 
                $dispatcher,
                $stateManager,
                $discoverer,
                $bootstrapper,
                $installer
            );
        });

        // 3. Router
        $container->singleton(\DomainSystem\Core\Routing\Router::class, function($c) {
            $router = new \DomainSystem\Core\Routing\Router($c);
            $router->addGlobalMiddleware(\DomainSystem\Core\Routing\Middlewares\CsrfMiddleware::class);
            $router->addGlobalMiddleware(\DomainSystem\Core\Routing\Middlewares\AuthMiddleware::class);
            return $router;
        });

        $container->singleton(\DomainSystem\Core\Contracts\RouterInterface::class, function($c) {
            return $c->make(\DomainSystem\Core\Routing\Router::class);
        });

        // 4. Theme & Shortcode
        $container->singleton(\DomainSystem\Core\Theme\ShortcodeManager::class, function($c) {
            return new \DomainSystem\Core\Theme\ShortcodeManager($c);
        });

        $container->singleton(\DomainSystem\Core\Theme\ThemeManager::class, function($c) use ($basePath, $dispatcher) {
            $themePath = $basePath . '/themes/admin';
            $shortcodeManager = $c->make(\DomainSystem\Core\Theme\ShortcodeManager::class);
            $themeManager = new \DomainSystem\Core\Theme\ThemeManager($themePath, $shortcodeManager);
            $themeManager->setDispatcher($dispatcher);
            return $themeManager;
        });

        // 5. Workspace
        $container->singleton(\DomainSystem\Core\Workspace\WorkspaceManager::class, function($c) {
            $themeManager = $c->make(\DomainSystem\Core\Theme\ThemeManager::class);
            return new \DomainSystem\Core\Workspace\WorkspaceManager($c, $themeManager);
        });

        // 6. Registries
        $container->singleton(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class, function() {
            return new \DomainSystem\Core\Cockpit\CockpitRegistry();
        });

        $container->singleton(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class, function() {
            return new \DomainSystem\Core\Registry\DashboardWidgetRegistry();
        });

        $container->singleton(\DomainSystem\Core\Plugin\LinkRegistry::class, function($c) {
            return new \DomainSystem\Core\Plugin\LinkRegistry($c);
        });

        // 7. OS 2.0 Links
        $kernelConnector = new \DomainSystem\Core\Plugin\OsConnector();
        $kernelConnector->provideLink('core.session', \DomainSystem\Core\Http\SessionManager::class);
        $kernelConnector->provideLink('core.router', \DomainSystem\Core\Routing\Router::class);
        
        $linkRegistry = $container->make(\DomainSystem\Core\Plugin\LinkRegistry::class);
        $linkRegistry->registerConnector('kernel', $kernelConnector);

        // 8. Container & Dispatcher Aliases
        $container->singleton(\DomainSystem\Core\Container\Container::class, function($c) { return $c; });
        $container->singleton(\DomainSystem\Core\Contracts\ContainerInterface::class, function($c) { return $c; });
        $container->singleton(\DomainSystem\Core\Events\EventDispatcher::class, function() use ($dispatcher) { return $dispatcher; });
        $container->singleton(\DomainSystem\Core\Contracts\EventDispatcherInterface::class, function() use ($dispatcher) { return $dispatcher; });
    }
}
