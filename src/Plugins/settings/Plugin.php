<?php

namespace DomainSystem\Plugins\settings;

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
            $router->addRoute('GET', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'index'], 'settings', ['admin']);
            $router->addRoute('POST', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'save'], 'settings', ['admin']);
        });

        // Adiciona ao Menu se for admin
        $events->addListener('admin.menu', function($menu) {
            $role = strtolower($_SESSION['user_role'] ?? 'admin');
            if ($role === 'admin') {
                $menu[] = [
                    'title' => 'Configuraes',
                    'url' => '/admin/settings',
                    'icon' => '⚙️'
                ];
            }
            return $menu;
        });
    }

    private function runMigrations(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->make(Connection::class);
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key_name VARCHAR(100) PRIMARY KEY,
                key_value TEXT NULL
            )
        ");

        // Inserir valores padro se a tabela estiver vazia
        $stmt = $db->query("SELECT COUNT(*) FROM settings");
        if ($stmt->fetchColumn() == 0) {
            $db->exec("INSERT INTO settings (key_name, key_value) VALUES 
                ('clinic_name', 'Clnica Padrão'),
                ('clinic_slogan', 'Excelência em Saúde'),
                ('clinic_address', 'Rua das Flores, 123 - Centro'),
                ('clinic_phone', '(11) 99999-9999')
            ");
        }
    }
}
