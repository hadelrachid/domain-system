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
        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface::class,
            \DomainSystem\Plugins\auth\Repositories\SqliteUserRepository::class
        );

        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface::class,
            \DomainSystem\Plugins\auth\Repositories\SqliteTwoFactorCodeStore::class
        );

        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface::class,
            \DomainSystem\Plugins\auth\Services\GoogleAuthenticatorAdapter::class
        );

        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\EmailSenderInterface::class,
            \DomainSystem\Plugins\auth\Services\PhpMailSender::class
        );

        $this->container->singleton(
            \DomainSystem\Plugins\auth\Services\TwoFactorService::class,
            function($container) {
                $service = new \DomainSystem\Plugins\auth\Services\TwoFactorService();
                $authenticator = $container->make(\DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface::class);
                $codeStore = $container->make(\DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface::class);
                $emailSender = $container->make(\DomainSystem\Plugins\auth\Contracts\EmailSenderInterface::class);
                $service->registerProvider('app', new \DomainSystem\Plugins\auth\Services\Providers\AppProvider($authenticator));
                $service->registerProvider('email', new \DomainSystem\Plugins\auth\Services\Providers\EmailProvider($codeStore, $emailSender));
                return $service;
            }
        );

        $events = $this->events();

        $events->addListener('router.register', function(\DomainSystem\Core\Routing\Router $router) {
            $router->addRoute('GET', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'showLoginForm']);
            $router->addRoute('POST', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'authenticate']);
            $router->addRoute('GET', '/logout', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'logout']);
            $router->addRoute('GET', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'index'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'store'], 'auth', ['admin']);
            $router->addRoute('GET', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'generate2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'confirm2fa'], 'auth', ['admin']);
            $router->addRoute('GET', '/admin/users/2fa-disable', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'disable2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa-type', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'change2faType'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/reset-password', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'resetPassword'], 'auth', ['admin']);
        });

        $sessionManager = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
        $events->addListener('admin.menu', function($menu) use ($sessionManager) {
            $role = strtolower($sessionManager->get('user_role', 'admin'));
            if ($role === 'admin') {
                $menu[] = ['title' => 'Usuários', 'url' => '/admin/users', 'icon' => '👥'];
            }
            return $menu;
        });

        $events->addListener('router.before_dispatch', function(string $uri) use ($sessionManager) {
            if (str_starts_with($uri, '/admin') && !str_starts_with($uri, '/admin/emergency')) {
                if (!$sessionManager->has('user_id')) {
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }
        });
    }

    public function activate(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->db();
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        try { $db->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN linked_doctor_id INTEGER NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_type VARCHAR(20) DEFAULT 'none'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_code VARCHAR(6) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_expiry DATETIME NULL"); } catch (\Exception $e) {}

        // Se a tabela estiver vazia, cria o usurio padro admin@admin.com / admin
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        if ($stmt && $stmt->fetchColumn() == 0) {
            $pass = password_hash('admin', PASSWORD_DEFAULT);
            $db->exec("INSERT INTO users (name, email, password, role) VALUES ('Administrador Geral', 'admin@admin.com', '$pass', 'admin')");
        }
    }
}
