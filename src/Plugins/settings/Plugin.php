<?php

namespace DomainSystem\Plugins\settings;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.session');
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface::class,
            \DomainSystem\Plugins\settings\Repositories\SettingRepository::class
        );

        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'index'], 'settings', ['admin']);
            $router->addRoute('POST', '/admin/settings', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'save'], 'settings', ['admin']);
            
            // Manutenção
            $router->addRoute('POST', '/admin/settings/maintenance', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'toggleMaintenance'], 'settings', ['admin']);
            
            // Factory Reset
            $router->addRoute('GET', '/admin/settings/factory-reset', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'factoryReset'], 'settings', ['admin']);
            $router->addRoute('POST', '/admin/settings/factory-reset/execute', [\DomainSystem\Plugins\settings\Controllers\SettingsController::class, 'executeFactoryReset'], 'settings', ['admin']);
        });

        // Adiciona ao Menu se for admin
        $sessionManager = $runtime->getLink('core.session');
        $runtime->onHook('admin.menu', function($menu) use ($sessionManager) {
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
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('settings', function ($table) {
            $table->string('key_name', 100)->primary();
            $table->text('key_value')->nullable();
        });

        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        $db = $connection->getPdo();

        // Inserir valores padro se a tabela estiver vazia
        $stmt = $db->query("SELECT COUNT(*) FROM settings");
        if ($stmt->fetchColumn() == 0) {
            $db->exec("INSERT INTO settings (key_name, key_value) VALUES 
                ('site_name', 'Meu Sistema Web'),
                ('site_slogan', 'Plataforma SaaS Universal'),
                ('site_address', 'Rua das Flores, 123 - Centro'),
                ('site_phone', '(11) 99999-9999')
            ");
        }
    }
}
