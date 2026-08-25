<?php

namespace DomainSystem\Core;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Workspace\WorkspaceManager;

class Application
{
    private static ?Application $instance = null;
    
    private Container $container;
    private EventDispatcher $dispatcher;
    private PluginManager $pluginManager;
    private Router $router;
    private ThemeManager $themeManager;
    private WorkspaceManager $workspaceManager;
    private string $basePath;

    public function __construct(Container $container, EventDispatcher $dispatcher, string $basePath)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->basePath = $basePath;
        $this->pluginManager = new PluginManager($container, $dispatcher);
        $this->router = new Router($container);
        
        // Define the default theme path. This can be changed later by a DB config or a plugin.
        $themePath = $basePath . '/themes/default';
        $this->themeManager = new ThemeManager($themePath);
        $this->themeManager->setDispatcher($dispatcher);
        $this->workspaceManager = new WorkspaceManager($this->container, $this->themeManager);
        
        self::$instance = $this;

        // Automatically bind itself to the container
        $this->container->singleton(Application::class, function() {
            return $this;
        });
        
        $this->container->singleton(Container::class, function() {
            return $this->container;
        });
        
        $this->container->singleton(EventDispatcher::class, function() {
            return $this->dispatcher;
        });

        $this->container->singleton(PluginManager::class, function() {
            return $this->pluginManager;
        });

        $this->container->singleton(Router::class, function() {
            return $this->router;
        });

        $this->container->singleton(ThemeManager::class, function() {
            return $this->themeManager;
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
        
        // Register workspaces from plugins
        $this->dispatcher->dispatch('workspace.register', $this->workspaceManager);
    }
}
