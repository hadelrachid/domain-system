<?php

namespace DomainSystem\Plugins\auth;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\auth\Controllers\AuthController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // Ignorado pelo OS novo
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        // Pede os serviços vitais
        $os->requireLink('core.db');
        $os->requireLink('core.session');

        // Pede permissão para ouvir eventos do Kernel
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
        $os->listenHook('router.before_dispatch');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Registra os Contratos de Segurança no Container (Local do Plugin)
        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface::class,
            \DomainSystem\Plugins\auth\Repositories\UserRepository::class
        );

        $this->container->bind(
            \DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface::class,
            \DomainSystem\Plugins\auth\Repositories\TwoFactorCodeStore::class
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

        // 2. Ouvindo Hooks com Segurança Absoluta (Aprovado pelo OsConnector)
        
        // 2.1. Rotas
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'showLoginForm']);
            $router->addRoute('POST', '/login', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'authenticate']);
            $router->addRoute('GET', '/logout', [\DomainSystem\Plugins\auth\Controllers\AuthController::class, 'logout']);
            $router->addRoute('GET', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'index'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'store'], 'auth', ['admin']);
            $router->addRoute('GET', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'generate2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'confirm2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa-disable', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'disable2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa-type', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'change2faType'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/reset-password', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'resetPassword'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/delete', [\DomainSystem\Plugins\auth\Controllers\UserController::class, 'delete'], 'auth', ['admin']);
        });

        // 2.2. Menu do Admin (Puxando a Sessão Oficial via Link)
        $sessionManager = $runtime->getLink('core.session');
        $runtime->onHook('admin.menu', function($menu) use ($sessionManager) {
            $role = strtolower($sessionManager->get('user_role', 'admin'));
            if ($role === 'admin') {
                $menu[] = ['title' => 'Usuários', 'url' => '/admin/users', 'icon' => '👥'];
            }
            return $menu;
        });

        // 2.3. Blindagem de Segurança Global
        $runtime->onHook('router.before_dispatch', function(string $uri) use ($sessionManager) {
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
        try {
            $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role', 50)->default('admin');
                $table->integer('linked_doctor_id')->nullable();
                $table->string('two_factor_secret')->nullable();
                $table->string('two_factor_type', 20)->default('none');
                $table->string('email_2fa_code', 6)->nullable();
                $table->datetime('email_2fa_expiry')->nullable();
                $table->string('theme_color', 50)->default('default');
                $table->string('profile_image', 255)->nullable();
                $table->text('dashboard_layout')->nullable();
                $table->timestamps();
            });
        } catch (\Exception $e) {}

        // Fallback for existing installations (SQLite/MySQL ADD COLUMN)
        $db = $this->container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        try { $db->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN linked_doctor_id INTEGER NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_type VARCHAR(20) DEFAULT 'none'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_code VARCHAR(6) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_expiry DATETIME NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN dashboard_layout TEXT NULL"); } catch (\Exception $e) {}
    }
}
