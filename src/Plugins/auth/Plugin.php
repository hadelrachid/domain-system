<?php

namespace DomainSystem\Plugins\auth;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\auth\Controllers\AuthController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // 1. Iniciar sessão do PHP com segurança se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Garantir que a tabela users existe
        $this->runMigrations();

        // 3. O Container vai fazer o auto-wiring do AuthController automaticamente na hora do router!

        // 4. Registrar rotas do Auth
        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);
        
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/login', [AuthController::class, 'showLoginForm']);
            $router->addRoute('POST', '/login', [AuthController::class, 'authenticate']);
            $router->addRoute('GET', '/logout', [AuthController::class, 'logout']);
        });

        // 5. O Middleware de Proteção (O Cockpit)
        $events->addListener('router.before_dispatch', function(string $uri) {
            // Se tentar acessar o admin (qualquer coisa sob /admin)
            if (str_starts_with($uri, '/admin')) {
                // Checa se tem crachá
                if (!isset($_SESSION['user_id'])) {
                    // Sem crachá = Volta pro Login
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }
        });
    }

    private function runMigrations(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        $connection->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}
