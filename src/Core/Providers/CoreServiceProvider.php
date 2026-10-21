<?php

namespace DomainSystem\Core\Providers;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;

class CoreServiceProvider
{
    public function register(ContainerInterface $container, EventDispatcherInterface $dispatcher, string $basePath): void
    {
        // 0. Bind the passed Dispatcher
        $container->singleton(\DomainSystem\Core\Contracts\EventDispatcherInterface::class, function() use ($dispatcher) {
            return $dispatcher;
        });

        // 1. Database Configuration (Agnostic - DIP Compliant)
        $container->singleton(\DomainSystem\Core\Config\DatabaseConfig::class, function() use ($basePath) {
            $dsn = $_ENV["DB_DSN"] ?? getenv("DB_DSN") ?: "sqlite:" . $basePath . "/database.sqlite";
            $user = $_ENV["DB_USER"] ?? getenv("DB_USER") ?: "";
            $pass = $_ENV["DB_PASS"] ?? getenv("DB_PASS") ?: "";
            return new \DomainSystem\Core\Config\DatabaseConfig($dsn, $user, $pass);
        });

        // 1.4 Password Policy
        $container->singleton(\DomainSystem\Core\Contracts\PasswordPolicyInterface::class, function() {
            return new \DomainSystem\Core\Security\PasswordPolicy();
        });

        // 1.45 Session Manager (Consolidado em Core\Security com alias em Core\Http)
        $container->singleton(\DomainSystem\Core\Security\SessionManager::class, function() {
            return new \DomainSystem\Core\Http\SessionManager();
        });
        $container->singleton(\DomainSystem\Core\Http\SessionManager::class, function($c) {
            return $c->make(\DomainSystem\Core\Security\SessionManager::class);
        });
        $container->singleton(\DomainSystem\Core\Contracts\SessionManagerInterface::class, function($c) {
            return $c->make(\DomainSystem\Core\Security\SessionManager::class);
        });

        // 1.5 Security & ACL Engine
        $container->singleton(\DomainSystem\Core\Security\IdentityManager::class, function($c) {
            return new \DomainSystem\Core\Security\IdentityManager($c);
        });

        // Core Monitoring Services
        $container->singleton(\DomainSystem\Core\Contracts\NotificationManagerInterface::class, function() use ($basePath) {
            return new \DomainSystem\Core\Monitoring\NotificationManager($basePath);
        });

        $container->singleton(\DomainSystem\Core\Monitoring\RequestMetricsRecorder::class, function() use ($basePath) {
            return new \DomainSystem\Core\Monitoring\RequestMetricsRecorder($basePath);
        });

        $container->singleton(\DomainSystem\Core\Plugin\Services\ProcessRegistry::class, function() use ($basePath) {
            return new \DomainSystem\Core\Plugin\Services\ProcessRegistry($basePath);
        });

        // 1.55 Atomic JSON Store & Resilience Services (v2.4)
        $container->singleton(\DomainSystem\Core\Contracts\JsonStoreInterface::class, function() {
            return new \DomainSystem\Core\Storage\AtomicJsonStore();
        });

        $container->singleton(\DomainSystem\Core\Error\CircuitBreaker::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Error\CircuitBreaker(
                $basePath . '/config/plugins.json',
                $basePath . '/config/disarmed.json',
                $c->make(\DomainSystem\Core\Contracts\JsonStoreInterface::class)
            );
        });

        $container->singleton(\DomainSystem\Core\Database\PluginMigrationScanner::class, function($c) use ($basePath) {
            $db = $c->has(\DomainSystem\Core\Contracts\DatabaseConnectionInterface::class)
                ? $c->make(\DomainSystem\Core\Contracts\DatabaseConnectionInterface::class)
                : null;
            return new \DomainSystem\Core\Database\PluginMigrationScanner($basePath, $db);
        });

        $container->singleton(\DomainSystem\Core\Contracts\SchemaDoctorInterface::class, function($c) {
            return $c->make(\DomainSystem\Core\Database\PluginMigrationScanner::class);
        });

        $container->singleton(\DomainSystem\Core\Storage\StateSnapshotManager::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Storage\StateSnapshotManager(
                $basePath,
                $c->make(\DomainSystem\Core\Contracts\JsonStoreInterface::class)
            );
        });

        // 1.6 Public Storage (Agnostic - XAMPP / Hostinger / Cloud)
        $container->singleton(\DomainSystem\Core\Contracts\PublicStorageInterface::class, function() use ($basePath) {
            $baseUrlProvider = function() {
                return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            };
            return new \DomainSystem\Core\Storage\LocalPublicStorage($basePath, $baseUrlProvider);
        });

        // Plugin Services
        $container->singleton(\DomainSystem\Core\Plugin\Services\PluginStateManager::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Plugin\Services\PluginStateManager(
                $basePath,
                $c->make(\DomainSystem\Core\Contracts\JsonStoreInterface::class)
            );
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
                $c->make(\DomainSystem\Core\Security\SessionManager::class)
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
                $c->make(\DomainSystem\Core\Plugin\Services\PluginBootStack::class),
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

        // 4.1. Theme Activator & Site State
        $container->singleton(\DomainSystem\Core\Contracts\ThemeActivatorInterface::class, function($c) use ($basePath, $dispatcher) {
            return new \DomainSystem\Core\Theme\ThemeActivator($basePath, null, $dispatcher);
        });

        $container->singleton(\DomainSystem\Core\Contracts\SiteStateInterface::class, function() {
            return new \DomainSystem\Core\Theme\EnvSiteState();
        });

        // 4.2. Navigation Manager
        $container->singleton(\DomainSystem\Core\Contracts\NavigationManagerInterface::class, function($c) {
            return new \DomainSystem\Core\Theme\NavigationManager();
        });

        // 4.3. Theme Parts (Header & Footer Builder Engine)
        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface::class, function() use ($basePath) {
            return new \DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository($basePath . '/config/theme_parts');
        });

        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface::class, function() use ($basePath) {
            return new \DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation($basePath . '/config/theme_parts_associations.json');
        });

        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface::class, function() use ($basePath) {
            return new \DomainSystem\Core\Theme\Parts\Models\DesignTokens($basePath . '/config/design_tokens.json');
        });

        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface::class, function() {
            return new \DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer();
        });

        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface::class, function($c) {
            $menuProvider = $c->has(\DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface::class)
                ? $c->make(\DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface::class)
                : null;
            $sm = $c->make(\DomainSystem\Core\Theme\ShortcodeManager::class);
            return \DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory::create($menuProvider, $sm);
        });

        $container->singleton(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class, function($c) use ($basePath) {
            return new \DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer(
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface::class),
                $basePath
            );
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
