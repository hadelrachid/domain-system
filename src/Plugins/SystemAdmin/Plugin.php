<?php

namespace DomainSystem\Plugins\SystemAdmin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;
use DomainSystem\Plugins\SystemAdmin\Controllers\DashboardController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface::class,
            \DomainSystem\Plugins\SystemAdmin\Repositories\SqliteDashboardRepository::class
        );

        $sessionManager = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);

        $events = $this->events();

        // --- THE EMERGENCY HATCH (REDE DE SEGURANÇA) ---
        // Prioridade 999 garante que executa DEPOIS de todos os outros plugins.
        // Se o plugin Auth estivesse vivo, ele já teria redirecionado e dado EXIT.
        $events->addListener('router.before_dispatch', function(string $uri) use ($sessionManager) {
            if (str_starts_with($uri, '/admin') && !str_starts_with($uri, '/admin/emergency')) {
                if (!$sessionManager->has('user_id')) {
                    header("Location: " . BASE_URL . "/admin/emergency");
                    exit;
                }
            }
        }, 999);

        $events->addListener('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->theme();
            $wm->registerWorkspace('receptionist', new \DomainSystem\Plugins\SystemAdmin\Workspace\ReceptionWorkspace($theme));
        });

        // Shortcodes menu removed as requested

        // O Plugue (Macho) se conectando à Régua de Tomadas!
        $events->addListener('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            $shortcodes->add('info_sistema', function($attr) {
                $color = $attr['color'] ?? 'black';
                return "<div style='padding: 10px; background-color: {$color}; color: white; border-radius: 5px;'>
                            <strong>CockPIT Info:</strong> Versão PHP: " . phpversion() . "
                        </div>";
            }, 'Exibe as informações do sistema.', ['color' => 'Cor de fundo do widget']);
        });

        $events->addListener('router.register', function(Router $router) {
            // Redireciona a raiz para o admin
            $router->addRoute('GET', '/', function() { header("Location: " . BASE_URL . "/admin"); exit; });

            // Rota de Emergência (Independente de Auth)
            $router->addRoute('GET', '/admin/emergency', [\DomainSystem\Plugins\SystemAdmin\Controllers\EmergencyController::class, 'index']);
            $router->addRoute('POST', '/admin/emergency', [\DomainSystem\Plugins\SystemAdmin\Controllers\EmergencyController::class, 'login']);

            // Dashboard base
            $router->addRoute('GET', '/admin', [DashboardController::class, 'index']);

            $router->addRoute('GET', '/admin/shortcodes', [AdminController::class, 'listShortcodes']);
            $router->addRoute('GET', '/admin/plugins', [AdminController::class, 'listPlugins']);
            $router->addRoute('GET', '/admin/themes', [AdminController::class, 'listThemes']);
            $router->addRoute('GET', '/admin/themes/preview', [AdminController::class, 'previewTheme']);
            $router->addRoute('POST', '/admin/themes/create', [AdminController::class, 'createTheme']);
            $router->addRoute('POST', '/admin/themes/upload', [AdminController::class, 'uploadTheme']);
            $router->addRoute('POST', '/admin/themes/delete', [AdminController::class, 'deleteTheme']);
            $router->addRoute('POST', '/admin/plugins/toggle', [AdminController::class, 'togglePlugin']);
            $router->addRoute('POST', '/admin/plugins/upload', [AdminController::class, 'uploadPlugin']);
            $router->addRoute('POST', '/admin/plugins/delete', [AdminController::class, 'deletePlugin']);
        });
    }
}
