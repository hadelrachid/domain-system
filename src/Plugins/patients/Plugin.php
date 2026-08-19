<?php

namespace DomainSystem\Plugins\patients;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\patients\Controllers\PatientController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->runMigrations();

        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);
        
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/patients', [PatientController::class, 'index']);
            $router->addRoute('POST', '/admin/patients', [PatientController::class, 'store']);
            $router->addRoute('GET', '/admin/patients/edit', [PatientController::class, 'edit']);
            $router->addRoute('POST', '/admin/patients/update', [PatientController::class, 'update']);
            $router->addRoute('POST', '/admin/patients/delete', [PatientController::class, 'delete']);
        });

        // Adiciona um link no menu lateral do admin
        $events->addListener('admin.menu', function(array $menu) {
            $menu[] = [
                'title' => 'Pacientes',
                'url' => '/admin/patients',
                'icon' => '👥'
            ];
            return $menu;
        });
    }

    private function runMigrations(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        $connection->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                email VARCHAR(255) NULL,
                phone VARCHAR(20) NULL,
                birthdate DATE NULL,
                zip_code VARCHAR(10) NULL,
                address VARCHAR(255) NULL,
                address_number VARCHAR(20) NULL,
                city VARCHAR(100) NULL,
                state VARCHAR(50) NULL,
                insurance_number VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}
