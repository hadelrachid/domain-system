<?php

namespace DomainSystem\Plugins\doctors;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\doctors\Controllers\DoctorController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->runMigrations();

        // Registrar item no menu lateral
        $this->getEventDispatcher()->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Médicos',
                'url' => BASE_URL . '/admin/doctors',
                'icon' => 'dashicons-businessman'
            ];
            return $menu;
        });

        // Registrar rotas
        $this->getEventDispatcher()->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/doctors', [DoctorController::class, 'index']);
            $router->addRoute('POST', '/admin/doctors', [DoctorController::class, 'store']);
            $router->addRoute('GET', '/admin/doctors/edit', [DoctorController::class, 'edit']);
            $router->addRoute('POST', '/admin/doctors/update', [DoctorController::class, 'update']);
            $router->addRoute('POST', '/admin/doctors/delete', [DoctorController::class, 'delete']);
            $router->addRoute('POST', '/admin/doctors/sync-wp', [DoctorController::class, 'syncWp']);
        });
    }

    private function runMigrations(): void
    {
        $db = $this->getContainer()->get(\DomainSystem\Plugins\Database\Database::class)->getPdo();
        
        $db->exec("
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
}
