<?php

namespace DomainSystem\SystemApps\auth;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\SystemApps\auth\Controllers\AuthController;
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
        $os->requireLink('core.db.schema');
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
        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface::class,
            \DomainSystem\SystemApps\auth\Repositories\UserRepository::class
        );
        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\RoleRepositoryInterface::class,
            \DomainSystem\SystemApps\auth\Repositories\RoleRepository::class
        );
        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface::class,
            \DomainSystem\SystemApps\auth\Repositories\CapabilityRepository::class
        );


        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\TwoFactorCodeStoreInterface::class,
            \DomainSystem\SystemApps\auth\Repositories\TwoFactorCodeStore::class
        );

        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\AuthenticatorInterface::class,
            \DomainSystem\SystemApps\auth\Services\GoogleAuthenticatorAdapter::class
        );

        $runtime->bind(
            \DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface::class,
            \DomainSystem\SystemApps\auth\Services\PhpMailSender::class
        );

        $runtime->singleton(
            \DomainSystem\SystemApps\auth\Services\TwoFactorService::class,
            function($container) {
                $service = new \DomainSystem\SystemApps\auth\Services\TwoFactorService();
                $authenticator = $container->make(\DomainSystem\SystemApps\auth\Contracts\AuthenticatorInterface::class);
                $codeStore = $container->make(\DomainSystem\SystemApps\auth\Contracts\TwoFactorCodeStoreInterface::class);
                $emailSender = $container->make(\DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface::class);
                $service->registerProvider('app', new \DomainSystem\SystemApps\auth\Services\Providers\AppProvider($authenticator));
                $service->registerProvider('email', new \DomainSystem\SystemApps\auth\Services\Providers\EmailProvider($codeStore, $emailSender));
                return $service;
            }
        );

        // 2. Ouvindo Hooks com Segurança Absoluta (Aprovado pelo OsConnector)
        
        // 2.1. Rotas
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/login', [\DomainSystem\SystemApps\auth\Controllers\AuthController::class, 'showLoginForm']);
            $router->addRoute('POST', '/login', [\DomainSystem\SystemApps\auth\Controllers\AuthController::class, 'authenticate']);
            $router->addRoute('GET', '/logout', [\DomainSystem\SystemApps\auth\Controllers\AuthController::class, 'logout']);
            $router->addRoute('GET', '/admin/users', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'index'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'store'], 'auth', ['admin']);
            $router->addRoute('GET', '/admin/users/2fa', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'generate2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'confirm2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa-disable', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'disable2fa'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/2fa-type', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'change2faType'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/reset-password', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'resetPassword'], 'auth', ['admin']);
            $router->addRoute('POST', '/admin/users/delete', [\DomainSystem\SystemApps\auth\Controllers\UserController::class, 'delete'], 'auth', ['admin']);
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

    public function activate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void
    {
        try {
            $schema = $runtime->getLink('core.db.schema');
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role', 50)->default('admin');
                $table->string('two_factor_secret')->nullable();
                $table->string('two_factor_type', 20)->default('none');
                $table->string('email_2fa_code', 6)->nullable();
                $table->datetime('email_2fa_expiry')->nullable();
                $table->string('theme_color', 50)->default('default');
                $table->string('profile_image', 255)->nullable();
                $table->text('dashboard_layout')->nullable();
                $table->timestamps();
            });

            // ACL & Identity Management Tables
            $schema->create('roles', function ($table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->string('description', 255)->nullable();
                $table->boolean('is_system_locked')->default('1');
            });

            $schema->create('capabilities', function ($table) {
                $table->id();
                $table->string('slug', 100)->unique();
                $table->string('context', 100)->nullable();
            });

            $schema->create('role_capabilities', function ($table) {
                $table->integer('role_id');
                $table->integer('capability_id');
                $table->foreign('role_id', 'id', 'roles');
                $table->foreign('capability_id', 'id', 'capabilities');
            });

            $schema->create('user_roles', function ($table) {
                $table->integer('user_id');
                $table->integer('role_id');
                $table->foreign('user_id', 'id', 'users');
                $table->foreign('role_id', 'id', 'roles');
            });
            
            // Seed base Roles and migrate existing Admins
            $db = $runtime->getLink('core.db')->getPdo();
            
            // 1. Create Admin Role if not exists
            $stmt = $db->query("SELECT id FROM roles WHERE slug = 'admin'");
            $adminRole = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$adminRole) {
                $db->exec("INSERT INTO roles (slug, name, description, is_system_locked) VALUES ('admin', 'Administrador Global', 'Acesso total ao sistema', 1)");
                $adminRoleId = $db->lastInsertId();
            } else {
                $adminRoleId = $adminRole['id'];
            }
            
            // 2. Migrate existing users that have 'admin' in legacy role column
            $stmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($users as $user) {
                $check = $db->prepare("SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = ?");
                $check->execute([$user['id'], $adminRoleId]);
                if (!$check->fetch()) {
                    $insert = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    $insert->execute([$user['id'], $adminRoleId]);
                }
            }

        } catch (\Exception $e) {}

        // Fallback for existing installations (SQLite/MySQL ADD COLUMN)
        $db = $runtime->getLink('core.db')->getPdo();
        try { $db->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN two_factor_type VARCHAR(20) DEFAULT 'none'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_code VARCHAR(6) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email_2fa_expiry DATETIME NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN dashboard_layout TEXT NULL"); } catch (\Exception $e) {}
    }
}
