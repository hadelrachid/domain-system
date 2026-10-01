<?php

namespace DomainSystem\Plugins\SystemAdmin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;
use DomainSystem\Plugins\SystemAdmin\Controllers\DashboardController;
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
        $os->listenHook('router.before_dispatch');
        $os->listenHook('workspace.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('router.register');
        $os->listenHook('dashboard.register_widgets');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface::class,
            \DomainSystem\Plugins\SystemAdmin\Repositories\DashboardRepository::class
        );

        $sessionManager = $runtime->getLink('core.session');

        // Adiciona links na Sidebar do Painel
        $runtime->onHook('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Temas',
                    'url' => '/admin/themes',
                    'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>'
                ];
                $menus[] = [
                    'title' => 'Catálogo de Shortcodes',
                    'url' => '/admin/shortcodes',
                    'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>'
                ];
                $menus[] = [
                    'title' => 'Módulos (Plugins)',
                    'url' => '/admin/plugins',
                    'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"></path></svg>'
                ];
            }
            return $menus;
        });

        // --- THE EMERGENCY HATCH (REDE DE SEGURANÇA) ---
        // Prioridade 999 garante que executa DEPOIS de todos os outros plugins.
        // Se o plugin Auth estivesse vivo, ele já teria redirecionado e dado EXIT.
        $runtime->onHook('router.before_dispatch', function(string $uri) use ($sessionManager) {
            if (str_starts_with($uri, '/admin') && !str_starts_with($uri, '/admin/emergency')) {
                if (!$sessionManager->has('user_id')) {
                    header("Location: " . BASE_URL . "/admin/emergency");
                    exit;
                }
            }
        }, 999);

        $runtime->onHook('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
            $wm->registerWorkspace('receptionist', new \DomainSystem\Plugins\SystemAdmin\Workspace\ReceptionWorkspace($theme));
        });

        try {
            $registry = \DomainSystem\Core\Application::getInstance()->getContainer()->make(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class);
            $registry->registerProvider(new \DomainSystem\Plugins\SystemAdmin\Widgets\SystemWidgetProvider());
        } catch (\Exception $e) {}

        // O Plugue (Macho) se conectando à Régua de Tomadas!
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            $shortcodes->add('info_sistema', function($attr) {
                $color = $attr['color'] ?? 'black';
                return "<div style='padding: 10px; background-color: {$color}; color: white; border-radius: 5px;'>
                            <strong>CockPIT Info:</strong> Versão PHP: " . phpversion() . "
                        </div>";
            }, 'Exibe as informações do sistema.', ['color' => 'Cor de fundo do widget']);
        });

        $runtime->onHook('router.register', function(Router $router) use ($sessionManager) {
            // Redireciona a raiz para o admin ou cockpit
            $router->addRoute('GET', '/', function() use ($sessionManager) {
                if ($sessionManager->has('user_role')) {
                    $app = \DomainSystem\Core\Application::getInstance();
                    if ($app->getContainer()->has(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class)) {
                        $registry = $app->getContainer()->make(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class);
                        $provider = $registry->getProviderForRole($sessionManager->get('user_role'));
                        if ($provider) {
                            header("Location: " . BASE_URL . $provider->getDashboardRoute());
                            exit;
                        }
                    }
                }
                header("Location: " . BASE_URL . "/admin");
                exit;
            });

            // Rota de Emergência (Independente de Auth)
            $router->addRoute('GET', '/admin/emergency', [\DomainSystem\Plugins\SystemAdmin\Controllers\EmergencyController::class, 'index']);
            $router->addRoute('POST', '/admin/emergency', [\DomainSystem\Plugins\SystemAdmin\Controllers\EmergencyController::class, 'login']);

            // Dashboard base
            $router->addRoute('GET', '/admin', [DashboardController::class, 'index'], 'system-admin', ['admin', 'doctor', 'receptionist']);
            $router->addRoute('POST', '/admin/dashboard/save-layout', [DashboardController::class, 'saveLayout'], 'system-admin', ['admin', 'doctor', 'receptionist']);

            $router->addRoute('GET', '/admin/shortcodes', [AdminController::class, 'listShortcodes'], 'system-admin', ['admin']);
            $router->addRoute('GET', '/admin/plugins', [AdminController::class, 'listPlugins'], 'system-admin', ['admin']);
            $router->addRoute('GET', '/admin/themes', [AdminController::class, 'listThemes'], 'system-admin', ['admin']);
            $router->addRoute('GET', '/admin/themes/preview', [AdminController::class, 'previewTheme'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/themes/create', [AdminController::class, 'createTheme'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/themes/upload', [AdminController::class, 'uploadTheme'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/themes/delete', [AdminController::class, 'deleteTheme'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/plugins/toggle', [AdminController::class, 'togglePlugin'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/plugins/upload', [AdminController::class, 'uploadPlugin'], 'system-admin', ['admin']);
            $router->addRoute('POST', '/admin/plugins/delete', [AdminController::class, 'deletePlugin'], 'system-admin', ['admin']);
        });
    }
}





