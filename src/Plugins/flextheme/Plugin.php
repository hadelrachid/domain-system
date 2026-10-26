<?php

namespace DomainSystem\Plugins\flextheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Contracts\PageTreeRendererInterface;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;
use DomainSystem\Plugins\flextheme\BuilderFlex\Controllers\BuilderAdminController;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\WidgetManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\ContainerWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\JsonRenderer;
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
        $os->requireLink('core.theme_parts');
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
                return Response::redirect("$base/docs/index");
            });

            // Rotas do Novo Flex Builder (Motor Visual em Blocos FSE & Contratos SOLID)
            $router->addRoute('GET', '/admin/builder', [ThemePartsAdminController::class, 'index'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/create', [ThemePartsAdminController::class, 'create'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/edit', [ThemePartsAdminController::class, 'edit'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/save-part', [ThemePartsAdminController::class, 'savePart'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/delete-part', [ThemePartsAdminController::class, 'deletePart'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/assign', [ThemePartsAdminController::class, 'assign'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/tokens', [ThemePartsAdminController::class, 'tokens'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/tokens/save', [ThemePartsAdminController::class, 'saveTokens'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/preview', [ThemePartsAdminController::class, 'preview'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/builder/upload-image', [ThemePartsAdminController::class, 'uploadImage'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/schema', [ThemePartsAdminController::class, 'schema'], 'flextheme', ['admin']);

            // Aliases de compatibilidade e navegação
            $router->addRoute('POST', '/admin/builder/save', [ThemePartsAdminController::class, 'savePart'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/builder/api/schema', [ThemePartsAdminController::class, 'schema'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/themes/parts', [ThemePartsAdminController::class, 'index'], 'flextheme', ['admin']);
            $router->addRoute('GET', '/admin/themes/tokens', [ThemePartsAdminController::class, 'tokens'], 'flextheme', ['admin']);
        });

        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) use ($runtime) {
            $sm->add('base_url', function() {
                return defined('BASE_URL') ? BASE_URL : '';
            }, 'Retorna a URL base do site (ex: /domain-system/public)');

            $sm->add('theme_header', function($attrs = []) use ($runtime) {
                try {
                    $renderer = $runtime->getLink('core.theme_parts');
                    $theme = $attrs['theme'] ?? 'default';
                    return $renderer->renderHeader($theme);
                } catch (\Throwable $e) {
                    return '';
                }
            }, 'Renderiza o Header ativo do Flex Builder (suporta atributo theme)');

            $sm->add('theme_footer', function($attrs = []) use ($runtime) {
                try {
                    $renderer = $runtime->getLink('core.theme_parts');
                    $theme = $attrs['theme'] ?? 'default';
                    return $renderer->renderFooter($theme);
                } catch (\Throwable $e) {
                    return '';
                }
            }, 'Renderiza o Rodapé ativo do Flex Builder (suporta atributo theme)');

            $sm->add('section', function($attrs = []) use ($runtime) {
                $id = $attrs['id'] ?? '';
                if ($id !== '') {
                    try {
                        $renderer = $runtime->getLink('core.theme_parts');
                        return $renderer->renderSection($id);
                    } catch (\Throwable $e) {
                        return '';
                    }
                }
                return '';
            }, 'Renderiza uma Seção específica do Flex Builder pelo ID: [section id="..."]');

            $sm->add('theme_part', function($attrs = []) use ($runtime) {
                $id = $attrs['id'] ?? '';
                if ($id !== '') {
                    try {
                        $renderer = $runtime->getLink('core.theme_parts');
                        return $renderer->renderSection($id);
                    } catch (\Throwable $e) {
                        return '';
                    }
                }
                return '';
            }, 'Renderiza uma parte específica (Header, Footer ou Seção) pelo ID: [theme_part id="..."]');
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
        $runtime->singleton(DtoRenderer::class, fn() => new DtoRenderer());
        $runtime->singleton(JsonRenderer::class, function($c) {
            return new JsonRenderer(
                $c->make(WidgetManagerInterface::class),
                $c->make(DtoRenderer::class)
            );
        });
        $runtime->singleton(PageTreeRendererInterface::class, function($c) {
            return $c->make(JsonRenderer::class);
        });

        // 2. Controller do Novo Flex Builder
        $runtime->singleton(ThemePartsAdminController::class, function($c) {
            return new ThemePartsAdminController(
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface::class),
                $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class),
                $c->make(\DomainSystem\Core\Contracts\ThemeManagerInterface::class),
                $c->make(\DomainSystem\Core\Theme\ShortcodeManager::class),
                $c->has(\DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface::class)
                    ? $c->make(\DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface::class)
                    : null,
                $c->has(\DomainSystem\Core\Contracts\SessionManagerInterface::class)
                    ? $c->make(\DomainSystem\Core\Contracts\SessionManagerInterface::class)
                    : null
            );
        });

        // 3. Resolve a instância do gerenciador de widgets
        /** @var WidgetManagerInterface $manager */
        $manager = $runtime->make(WidgetManagerInterface::class);

        // 4. Registra os widgets nativos
        $manager->registerWidget(new ContainerWidget());
        $manager->registerWidget(new TextWidget());
        $manager->registerWidget(new LogoWidget());

        // 5. Integra com o motor de Shortcodes
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) use ($manager) {
            $manager->bootShortcodes($sm);
        });

        // 6. Adiciona o submenu do Flex Builder e Design Tokens dentro do menu "Temas"
        $runtime->onHook('admin.menu', function($menus, $role) {
            if (in_array($role, ['admin', 'manager'])) {
                $inserted = false;
                
                // Procura o menu "Temas" para inserir como submenus
                foreach ($menus as &$menu) {
                    if (isset($menu['title']) && $menu['title'] === 'Temas') {
                        if (!isset($menu['submenu'])) {
                            $menu['submenu'] = [];
                        }
                        $menu['submenu'][] = [
                            'title' => 'Flex Builder',
                            'url'   => '/admin/builder',
                        ];
                        $menu['submenu'][] = [
                            'title' => 'Design Tokens',
                            'url'   => '/admin/builder/tokens',
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
                    $menus[] = [
                        'title' => 'Design Tokens',
                        'url'   => '/admin/builder/tokens',
                        'icon'  => '🎨'
                    ];
                }
            }
            return $menus;
        });
    }
}
