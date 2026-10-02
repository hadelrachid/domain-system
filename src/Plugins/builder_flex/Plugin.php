<?php

namespace DomainSystem\Plugins\builder_flex;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\builder_flex\Controllers\BuilderAdminController;
use DomainSystem\Plugins\builder_flex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\builder_flex\Core\WidgetManager;
use DomainSystem\Plugins\builder_flex\Core\ContainerWidget;
use DomainSystem\Plugins\builder_flex\Widgets\TextWidget;
use DomainSystem\Plugins\builder_flex\Widgets\LogoWidget;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        // Negociação de recursos com o SO
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. INJEÇÃO DE DEPENDÊNCIA: Vincula a interface ao gerenciador concreto
        $this->container->singleton(WidgetManagerInterface::class, WidgetManager::class);

        // 2. Resolve a instância
        /** @var WidgetManagerInterface $manager */
        $manager = $this->container->make(WidgetManagerInterface::class);

        // 3. Registra os widgets dinamicamente (Poderia vir de outros plugins via hooks futuramente!)
        $manager->registerWidget(new ContainerWidget());
        $manager->registerWidget(new TextWidget());
        $manager->registerWidget(new LogoWidget());

        // 4. Integra com o motor de Shortcodes do OS
        $shortcodeManager = $this->container->make(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $manager->bootShortcodes($shortcodeManager);

        // 5. Adiciona o botão do Builder Flex no menu administrativo
        $runtime->onHook('admin.menu', function($menus, $role) {
            if (in_array($role, ['admin', 'manager'])) {
                $menus[] = [
                    'title' => 'Flex Builder',
                    'url' => '/admin/builder',
                    'icon' => '🏗️'
                ];
            }
            return $menus;
        });

        // 6. Roteamento do Editor Visual
        $runtime->onHook('router.register', function(Router $router) {
            // Será o Controller que carregará o seu frontend protótipo!
            $router->addRoute('GET', '/admin/builder', [BuilderAdminController::class, 'index']);
            $router->addRoute('GET', '/admin/builder/api/schema', [BuilderAdminController::class, 'getSchema']);
        });
    }

    public function activate(): void
    {
        // Aqui criaremos tabelas no futuro se necessário (ex: global_templates para header e footer)
    }
}
