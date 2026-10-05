<?php

namespace DomainSystem\Plugins\flextheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Plugins\flextheme\BuilderFlex\Controllers\BuilderAdminController;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\WidgetManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\ContainerWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Widgets\TextWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Widgets\LogoWidget;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PLUGIN: FlexTheme (O Hub Visual do Domain System OS)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * O FlexTheme é o "Hub Central" de toda a camada visual do Domain System.
 * Ele concentra duas responsabilidades maiores:
 *
 *   1. MOTOR DE TEMAS: Renderiza as páginas do frontend (vitrine) usando 
 *      temas intercambiáveis (default, rachidd, etc).
 *
 *   2. BUILDER FLEX (Subplugin): Editor visual Full Site Editing (FSE)
 *      inspirado no Elementor. Permite construir páginas visualmente 
 *      usando widgets arrastáveis (Container, Text, Logo, etc).
 *
 * POR QUE O BUILDER FLEX ESTÁ DENTRO DO FLEXTHEME?
 * ─────────────────────────────────────────────────
 * O Builder Flex é, por natureza, uma extensão do motor de temas. Ele não 
 * tem razão de existir sem o FlexTheme, porque:
 *   - O Builder gera shortcodes que SÓ o motor de temas sabe renderizar.
 *   - Se o FlexTheme estiver desligado, o Builder abre mas não consegue 
 *     renderizar preview nenhum (gerando confusão pro usuário).
 *   - Em Sistemas Operacionais, "programas visuais" pertencem ao subsistema 
 *     gráfico, não ao kernel nem aos apps de usuário avulsos.
 *
 * ESTRUTURA SOLID DO BUILDER FLEX (Subplugin):
 * ────────────────────────────────────────────
 *   flextheme/
 *   ├── Plugin.php              ← Este arquivo (Hub)
 *   ├── Controllers/            ← ThemeController (renderização frontend)
 *   ├── themes/                 ← Temas visuais intercambiáveis
 *   └── BuilderFlex/            ← Subplugin do Editor Visual
 *       ├── Contracts/          ← Interfaces (WidgetInterface, WidgetManagerInterface)
 *       ├── Core/               ← Classes abstratas (AbstractWidget, ContainerWidget)
 *       ├── Widgets/            ← Widgets concretos (TextWidget, LogoWidget)
 *       ├── Controllers/        ← BuilderAdminController
 *       └── views/              ← Template do editor visual (builder_editor.php)
 *
 * PADRÃO DE PROJETO: Composite (Widgets), Hub/Gateway (FlexTheme)
 * PRINCÍPIO SOLID:   SRP (Hub só orquestra), OCP (novos widgets sem alterar core)
 */
class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ════════════════════════════════════════════════════════════════════
    //  FASE 1: NEGOCIAÇÃO (OS 2.0)
    // ════════════════════════════════════════════════════════════════════
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('admin.menu');
    }

    // ════════════════════════════════════════════════════════════════════
    //  FASE 2: EXECUÇÃO (OS 2.0)
    // ════════════════════════════════════════════════════════════════════
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // ──────────────────────────────────────────────────────────────
        //  MOTOR DE TEMAS (Renderização Frontend)
        // ──────────────────────────────────────────────────────────────
        $runtime->onHook('router.register', function(Router $router) {
            // Rotas de renderização do tema
            $router->addRoute('GET', '/', [ThemeController::class, 'renderHome']);
            $router->addRoute('GET', '/docs/{*slug}', [ThemeController::class, 'renderDoc']);
            $router->addRoute('GET', '/docs', function() {
                $base = defined('BASE_URL') ? BASE_URL : '';
                header("Location: $base/docs/index");
                exit;
            });

            // Rotas do Builder Flex (Editor Visual)
            $router->addRoute('GET', '/admin/builder', [BuilderAdminController::class, 'index'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/api/schema', [BuilderAdminController::class, 'getSchema'], 'flextheme', ['admin']);
        });

        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) {
            $sm->add('base_url', function() {
                return defined('BASE_URL') ? BASE_URL : '';
            }, 'Retorna a URL base do site (ex: /domain-system/public)');
        });

        // ──────────────────────────────────────────────────────────────
        //  BUILDER FLEX (Subplugin — Editor Visual)
        // ──────────────────────────────────────────────────────────────
        $this->bootBuilderFlex($runtime);
    }

    /**
     * Inicializa o subsistema do Builder Flex.
     * Separado em método próprio para clareza e manutenibilidade (SRP).
     */
    private function bootBuilderFlex(OsRuntimeInterface $runtime): void
    {
        // 1. Injeção de Dependência: Vincula a interface ao gerenciador concreto
        $runtime->singleton(WidgetManagerInterface::class, WidgetManager::class);

        // 2. Resolve a instância do gerenciador
        /** @var WidgetManagerInterface $manager */
        $manager = $runtime->make(WidgetManagerInterface::class);

        // 3. Registra os widgets nativos
        //    Futuramente, outros plugins poderão injetar widgets via hook 'builder.register_widgets'
        $manager->registerWidget(new ContainerWidget());
        $manager->registerWidget(new TextWidget());
        $manager->registerWidget(new LogoWidget());

        // 4. Integra com o motor de Shortcodes
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) use ($manager) {
            $manager->bootShortcodes($sm);
        });

                // 5. Adiciona o botão do Builder no menu administrativo (DENTRO DO MENU TEMAS)
        $runtime->onHook('admin.menu', function($menus, $role) {
            if (in_array($role, ['admin', 'manager'])) {
                $inserted = false;
                
                // Procura o menu "Temas" para inserir como submenu
                foreach ($menus as &$menu) {
                    if (isset($menu['title']) && $menu['title'] === 'Temas') {
                        if (!isset($menu['submenu'])) {
                            $menu['submenu'] = [];
                        }
                        $menu['submenu'][] = [
                            'title' => 'Flex Builder',
                            'url'   => '/admin/builder',
                        ];
                        $inserted = true;
                        break;
                    }
                }
                
                // Se o menu "Temas" não existir (ex: admin plugin desativado), cria avulso
                if (!$inserted) {
                    $menus[] = [
                        'title' => 'Flex Builder',
                        'url'   => '/admin/builder',
                        'icon'  => '🏗️'
                    ];
                }
            }
            return $menus;
        });
    }
}
