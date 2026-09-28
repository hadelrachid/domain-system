<?php

namespace DomainSystem\Plugins\pages;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\pages\Controllers\PageAdminController;
use DomainSystem\Plugins\pages\Controllers\PageFrontController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface::class,
            \DomainSystem\Plugins\pages\Repositories\PageRepository::class
        );

        // Adiciona ao Menu do Painel
        $runtime->onHook('admin.menu', function($menus, $role = 'admin') {
            if (in_array($role, ['admin', 'manager', 'receptionist'])) {
                $menus[] = [
                    'title' => 'Páginas',
                    'url' => '/admin/pages',
                    'icon' => '📄'
                ];
            }
            return $menus;
        });

        // Rotas
        $runtime->onHook('router.register', function(Router $router) {
            // Rotas do Painel
            $router->addRoute('GET', '/admin/pages', [PageAdminController::class, 'index']);
            $router->addRoute('GET', '/admin/pages/create', [PageAdminController::class, 'create']);
            $router->addRoute('GET', '/admin/pages/edit/{id}', [PageAdminController::class, 'edit']);
            $router->addRoute('POST', '/admin/pages/store', [PageAdminController::class, 'store']);
            $router->addRoute('POST', '/admin/pages/delete/{id}', [PageAdminController::class, 'delete']);
            
            // Rota Pública (O site)
            $router->addRoute('GET', '/p/{slug}', [PageFrontController::class, 'show']);
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('pages', function ($table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        });
    }
}
