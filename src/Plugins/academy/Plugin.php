<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Controllers\CourseAdminController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LocalCourseImageStorage;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema');
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Injeção de Dependências no Container
        $runtime->singleton(CourseRepositoryInterface::class, function() use ($runtime) {
            $db = $runtime->getLink('core.db')->getPdo();
            return new PdoCourseRepository($db);
        });

        $runtime->singleton(CourseImageStorageInterface::class, function() {
            $uploadDir = defined('DOMAIN_SYSTEM_ROOT') 
                ? DOMAIN_SYSTEM_ROOT . '/uploads' 
                : dirname(__DIR__, 3) . '/uploads';
            return new LocalCourseImageStorage($uploadDir);
        });

        $runtime->singleton(CourseService::class, function() use ($runtime) {
            return new CourseService(
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->make(CourseImageStorageInterface::class)
            );
        });

        $runtime->singleton(CourseCatalogRenderer::class, function() use ($runtime) {
            return new CourseCatalogRenderer(
                $runtime->make(CourseRepositoryInterface::class)
            );
        });

        // 2. Menu Administrativo
        $runtime->onHook('admin.menu', function($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Academy (Cursos)',
                    'url'   => '/admin/academy',
                    'icon'  => '🎓'
                ];
            }
            return $menus;
        });

        // 3. Rotas Administrativas (CRUD Completo)
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/academy', [CourseAdminController::class, 'index'], 'academy', ['admin']);
            $router->addRoute('GET', '/admin/academy/edit/{id}', [CourseAdminController::class, 'edit'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/store', [CourseAdminController::class, 'store'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/update/{id}', [CourseAdminController::class, 'update'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/delete/{id}', [CourseAdminController::class, 'delete'], 'academy', ['admin']);
        });

        // 4. Shortcode: [hub_de_cursos]
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $manager) use ($runtime) {
            $manager->add('hub_de_cursos', function($attrs = []) use ($runtime) {
                try {
                    /** @var CourseCatalogRenderer $renderer */
                    $renderer = $runtime->make(CourseCatalogRenderer::class);
                    return $renderer->render(is_array($attrs) ? $attrs : []);
                } catch (\Throwable $e) {
                    return "<div style='text-align:center; padding:30px; background:rgba(255,255,255,0.03); border-radius:8px; color:var(--text-muted, #8c8f94);'>Hub de Cursos em configuração.</div>";
                }
            });
        });
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        $db = $runtime->getLink('core.db')->getPdo();
        new PdoCourseRepository($db);
    }
}
