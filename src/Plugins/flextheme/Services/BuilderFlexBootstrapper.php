<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\PageTreeRendererInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\TranslatorInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartCompilerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartValidatorInterface;
use DomainSystem\Core\Theme\Parts\Validation\ThemePartValidator;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\ContainerWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\JsonRenderer;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\WidgetManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Widgets\LogoWidget;
use DomainSystem\Plugins\flextheme\BuilderFlex\Widgets\TextWidget;
use DomainSystem\Plugins\flextheme\Contracts\MediaUploadServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;

/**
 * Bootstrapper dedicado do subsistema Builder Flex (SOLID - SRP / DIP).
 *
 * Isola o registro de singletons, provedores, widgets e menus administrativos
 * do arquivo principal Plugin.php.
 */
class BuilderFlexBootstrapper
{
    public function boot(OsRuntimeInterface $runtime): void
    {
        $basePath = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);

        // 1. Serviços de infraestrutura e aplicação do FlexTheme / Flex Builder
        $runtime->singleton(ThemeDiscoveryServiceInterface::class, fn() => new ThemeDiscoveryService($basePath));
        $runtime->singleton(MediaUploadServiceInterface::class, fn() => new MediaUploadService($basePath));

        $runtime->singleton(ThemePartCompilerInterface::class, fn($c) => $c->make(ThemePartRendererInterface::class));

        $runtime->singleton(ThemePartValidatorInterface::class, function ($c) {
            return new ThemePartValidator(
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class)
            );
        });

        $runtime->singleton(ThemePartPreviewServiceInterface::class, function ($c) {
            return new ThemePartPreviewService(
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class),
                $c->make(DesignTokensInterface::class),
                $c->has(ElementStyleRendererInterface::class) ? $c->make(ElementStyleRendererInterface::class) : null,
                $c->make(ThemePartCompilerInterface::class)
            );
        });

        $runtime->singleton(ThemePartManagementService::class, function ($c) {
            return new ThemePartManagementService(
                $c->make(ThemePartRepositoryInterface::class),
                $c->make(ThemePartAssociationInterface::class),
                $c->make(ThemeDiscoveryServiceInterface::class),
                $c->make(ThemePartValidatorInterface::class),
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class)
            );
        });

        // 2. Motor de Widgets do BuilderFlex (Subplugin)
        $runtime->singleton(WidgetManagerInterface::class, WidgetManager::class);
        $runtime->singleton(DtoRenderer::class, function ($c) {
            return new DtoRenderer(
                $c->has(HtmlSanitizerInterface::class) ? $c->make(HtmlSanitizerInterface::class) : null,
                $c->has(ElementStyleRendererInterface::class) ? $c->make(ElementStyleRendererInterface::class) : null
            );
        });
        $runtime->singleton(JsonRenderer::class, function ($c) {
            return new JsonRenderer(
                $c->make(WidgetManagerInterface::class),
                $c->make(DtoRenderer::class)
            );
        });
        $runtime->singleton(PageTreeRendererInterface::class, function ($c) {
            return $c->make(JsonRenderer::class);
        });

        // 3. Controllers do FlexTheme / Flex Builder com todas as dependências injetadas via Container
        $runtime->singleton(ThemeController::class, function ($c) use ($basePath) {
            return new ThemeController(
                $c->has(SiteStateInterface::class) ? $c->make(SiteStateInterface::class) : null,
                $c->has(ThemePartRendererInterface::class) ? $c->make(ThemePartRendererInterface::class) : null,
                $c->has(ShortcodeManager::class) ? $c->make(ShortcodeManager::class) : null,
                $c->has(PublicUrlResolverInterface::class) ? $c->make(PublicUrlResolverInterface::class) : null,
                $basePath
            );
        });

        $runtime->singleton(ThemePartsAdminController::class, function ($c) use ($basePath) {
            return new ThemePartsAdminController(
                $c->make(ThemePartRepositoryInterface::class),
                $c->make(ThemePartAssociationInterface::class),
                $c->make(DesignTokensInterface::class),
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class),
                $c->make(ThemePartRendererInterface::class),
                $c->make(ThemeManagerInterface::class),
                $c->make(ShortcodeManager::class),
                $c->has(MenuProviderInterface::class) ? $c->make(MenuProviderInterface::class) : null,
                $c->has(SessionManagerInterface::class) ? $c->make(SessionManagerInterface::class) : null,
                $basePath,
                $c->make(ThemeDiscoveryServiceInterface::class),
                $c->make(MediaUploadServiceInterface::class),
                $c->has(TranslatorInterface::class) ? $c->make(TranslatorInterface::class) : null,
                $c->has(ElementStyleRendererInterface::class) ? $c->make(ElementStyleRendererInterface::class) : null,
                $c->make(ThemePartPreviewServiceInterface::class),
                $c->make(ThemePartManagementService::class),
                $c->has(PublicUrlResolverInterface::class) ? $c->make(PublicUrlResolverInterface::class) : null
            );
        });

        // 4. Registra os widgets nativos
        /** @var WidgetManagerInterface $manager */
        $manager = $runtime->make(WidgetManagerInterface::class);
        $manager->registerWidget(new ContainerWidget());
        $manager->registerWidget(new TextWidget());
        $manager->registerWidget(new LogoWidget());

        // 5. Integra com o motor de Shortcodes
        $runtime->onHook('shortcodes.register', function (ShortcodeManager $sm) use ($manager) {
            $manager->bootShortcodes($sm);
        });

        // 6. Adiciona o submenu do Flex Builder e Design Tokens dentro do menu "Temas"
        $runtime->onHook('admin.menu', function ($menus, $role) {
            if (in_array($role, ['admin', 'manager'])) {
                $inserted = false;

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
