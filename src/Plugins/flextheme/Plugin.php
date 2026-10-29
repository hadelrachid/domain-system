<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;
use DomainSystem\Plugins\flextheme\Services\BuilderFlexBootstrapper;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PLUGIN: FlexTheme (O Hub Visual do Domain System OS)
 * ════════════════════════════════════════════════════════════════════════════
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
        //  BUILDER FLEX (Subplugin — Bootstrapper Dedicado SRP)
        // ──────────────────────────────────────────────────────────────
        (new BuilderFlexBootstrapper())->boot($runtime);
    }
}
