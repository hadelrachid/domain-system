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
        $this->container->bind(
            \DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface::class,
            \DomainSystem\Plugins\settings\Repositories\SqliteSettingRepository::class
        );

        /** @var EventDispatcher $events */
        $events = $this->events();

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'index'], 'settings', ['admin']);
            $router->addRoute('POST', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'save'], 'settings', ['admin']);
        });

        // Adiciona ao Menu se for admin
        $sessionManager = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
        $events->addListener('admin.menu', function($menu) use ($sessionManager) {
            $role = strtolower($sessionManager->get('user_role', 'admin'));
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

    public function activate(): void
    {
        /** @var Connection $connection */
        $connection = $this->db();
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
