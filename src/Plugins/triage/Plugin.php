<?php
namespace DomainSystem\Plugins\triage;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->runMigrations();

        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/triage', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'index']);
            $router->addRoute('GET', '/admin/triage/form/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'form']);
            $router->addRoute('POST', '/admin/triage/save/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'save']);
        });

        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin' || $role === 'receptionist') {
                $menus[] = [
                    'title' => 'Triagem',
                    'url' => '/admin/triage',
                    'icon' => '🩺'
                ];
            }
            return $menus;
        });
    }

    private function runMigrations(): void
    {
        $connection = $this->container->make(Connection::class);
        $db = $connection->getPdo();
        $db->exec("
            CREATE TABLE IF NOT EXISTS triage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                weight DECIMAL(5,2),
                height DECIMAL(5,2),
                blood_pressure VARCHAR(20),
                temperature DECIMAL(4,1),
                heart_rate INTEGER,
                sp02 INTEGER,
                blood_sugar INTEGER,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(appointment_id) REFERENCES appointments(id)
            )
        ");
    }
}

