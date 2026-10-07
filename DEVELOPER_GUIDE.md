# Guia do Desenvolvedor: Domain System Web OS

Bem-vindo ao **Ambiente Operacional Web (Web OS)** definitivo. O Domain System deixou de ser um simples CMS para se tornar um Kernel puro (Ring 0) que orquestra Plugins de NÃ­vel de UsuÃ¡rio (Ring 3).

Este guia oficial foi atualizado para a versÃ£o **2.1** e ensina o "PadrÃ£o Ouro" da nossa arquitetura orientada a Contratos (Interfaces).

---

## ðï¸ A Estrutura de um Plugin (Ring 3)

Um Plugin Ã© onde reside a regra de negÃ³cios. Ele nÃ£o pode acessar o Banco de Dados diretamente sem pedir permissÃ£o ao SO, e nÃ£o pode quebrar o sistema se falhar (graÃ§as ao Gatekeeper).

Crie uma pasta em `src/Plugins/nome_do_plugin/` contendo:
- `plugin.json`: Metadados.
- `Plugin.php`: O "Motor" do seu aplicativo.

### O Arquivo `plugin.json`
```json
{
    "name": "meu_app",
    "version": "1.0.0",
    "description": "Um aplicativo de exemplo",
    "author": "Sua Empresa",
    "core": false,
    "os_version": "2.1"
}
```

### A Classe `Plugin.php` (O PadrÃ£o Ouro)

Todo plugin moderno deve estender `AbstractPlugin` e implementar a interface `OsExtensionInterface`.
A inicializaÃ§Ã£o acontece em **3 Fases distintas**:

```php
<?php
namespace DomainSystem\Plugins\meu_app;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // Ignorado na V2 (Retrocompatibilidade)
    public function register(): void {}

    // ==========================================
    // FASE 1: NEGOCIAÃÃO (Gatekeeper)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        // Declare suas intenÃ§Ãµes de uso do Kernel!
        $os->requireLink('core.db.schema'); // PermissÃ£o para criar tabelas
        $os->requireLink('core.identity');  // PermissÃ£o para validar ACL
        $os->listenHook('dashboard.register_widgets'); // PermissÃ£o para injetar widgets
    }

    // ==========================================
    // FASE 2: EXECUÃÃO (Boot)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Aqui o SO concedeu as permissÃµes. Execute sua lÃ³gica!
        $identity = $runtime->getLink('core.identity');
        
        $runtime->onHook('dashboard.register_widgets', function($registry) use ($identity) {
            // Veja a seÃ§Ã£o "Criando Widgets" abaixo
            $registry->registerProvider(new MeuWidgetProvider($identity));
        });
    }

    // ==========================================
    // FASE 3: ATIVAÃÃO/INSTALAÃÃO (Chamado 1 vez)
    // ==========================================
    public function activate(OsRuntimeInterface $runtime): void
    {
        // 1. Crie Tabelas no Banco
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('minha_tabela', function($table) {
            $table->id();
            $table->string('nome');
        });

        // 2. Registre PermissÃµes de SeguranÃ§a (ACL) Oficialmente
        $runtime->registerCapability('meu_app.gerenciar', 'Aplicativo Meu App');
    }
}
```

---

## ð SeguranÃ§a e Capabilities (ACL)

O sistema de permissÃµes abandonou o modelo rÃ­gido de "Cargos" por um modelo microscÃ³pico de **Capabilities**.
Sempre que seu plugin for instalado, ele deve registrar suas Capabilities no mÃ©todo `activate()`, usando:

```php
$runtime->registerCapability('slug_da_permissao', 'Nome VisÃ­vel no Painel');
```
O SysAdmin poderÃ¡, atravÃ©s da interface do sistema, atrelar essa permissÃ£o a qualquer grupo (Admin, Editor, Visitante).
Para testar se o usuÃ¡rio atual tem a permissÃ£o, puxe o `core.identity` e chame:

```php
$identity = $runtime->getLink('core.identity');
$userId = $_SESSION['auth_user_id'] ?? 0;

if (!$identity->userCan($userId, 'slug_da_permissao')) {
    die("Acesso Negado!");
}
```

---

## ð§© Criando Widgets para o Dashboard Profissional

No Painel Administrativo (`/admin`), os Widgets **nÃ£o** sÃ£o strings HTML cruas injetadas num hook. Eles seguem um Contrato estrito para permitir customizaÃ§Ã£o drag-and-drop.

VocÃª deve criar uma classe separada implementando `DashboardWidgetProviderInterface`:

```php
<?php
namespace DomainSystem\Plugins\meu_app;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;

class MeuWidgetProvider implements DashboardWidgetProviderInterface
{
    public function getProviderName(): string {
        return 'MÃ³dulo de RelatÃ³rios';
    }

    public function getAvailableWidgets(): array {
        return [
            'grafico_vendas' => [
                'title' => 'GrÃ¡fico de Vendas Mensal',
                'description' => 'Exibe o faturamento do mÃªs atual'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string {
        if ($widgetId === 'grafico_vendas') {
            return '<div class="widget-box">HTML do seu grÃ¡fico aqui</div>';
        }
        return '';
    }
}
```
Lembre-se de instanciar e registrar esse Provider lÃ¡ dentro do Hook `dashboard.register_widgets` no seu `Plugin.php`.

---

## ð¨ Como criar Temas Visuais

Os Temas vivem em `themes/nome_do_tema/` e devem focar apenas na **RenderizaÃ§Ã£o Front-end**. Toda a lÃ³gica de negÃ³cios pertence aos plugins.

A view principal do painel administrativo, por exemplo, deve usar a engine de rotas para renderizar as saÃ­das. Quando um Plugin emite:
```php
return $this->theme->render('admin/dashboard_modular', ['dados' => $dados]);
```
O arquivo procurado serÃ¡ `themes/admin/dashboard_modular.php`.

### Dicas para Temas:
- **SeguranÃ§a XSS:** Sempre use `htmlspecialchars($var)` antes de imprimir qualquer variÃ¡vel na tela.
- **Isolamento:** Temas nÃ£o devem fazer `SELECT` direto no banco de dados. Deixe os RepositÃ³rios do SO fazerem o trabalho pesado.

---

O seu cÃ³digo Ã© isolado em Sandboxes. Use ExceÃ§Ãµes Ã  vontade. O **No-Break Shield** garantirÃ¡ que um erro de lÃ³gica no seu Plugin nÃ£o derrube o sistema inteiro, apenas exiba um aviso elegante ao administrador!
