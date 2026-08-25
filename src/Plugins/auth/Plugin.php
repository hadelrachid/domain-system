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
        $queryBuilder = $this->container->make(\DomainSystem\Plugins\Database\QueryBuilder::class);
        
        // A Tomada (TwoFactorService) precisa ser ÚNICA na memória para que os outros plugins encontrem os mesmos plugues
        $this->container->singleton(\DomainSystem\Plugins\auth\Services\TwoFactorService::class, function() {
            return new \DomainSystem\Plugins\auth\Services\TwoFactorService();
        });
        $twoFactorService = $this->container->make(\DomainSystem\Plugins\auth\Services\TwoFactorService::class);
        
        $twoFactorService->registerProvider('app', new \DomainSystem\Plugins\auth\Services\Providers\AppProvider());
        $twoFactorService->registerProvider('email', new \DomainSystem\Plugins\auth\Services\Providers\EmailProvider($queryBuilder));

        // 4. Registrar rotas do Auth
        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);
        
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'showLoginForm']);
            $router->addRoute('POST', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'authenticate']);
            $router->addRoute('GET', '/logout', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'logout']);

            // Usuários e 2FA
            $router->addRoute('GET', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'index']);
            $router->addRoute('POST', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'store']);
            $router->addRoute('GET', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'generate2fa']);
            $router->addRoute('POST', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'confirm2fa']);
            $router->addRoute('GET', '/admin/users/2fa-disable', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'disable2fa']);
            $router->addRoute('POST', '/admin/users/2fa-type', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'change2faType']);
            $router->addRoute('POST', '/admin/users/reset-password', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'resetPassword']);
        });

        // Adicionar ao Menu (Apenas se for admin)
        $events->addListener('admin.menu', function($menu) {
            $role = strtolower($_SESSION['user_role'] ?? 'admin');
            if ($role === 'admin') {
                $menu[] = [
                    'title' => 'Usuários',
                    'url' => '/admin/users',
                    'icon' => '👥'
                ];
            }
            return $menu;
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
                
                // ACL Rules (Controle de Acesso)
                $role = strtolower($_SESSION['user_role'] ?? 'admin');
                
                if ($role === 'receptionist') {
                    // Recepcionista NÃO pode acessar:
                    // Médicos, Usuários, Plugins e o Prontuário Médico
                    if (str_starts_with($uri, '/admin/doctors') || 
                        str_starts_with($uri, '/admin/users') || 
                        str_starts_with($uri, '/admin/plugins') || 
                        str_starts_with($uri, '/admin/appointments/record')) {
                        die('<div style="padding:20px; font-family:sans-serif; text-align:center;"><h2>Acesso Negado 🛑</h2><p>Perfil de <b>Recepcionista</b> não tem permissão para acessar esta área, como o Prontuário Médico, por respeito à LGPD.</p><a href="'.BASE_URL.'/admin">Voltar</a></div>');
                    }
                }
                
                if ($role === 'doctor') {
                    // Médico NÃO pode acessar:
                    // Médicos (cadastro), Usuários, Plugins e Pacientes (cadastro mestre, ele acessa via prontuário)
                    if (str_starts_with($uri, '/admin/doctors') || 
                        str_starts_with($uri, '/admin/users') || 
                        str_starts_with($uri, '/admin/patients') || 
                        str_starts_with($uri, '/admin/plugins')) {
                        die('<div style="padding:20px; font-family:sans-serif; text-align:center;"><h2>Acesso Negado 🛑</h2><p>Perfil de <b>Médico</b> não tem permissão para acessar o painel administrativo raiz.</p><a href="'.BASE_URL.'/admin/appointments">Voltar</a></div>');
                    }
                }
                
                if ($role === 'lawyer') {
                    if (!str_starts_with($uri, '/admin/legal') && $uri !== '/admin/logout' && $uri !== '/admin') {
                        die('<div style="padding:20px; font-family:sans-serif; text-align:center;"><h2>Acesso Negado ⚖️</h2><p>Advogados só podem acessar o Workspace Jurídico.</p><a href="'.BASE_URL.'/admin/legal">Acessar Processos</a></div>');
                    }
                }
            }
        });
    }

    private function runMigrations(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
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
    }
}
