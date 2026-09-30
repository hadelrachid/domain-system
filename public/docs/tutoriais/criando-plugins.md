# Como Criar Plugins no Domain-System

Os Plugins são a alma do sistema. Eles estendem rotas, conectam menus, injetam lógicas de negócios e carregam seus próprios *CockPITs* (Temas Empacotados).

## Estrutura Básica

```
/meu_plugin
├── plugin.json         <-- Metadados de instalação e UI
├── Plugin.php          <-- A classe central que herda de AbstractPlugin
└── /Controllers        <-- Seus controladores (Opcional)
└── /themes             <-- Temas empacotados específicos deste plugin (Opcional)
```

## Registrando seu Plugin na Injeção de Dependências

O arquivo `Plugin.php` é o coração. Através do método `register()`, você consegue falar com o EventDispatcher, Container de Injeção e Router!

```php
namespace DomainSystem\Plugins\meu_plugin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // 1. Ouvir Eventos
        $this->events()->addListener("router.register", function(Router $router) {
            $router->addRoute("GET", "/minha-rota", [MeuController::class, "index"], "cockpit", ["admin"]);
        });
        
        // 2. Registrar Menus no Admin
        $this->events()->addListener("admin.menu.register", function($menuManager) {
            $menuManager->addMenu("Meu App", "/admin/meu-app", "icon-star");
        });
    }
}
```

## Como Usar IA para Gerar Plugins
Se você quiser automatizar, basta fornecer a arquitetura SOLID e este JSON Schema para a I.A., e ela gerará a estrutura perfeitamente.
