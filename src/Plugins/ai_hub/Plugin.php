<?php

namespace DomainSystem\Plugins\ai_hub;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\ai_hub\Controllers\SettingsController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // $this->container->singleton(Contracts\AiProviderInterface::class, ...);
        // $this->container->singleton(Services\AiAgentService::class);
    }

    public function boot(): void
    {
        $router = $this->container->make(Router::class);
        $router->addRoute('GET', '/admin/ai-hub', [SettingsController::class, 'index'], 'ai_hub', ['admin']);
        $router->addRoute('POST', '/admin/ai-hub/save', [SettingsController::class, 'save'], 'ai_hub', ['admin']);
        $router->addRoute('GET', '/admin/ai-hub/builder', [\DomainSystem\Plugins\ai_hub\Controllers\BuilderController::class, 'index'], 'ai_hub_builder', ['admin']);
        $router->addRoute('POST', '/admin/ai-hub/builder/generate', [\DomainSystem\Plugins\ai_hub\Controllers\BuilderController::class, 'generate'], 'ai_hub_builder_generate', ['admin']);

        $events = $this->container->make(EventDispatcher::class);
        $events->addListener('admin.menu', function(array $menu) {
            $menu[] = [
                'title' => 'Cérebro I.A.',
                'icon' => '🤖',
                'url' => '/admin/ai-hub',
                'submenu' => [
                    [
                        'title' => 'Conexões Neurais',
                        'url' => '/admin/ai-hub',
                        'icon' => '🔌'
                    ],
                    [
                        'title' => 'Plugin Builder (Forms)',
                        'url' => '/admin/ai-hub/builder',
                        'icon' => '🏗️'
                    ]
                ]
            ];
            return $menu;
        }, 900);
    }
}
