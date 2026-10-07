<?php
namespace DomainSystem\Plugins\flex_builder;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\RouteRegistry;

class Plugin extends AbstractPlugin
{
    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    public function getPath(): string
    {
        return __DIR__;
    }

    public function register(\DomainSystem\Core\Container $container): void
    {
        // Registro de dependências no container, se houver
    }

    public function registerRoutes(RouteRegistry $registry): void
    {
        // Rota protegida do painel admin para o construtor Flex
        $registry->get('/admin/builder', 'DomainSystem\Plugins\flex_builder\Controllers\BuilderController@index', ['admin']);
    }

    public function boot(): void
    {
        // Registra o menu no painel admin
        $this->addAdminMenu();
    }

    private function addAdminMenu(): void
    {
        add_action('admin_menu', function ($menus) {
            $menus[] = [
                'title' => 'Construtor Flex',
                'url' => '/admin/builder',
                'icon' => 'fas fa-object-group',
                'order' => 15
            ];
            return $menus;
        });
    }
}
