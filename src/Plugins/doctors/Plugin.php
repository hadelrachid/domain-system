<?php

namespace Plugins\doctors;

use Core\PluginInterface;
use Core\Database;
use Core\Router;
use Core\EventDispatcher;
use Plugins\doctors\Controllers\DoctorController;

class Plugin implements PluginInterface
{
    public function register(EventDispatcher $events): void
    {
        // Registrar item no menu lateral
        $events->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Médicos',
                'url' => BASE_URL . '/admin/doctors',
                'icon' => 'dashicons-businessman' // Ícone do WordPress para usuário/profissional
            ];
            return $menu;
        });

        // Registrar rotas do CRUD de médicos e Sync WP
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/doctors', [DoctorController::class, 'index']);
            $router->addRoute('POST', '/admin/doctors', [DoctorController::class, 'store']);
            $router->addRoute('GET', '/admin/doctors/edit', [DoctorController::class, 'edit']);
            $router->addRoute('POST', '/admin/doctors/update', [DoctorController::class, 'update']);
            $router->addRoute('POST', '/admin/doctors/delete', [DoctorController::class, 'delete']);
            $router->addRoute('POST', '/admin/doctors/sync-wp', [DoctorController::class, 'syncWp']); // Rota de Sincronização
        });
    }

    public function activate(Database $db): void
    {
        // Criar tabela de médicos
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS doctors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                wp_id INTEGER NULL,
                name VARCHAR(100) NOT NULL,
                crm VARCHAR(50) NULL,
                specialty VARCHAR(100) NULL,
                consultation_time INTEGER DEFAULT 30,
                photo_url VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function deactivate(Database $db): void
    {
        // Opcional: não dropar dados ao desativar para evitar perda de médicos
    }
}
