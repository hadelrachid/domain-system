# Guia do Desenvolvedor: Domain System Web OS

Bem-vindo ao **Ambiente Operacional Web (Web OS)** definitivo. O Domain System deixou de ser um simples CMS para se tornar um Kernel puro (Ring 0) que orquestra Plugins de Nível de Usuário (Ring 3).

Este guia oficial foi atualizado para a versão **2.1** e ensina o "Padrão Ouro" da nossa arquitetura orientada a Contratos (Interfaces).

---

## 🏗️ A Estrutura de um Plugin (Ring 3)

Um Plugin é onde reside a regra de negócios. Ele não pode acessar o Banco de Dados diretamente sem pedir permissão ao SO, e não pode quebrar o sistema se falhar (graças ao Gatekeeper).

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

### A Classe `Plugin.php` (O Padrão Ouro)

Todo plugin moderno deve estender `AbstractPlugin` e implementar a interface `OsExtensionInterface`.
A inicialização acontece em **3 Fases distintas**:

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
    // FASE 1: NEGOCIAÇÃO (Gatekeeper)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        // Declare suas intenções de uso do Kernel!
        $os->requireLink('core.db.schema'); // Permissão para criar tabelas
        $os->requireLink('core.identity');  // Permissão para validar ACL
        $os->listenHook('dashboard.register_widgets'); // Permissão para injetar widgets
    }

    // ==========================================
    // FASE 2: EXECUÇÃO (Boot)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Aqui o SO concedeu as permissões. Execute sua lógica!
        $identity = $runtime->getLink('core.identity');
        
        $runtime->onHook('dashboard.register_widgets', function($registry) use ($identity) {
            // Veja a seção "Criando Widgets" abaixo
            $registry->registerProvider(new MeuWidgetProvider($identity));
        });
    }

    // ==========================================
    // FASE 3: ATIVAÇÃO/INSTALAÇÃO (Chamado 1 vez)
    // ==========================================
    public function activate(OsRuntimeInterface $runtime): void
    {
        // 1. Crie Tabelas no Banco
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('minha_tabela', function($table) {
            $table->id();
            $table->string('nome');
        });

        // 2. Registre Permissões de Segurança (ACL) Oficialmente
        $runtime->registerCapability('meu_app.gerenciar', 'Aplicativo Meu App');
    }
}
```

---

## 🔒 Segurança e Capabilities (ACL)

O sistema de permissões abandonou o modelo rígido de "Cargos" por um modelo microscópico de **Capabilities**.
Sempre que seu plugin for instalado, ele deve registrar suas Capabilities no método `activate()`, usando:

```php
$runtime->registerCapability('slug_da_permissao', 'Nome Visível no Painel');
```
O SysAdmin poderá, através da interface do sistema, atrelar essa permissão a qualquer grupo (Admin, Editor, Visitante).
Para testar se o usuário atual tem a permissão, puxe o `core.identity` e chame:

```php
$identity = $runtime->getLink('core.identity');
$userId = $_SESSION['auth_user_id'] ?? 0;

if (!$identity->userCan($userId, 'slug_da_permissao')) {
    die("Acesso Negado!");
}
```

---

## 🧩 Criando Widgets para o Dashboard Profissional

No Painel Administrativo (`/admin`), os Widgets **não** são strings HTML cruas injetadas num hook. Eles seguem um Contrato estrito para permitir customização drag-and-drop.

Você deve criar uma classe separada implementando `DashboardWidgetProviderInterface`:

```php
<?php
namespace DomainSystem\Plugins\meu_app;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;

class MeuWidgetProvider implements DashboardWidgetProviderInterface
{
    public function getProviderName(): string {
        return 'Módulo de Relatórios';
    }

    public function getAvailableWidgets(): array {
        return [
            'grafico_vendas' => [
                'title' => 'Gráfico de Vendas Mensal',
                'description' => 'Exibe o faturamento do mês atual'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string {
        if ($widgetId === 'grafico_vendas') {
            return '<div class="widget-box">HTML do seu gráfico aqui</div>';
        }
        return '';
    }
}
```
Lembre-se de instanciar e registrar esse Provider lá dentro do Hook `dashboard.register_widgets` no seu `Plugin.php`.

---

## 🎨 Como criar Temas Visuais

Os Temas vivem em `themes/nome_do_tema/` e devem focar apenas na **Renderização Front-end**. Toda a lógica de negócios pertence aos plugins.

A view principal do painel administrativo, por exemplo, deve usar a engine de rotas para renderizar as saídas. Quando um Plugin emite:
```php
return $this->theme->render('admin/dashboard_modular', ['dados' => $dados]);
```
O arquivo procurado será `themes/admin/dashboard_modular.php`.

### Dicas para Temas:
- **Segurança XSS:** Sempre use `htmlspecialchars($var)` antes de imprimir qualquer variável na tela.
- **Isolamento:** Temas não devem fazer `SELECT` direto no banco de dados. Deixe os Repositórios do SO fazerem o trabalho pesado.

---

O seu código é isolado em Sandboxes. Use Exceções à vontade. O **No-Break Shield** garantirá que um erro de lógica no seu Plugin não derrube o sistema inteiro, apenas exiba um aviso elegante ao administrador!
