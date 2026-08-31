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
        // 1. Bind da Infraestrutura (Repositório)
        $this->container->bind(
            \DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface::class,
            \DomainSystem\Plugins\doctors\Repositories\SqliteDoctorRepository::class
        );

        // 2. Bind do Fornecedor para Agendamentos (DIP)
        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class,
                \DomainSystem\Plugins\doctors\Providers\AppointmentDoctorProvider::class
            );
        }

        /** @var EventDispatcher $events */
        $events = $this->events();

        $events->addListener('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->theme();
            $wm->registerWorkspace('doctor', new \DomainSystem\Plugins\doctors\Workspace\DoctorWorkspace($theme));
        });

        // Registrar item no menu lateral
        $events->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Médicos',
                'url' => '/admin/doctors',
                'icon' => '👨‍⚕️'
            ];
            return $menu;
        });

        // Registrar rotas
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/doctors', [DoctorController::class, 'index'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors', [DoctorController::class, 'store'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('GET', '/admin/doctors/edit', [DoctorController::class, 'edit'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/update', [DoctorController::class, 'update'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/delete', [DoctorController::class, 'delete'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/sync-wp', [DoctorController::class, 'syncWp'], 'doctors', ['admin', 'receptionist']);
        });
    }

    public function activate(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->db();
        $db = $connection->getPdo();
        
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

