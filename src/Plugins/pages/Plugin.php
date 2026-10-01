<?php

namespace DomainSystem\Plugins\pages;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\pages\Controllers\PageAdminController;
use DomainSystem\Plugins\pages\Controllers\PageFrontController;
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
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('shortcodes.register');
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface::class,
            \DomainSystem\Plugins\pages\Repositories\PageRepository::class
        );

        // Adiciona ao Menu do Painel
        $runtime->onHook('admin.menu', function($menus, $role = 'admin') {
            if (in_array($role, ['admin', 'manager'])) {
                $menus[] = [
                    'title' => 'Páginas',
                    'url' => '/admin/pages',
                    'icon' => '📄'
                ];
            }
            return $menus;
        });

        // Rotas
        $runtime->onHook('router.register', function(Router $router) {
            // Rotas do Painel
            $router->addRoute('GET', '/admin/pages', [PageAdminController::class, 'index'], 'pages', ['admin', 'manager']);
            $router->addRoute('GET', '/admin/pages/create', [PageAdminController::class, 'create'], 'pages', ['admin', 'manager']);
            $router->addRoute('GET', '/admin/pages/edit/{id}', [PageAdminController::class, 'edit'], 'pages', ['admin', 'manager']);
            $router->addRoute('POST', '/admin/pages/store', [PageAdminController::class, 'store'], 'pages', ['admin', 'manager']);
            $router->addRoute('POST', '/admin/pages/delete/{id}', [PageAdminController::class, 'delete'], 'pages', ['admin', 'manager']);
            
            // A rota de API para carregar os templates do tema dinamicamente!
            $router->addRoute('GET', '/admin/pages/api/theme-files', [PageAdminController::class, 'getThemeFiles'], 'pages', ['admin', 'manager']);
            
            // Rota Pública (O site)
            // Alterado de /p/{slug} para /{slug} para URLs limpas (estilo WordPress)
            $router->addRoute('GET', '/{slug}', [PageFrontController::class, 'show']);
        });

        // ==================================================
        // 🛡️ SHORTCODE DE LINK BLINDADO
        // Ex: [url to="privacidade"] ou [url to="docs/index"]
        // ==================================================
        $runtime->onHook('shortcodes.register', function($manager) {
            $manager->add('url', function($attrs) {
                $to = $attrs['to'] ?? '';
                if (empty($to)) return '#';
                
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                return $baseUrl . '/' . ltrim($to, '/');
                
            }, 'Gera um link seguro para uma rota. Exemplo: href="[url to=\'sobre\']"', ['to' => 'Slug da página (ex: tutoriais)'], 'Páginas');
        });
    }

    public function activate(): void
    {
        try {
            $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
            $schema->create('pages', function ($table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->text('content')->nullable();
                $table->string('theme')->nullable();
                $table->string('template_file')->nullable();
                $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            });
            
            // Seed default pages
            $db = $this->container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
            $defaults = [
                ['tutoriais', 'Tutoriais e Cursos', '<h1>Central de Conhecimento</h1><p>Em breve nosso hub de cursos.</p>'],
                ['sobre', 'Sobre Mim', '<h1>Sobre Rachid</h1><p>Sou o criador do Domain-System OS.</p>'],
                ['termos', 'Termos de Uso', '<h1>Termos de Uso</h1><p>Estes são os termos de uso.</p>'],
                ['privacidade', 'Política de Privacidade', '<h1>Política de Privacidade</h1><p>Respeitamos seus dados.</p>']
            ];
            
            $stmt = $db->prepare("INSERT INTO pages (slug, title, content, theme, template_file) VALUES (?, ?, ?, 'rachidd', 'pages/index.php')");
            foreach ($defaults as $p) {
                $stmt->execute($p);
            }
        } catch (\Exception $e) {}
    }
}
