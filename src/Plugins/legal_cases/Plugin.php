<?php
namespace DomainSystem\Plugins\legal_cases;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->runMigrations();

        $events = $this->container->make(EventDispatcher::class);

        $events->addListener('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
            $wm->registerWorkspace('lawyer', new \DomainSystem\Plugins\legal_cases\Workspace\LawyerWorkspace($theme));
        });

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/legal', [\DomainSystem\Plugins\legal_cases\Controllers\LegalController::class, 'index']);
        });
    }

    private function runMigrations(): void
    {
        $connection = $this->container->make(Connection::class);
        $db = $connection->getPdo();
        $db->exec("
            CREATE TABLE IF NOT EXISTS legal_cases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_name TEXT NOT NULL,
                case_number TEXT NOT NULL,
                status TEXT DEFAULT 'Ativo'
            )
        ");
        
        // Mock data
        $count = $db->query("SELECT COUNT(*) FROM legal_cases")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO legal_cases (client_name, case_number, status) VALUES ('John Doe', '00123-2026-SP', 'Aguardando Audiência')");
            $db->exec("INSERT INTO legal_cases (client_name, case_number, status) VALUES ('Wayne Enterprises', '00999-2026-SP', 'Em Recurso')");
        }
    }
}

