<?php

namespace DomainSystem\Core\Providers;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;

class CoreServiceProvider
{
    public function register(ContainerInterface $container, EventDispatcherInterface $dispatcher, string $basePath): void
    {
        // 1. Database
        $container->singleton(\DomainSystem\Plugins\Database\Connection::class, function() use ($basePath) {
            $dsn = $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'sqlite:' . $basePath . '/database.sqlite';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
            return new \DomainSystem\Plugins\Database\Connection($dsn, $user, $pass);
        });
        
        $container->singleton(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class, function($c) {
            return new \DomainSystem\Plugins\Database\Schema\SchemaBuilder($c->make(\DomainSystem\Plugins\Database\Connection::class));
        });

        // 2. Auth (Session-based)
        $container->singleton(\DomainSystem\Plugins\auth\AuthManager::class, function($c) {
            $session = $c->make(\DomainSystem\Core\Http\SessionManager::class);
            $db = $c->make(\DomainSystem\Plugins\Database\Connection::class);
            return new \DomainSystem\Plugins\auth\AuthManager($session, $db);
        });

        // Plugin Services
        $container->singleton(\DomainSystem\Core\Plugin\Services\PluginStateManager::class, function() use ($basePath) {
            return new \DomainSystem\Core\Plugin\Services\PluginStateManager($basePath);
        });
        
        $container->singleton(\DomainSystem\Core\Plugin\Services\PluginDiscoverer::class, function($c) {
            return new \DomainSystem\Core\Plugin\Services\PluginDiscoverer(
                $c, 
                $c->make(\DomainSystem\Core\Contracts\EventDispatcherInterface::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginStateManager::class)
            );
        });
        
        $container->singleton(\DomainSystem\Core\Plugin\Services\PluginBootstrapper::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Plugin\Services\PluginBootstrapper(
                $c, 
                $c->make(\DomainSystem\Core\Contracts\EventDispatcherInterface::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginStateManager::class),
                $basePath,
                $c->make(\DomainSystem\Core\Http\SessionManager::class)
            );
        });

        $container->singleton(\DomainSystem\Core\Plugin\Services\PluginInstaller::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Plugin\Services\PluginInstaller($basePath, $c->make(\DomainSystem\Core\Plugin\Services\PluginStateManager::class));
        });

        $container->singleton(\DomainSystem\Core\Plugin\PluginManager::class, function($c) {
            return new \DomainSystem\Core\Plugin\PluginManager(
                $c,
                $c->make(\DomainSystem\Core\Contracts\EventDispatcherInterface::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginStateManager::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginDiscoverer::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginBootstrapper::class),
                $c->make(\DomainSystem\Core\Plugin\Services\PluginInstaller::class)
            );
        });

        // 3. Routing
        $container->singleton(\DomainSystem\Core\Contracts\RouterInterface::class, function($c) use ($dispatcher) {
            $router = new \DomainSystem\Core\Routing\Router($c, $dispatcher);
            $router->addGlobalMiddleware(\DomainSystem\Core\Routing\Middlewares\CsrfMiddleware::class);
            $router->addGlobalMiddleware(\DomainSystem\Core\Routing\Middlewares\AuthMiddleware::class);
            return $router;
        });

        // 4. Themes & Shortcodes
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

        // Bind ThemeManagerInterface
        $container->singleton(\DomainSystem\Core\Contracts\ThemeManagerInterface::class, function($c) {
            return $c->make(\DomainSystem\Core\Theme\ThemeManager::class);
        });

        // 4.1. Navigation Manager
        $container->singleton(\DomainSystem\Core\Contracts\NavigationManagerInterface::class, function($c) {
            return new \DomainSystem\Core\Theme\NavigationManager();
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
    }
}
