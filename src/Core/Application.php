<?php

namespace DomainSystem\Core;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Workspace\WorkspaceManager;

class Application
{
    private static ?Application $instance = null;
    
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private PluginManager $pluginManager;
    private Router $router;
    private ThemeManager $themeManager;
    private \DomainSystem\Core\Theme\ShortcodeManager $shortcodeManager;
    private WorkspaceManager $workspaceManager;
    private \DomainSystem\Core\Http\SessionManager $sessionManager;
    private \DomainSystem\Core\Cockpit\CockpitRegistry $cockpitRegistry;
    private string $basePath;
    
    private \DomainSystem\Core\Tenant\TenantContext $tenantContext;
    private \DomainSystem\Core\Tenant\TenantManager $tenantManager;

    public function __construct(ContainerInterface $container, EventDispatcherInterface $dispatcher, string $basePath)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->basePath = $basePath;
        
        $this->tenantContext = new \DomainSystem\Core\Tenant\TenantContext();
        $this->tenantManager = new \DomainSystem\Core\Tenant\TenantManager($this->tenantContext);
        
        $this->sessionManager = new \DomainSystem\Core\Http\SessionManager();
        $this->sessionManager->start(); // Start session securely on boot if HTTP context

        $this->pluginManager = new PluginManager($container, $dispatcher);
        $this->router = new Router($container);
        
        // Instantiate ShortcodeManager
        $this->shortcodeManager = new \DomainSystem\Core\Theme\ShortcodeManager($this->container);

        // Define the default theme path. This can be changed later by a DB config or a plugin.
        $themePath = $basePath . '/themes/admin';
        $this->themeManager = new ThemeManager($themePath, $this->shortcodeManager);
        $this->themeManager->setDispatcher($dispatcher);
        
        $this->workspaceManager = new WorkspaceManager($this->container, $this->themeManager);
        
        $this->cockpitRegistry = new \DomainSystem\Core\Cockpit\CockpitRegistry();
        
        self::$instance = $this;

        // Automatically bind itself to the container
        $this->container->singleton(Application::class, function() {
            return $this;
        });
        
        $this->container->singleton(\DomainSystem\Core\Tenant\TenantContext::class, function() {
            return $this->tenantContext;
        });
        
        $this->container->singleton(\DomainSystem\Core\Tenant\TenantManager::class, function() {
            return $this->tenantManager;
        });
        
        $this->container->singleton(\DomainSystem\Core\Http\SessionManager::class, function() {
            return $this->sessionManager;
        });
        
        $this->container->singleton(Container::class, function() {
            return $this->container;
        });
        
        $this->container->singleton(\DomainSystem\Core\Contracts\ContainerInterface::class, function() {
            return $this->container;
        });
        
        $this->container->singleton(EventDispatcher::class, function() {
            return $this->dispatcher;
        });
        
        $this->container->singleton(\DomainSystem\Core\Contracts\EventDispatcherInterface::class, function() {
            return $this->dispatcher;
        });

        $this->container->singleton(PluginManager::class, function() {
            return $this->pluginManager;
        });

        $this->container->singleton(Router::class, function() {
            return $this->router;
        });
        
        $this->container->singleton(\DomainSystem\Core\Contracts\RouterInterface::class, function() {
            return $this->router;
        });

        $this->container->singleton(ThemeManager::class, function() {
            return $this->themeManager;
        });
        
        $this->container->singleton(\DomainSystem\Core\Theme\ShortcodeManager::class, function() {
            return $this->shortcodeManager;
        });
        
        $this->container->singleton(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class, function() {
            return $this->cockpitRegistry;
        });
        
        $this->container->singleton(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class, function() {
            return new \DomainSystem\Core\Registry\DashboardWidgetRegistry();
        });
    }

    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }
    
    public function getShortcodeManager(): \DomainSystem\Core\Theme\ShortcodeManager
    {
        return $this->shortcodeManager;
    }

    public function getWorkspaceManager(): WorkspaceManager
    {
        return $this->workspaceManager;
    }

    public function boot(): void
    {
        $pluginsPath = $this->basePath . '/src/Plugins';
        $configPath = $this->basePath . '/config/plugins.json';
        
        $this->pluginManager->discoverPlugins($pluginsPath, $configPath);
        
        // Load plugins
        $this->pluginManager->bootPlugins();
        
        // Dispatch the init hook, giving plugins a chance to register their components
        $this->dispatcher->dispatch('init');
        
        // Dispatch the shortcodes registration hook
        $this->dispatcher->dispatch('shortcodes.register', $this->shortcodeManager);
        
        // Register workspaces from plugins
        $this->dispatcher->dispatch('workspace.register', $this->workspaceManager);
    }
}
