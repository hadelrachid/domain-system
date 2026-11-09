<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ShortcodeManagerInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\ThemePathResolverInterface;
use DomainSystem\Core\Contracts\TranslatorInterface;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartCompilerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartValidatorInterface;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Validation\ThemePartValidator;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\Contracts\MediaUploadServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartManagementServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;

/**
 * Bootstrapper dedicado do subsistema Flex Builder (SOLID - SRP / DIP).
 *
 * Isola o registro de singletons, provedores e menus administrativos do motor
 * Full Site Editing (FSE) baseado em Theme/Parts do arquivo principal Plugin.php.
 */
class BuilderFlexBootstrapper
{
    public function boot(OsRuntimeInterface $runtime): void
    {
        $basePath = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);

        // 1. Serviços de infraestrutura e aplicação do FlexTheme / Flex Builder
        $runtime->singleton(ThemeDiscoveryServiceInterface::class, fn() => new ThemeDiscoveryService($basePath));
        $runtime->singleton(MediaUploadServiceInterface::class, fn() => new MediaUploadService($basePath));

        $runtime->singleton(ThemePathResolverInterface::class, fn() => new SafeThemeResolver([
            $basePath . '/themes',
            dirname(__DIR__) . '/themes',
        ]));

        $runtime->singleton(ThemePartCompilerInterface::class, fn($c) => $c->make(ThemePartRendererInterface::class));

        $runtime->singleton(ThemePartValidatorInterface::class, function ($c) {
            return new ThemePartValidator(
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class)
            );
        });

        $runtime->singleton(ThemePartPreviewServiceInterface::class, function ($c) {
            return new ThemePartPreviewService(
                $c->make(ThemePartCompilerInterface::class)
            );
        });

        $runtime->singleton(ThemePartManagementServiceInterface::class, function ($c) {
            return new ThemePartManagementService(
                $c->make(ThemePartRepositoryInterface::class),
                $c->make(ThemePartAssociationInterface::class),
                $c->make(ThemeDiscoveryServiceInterface::class),
                $c->make(ThemePartValidatorInterface::class)
            );
        });
        $runtime->singleton(ThemePartManagementService::class, fn($c) => $c->make(ThemePartManagementServiceInterface::class));

        // 2. Controllers do FlexTheme / Flex Builder com todas as dependências injetadas via Container
        $runtime->singleton(ThemeController::class, function ($c) use ($basePath) {
            $siteState = $c->has(SiteStateInterface::class) ? $c->make(SiteStateInterface::class) : new EnvSiteState();
            $partRenderer = $c->has(ThemePartRendererInterface::class) ? $c->make(ThemePartRendererInterface::class) : null;
            $shortcodes = $c->has(ShortcodeManagerInterface::class)
                ? $c->make(ShortcodeManagerInterface::class)
                : ($c->has(ShortcodeManager::class) ? $c->make(ShortcodeManager::class) : null);
            $urlResolver = $c->has(PublicUrlResolverInterface::class) ? $c->make(PublicUrlResolverInterface::class) : new BaseUrlResolver();
            $themeResolver = $c->make(ThemePathResolverInterface::class);

            return new ThemeController(
                $siteState,
                $partRenderer,
                $shortcodes,
                $urlResolver,
                $themeResolver,
                $basePath
            );
        });

        $runtime->singleton(ThemePartsAdminController::class, function ($c) use ($basePath) {
            $shortcodes = $c->has(ShortcodeManagerInterface::class)
                ? $c->make(ShortcodeManagerInterface::class)
                : $c->make(ShortcodeManager::class);
            $urlResolver = $c->has(PublicUrlResolverInterface::class)
                ? $c->make(PublicUrlResolverInterface::class)
                : new BaseUrlResolver();

            return new ThemePartsAdminController(
                $c->make(ThemePartRepositoryInterface::class),
                $c->make(ThemePartAssociationInterface::class),
                $c->make(DesignTokensInterface::class),
                $c->make(BlockRegistryInterface::class),
                $c->make(LayoutGridInterface::class),
                $c->make(ThemePartRendererInterface::class),
                $c->make(ThemeManagerInterface::class),
                $shortcodes,
                $c->has(MenuProviderInterface::class) ? $c->make(MenuProviderInterface::class) : null,
                $c->has(SessionManagerInterface::class) ? $c->make(SessionManagerInterface::class) : null,
                $basePath,
                $c->make(ThemeDiscoveryServiceInterface::class),
                $c->make(MediaUploadServiceInterface::class),
                $c->has(TranslatorInterface::class) ? $c->make(TranslatorInterface::class) : null,
                $c->has(ElementStyleRendererInterface::class) ? $c->make(ElementStyleRendererInterface::class) : null,
                $c->make(ThemePartPreviewServiceInterface::class),
                $c->make(ThemePartManagementServiceInterface::class),
                $urlResolver
            );
        });

        // 3. Adiciona o submenu do Flex Builder e Design Tokens dentro do menu "Temas"
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
