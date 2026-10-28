# Como Criar Plugins no Domain-System (v2.5.0)

Os Plugins são a alma do sistema. Eles estendem rotas, conectam menus, injetam lógicas de negócios, carregam seus próprios *CockPITs* (Temas Empacotados) e expõem manuais interativos auto-descobertos por contrato.

## Estrutura Básica

```
/meu_plugin
├── plugin.json         <-- Metadados de instalação e UI (os_version: "2.5")
├── Plugin.php          <-- A classe central que herda de AbstractPlugin e implementa OsExtensionInterface
├── /Controllers        <-- Seus controladores (sem acesso direto a superglobais $_SESSION)
├── /Services           <-- Camada de serviços de domínio (ex: conversores, importadores)
├── /Repositories       <-- Contratos e implementações PDO de persistência
├── /manual             <-- Auto-descoberto pelo Cockpit (/admin/manual) via ManualModuleInterface!
│   └── module.php
└── /themes             <-- Temas empacotados específicos deste plugin (Opcional)
```

## Registrando seu Plugin na Injeção de Dependências

O arquivo `Plugin.php` é o coração. Através de `osRegister()`, `osBoot()` e `register()`, você consegue negociar permissões com o Gatekeeper e falar com o `EventDispatcher`, `Container` e `Router`!

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

## Conectando seu Plugin ao Manual de Instruções do Cockpit (`ManualModuleInterface`)

Para que o seu plugin exiba automaticamente um guia passo-a-passo e um checklist de homologação (QA) dentro de **Manual de Instruções (`/admin/manual`)**, basta criar a pasta `manual/` dentro do seu plugin com um arquivo `module.php` retornando uma instância de `DomainSystem\Core\Contracts\ManualModuleInterface`:

```php
// src/Plugins/meu_plugin/manual/module.php
require_once __DIR__ . '/MeuPluginManualModule.php';
return new \DomainSystem\Plugins\meu_plugin\manual\MeuPluginManualModule();
```

O `PluginManualScanner` do Kernel fará a varredura automática de todas as pastas `src/Plugins/*/manual/module.php` e injetará o módulo na interface do Cockpit sem precisar alterar uma única linha do Kernel! Consulte a implementação completa em `src/Plugins/academy/manual/AcademyManualModule.php`.

## Gerador Oficial de Boilerplate via CLI (`os-cli.php`)

Em vez de criar pastas manualmente, use o gerador oficial na raiz do projeto:

```bash
php os-cli.php make:plugin meu_plugin
```

Ele gera automaticamente `plugin.json`, `Plugin.php` (`OsExtensionInterface`), `Contracts/`, `Controllers/`, `views/` e `manual/module.php` (`ManualModuleInterface`).

## Como Usar IA para Gerar Plugins ("Vibe Coding nos Trilhos")
O repositório inclui o guia mestre **[`AI_CONTRACT_GUIDE.md`](../../AI_CONTRACT_GUIDE.md)** na raiz, além dos arquivos automáticos `.github/copilot-instructions.md`, `.cursorrules` e `.clinerules`. Basta apontar qualquer IA para o `AI_CONTRACT_GUIDE.md` (ou clicar no botão **"📋 Copiar Prompt Mestre para IA"** dentro de `/admin/manual`), e ela gerará a estrutura 100% compatível com os contratos do Ring 3.

