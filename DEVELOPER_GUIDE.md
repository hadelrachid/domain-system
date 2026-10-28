# Guia do Desenvolvedor: Domain System (v2.5)

Este guia apresenta a estrutura do sistema, como desenvolver novos plugins e como estender as funcionalidades do núcleo utilizando interfaces e injeção de dependências em PHP 8.2+.

---

## 🏗️ Estrutura de um Plugin (`src/Plugins`)

No Domain System, a lógica de negócio e as funcionalidades específicas são organizadas na camada de plugins (`src/Plugins/`). 

Para criar um plugin básico, crie uma pasta em `src/Plugins/{nome_do_plugin}/` contendo:
- `plugin.json`: Manifesto de metadados do módulo.
- `Plugin.php`: Classe de inicialização que gerencia o ciclo de vida do plugin.
- `manual/module.php` *(opcional)*: Módulo de documentação e checklist exibido no painel administrativo (`/admin/manual`).

### 1. O Arquivo `plugin.json`
```json
{
    "name": "meu_app",
    "version": "2.5.0",
    "description": "Exemplo de plugin para o Domain System",
    "author": "Seu Nome ou Empresa",
    "core": false,
    "os_version": "2.5"
}
```

### 2. A Classe `Plugin.php`

Os plugins estendem `AbstractPlugin` e implementam `OsExtensionInterface`. A inicialização ocorre em fases bem definidas:

```php
<?php
namespace DomainSystem\Plugins\meu_app;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // FASE 1: REGISTRO E DECLARAÇÃO DE SERVIÇOS
    public function osRegister(OsConnectorInterface $os): void
    {
        // Declara quais serviços do núcleo e eventos o plugin utilizará
        $os->requireLink('core.db.schema');            // Acesso ao construtor de esquema
        $os->requireLink('core.identity');             // Acesso ao gerenciador de permissões
        $os->listenHook('dashboard.register_widgets'); // Escuta o evento de registro de widgets
    }

    // FASE 2: INICIALIZAÇÃO (BOOT)
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Resolução dos serviços autorizados
        $identity = $runtime->getLink('core.identity');
        
        $runtime->onHook('dashboard.register_widgets', function($registry) use ($identity) {
            $registry->registerProvider(new MeuWidgetProvider($identity));
        });
    }

    // FASE 3: ATIVAÇÃO (Executado no momento da ativação do módulo)
    public function activate(OsRuntimeInterface $runtime): void
    {
        // Criação de tabelas caso necessário
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('minha_tabela', function($table) {
            $table->id();
            $table->string('nome');
        });

        // Registro de permissões (Capabilities)
        $runtime->registerCapability('meu_app.gerenciar', 'Gerenciar Meu App');
    }
}
```

---

## 🔒 Permissões, Capabilities e Requisições HTTP

O sistema utiliza um modelo de permissões baseado em **Capabilities** granulares em vez de perfis engessados:

```php
$runtime->registerCapability('slug_da_permissao', 'Nome Visível no Painel');
```

O administrador do sistema pode associar cada permissão aos perfis desejados no painel.

Para verificar se um usuário autenticado possui determinada permissão:

```php
$identity = $runtime->getLink('core.identity');
$userId = (int) $this->session->get('auth_user_id', 0);

if (!$identity->userCan($userId, 'slug_da_permissao')) {
    return new Response("Acesso Negado", 403);
}
```

> **Boas Práticas de Arquitetura:** Evite o acesso direto a superglobais (`$_SESSION`, `$_POST`, `$_GET` ou `php://input`) em Controllers. Utilize `SessionManagerInterface` e os métodos de `Request` (`$request->input()`, `$request->json()`, `$request->withJsonBody()`) injetados via construtor.

---

## 🏗️ Arquitetura do Flex Builder (`src/Core/Theme/Parts`)

O construtor de temas **Flex Builder** (`/admin/builder`) separa a compilação no núcleo (`src/Core/Theme/Parts`) da gestão de interface no plugin `flextheme`:

1. **Compilação Unificada (`ThemePartCompilerInterface` e `ThemePartRendererInterface`):**
   - Tanto a pré-visualização no editor quanto a renderização do site compartilham o mesmo motor (`ThemePartCompilerInterface::compilePart()`). Isso garante que as regras de blocos, estilos inline e visibilidade responsiva funcionem com paridade entre editor e visualização final.
2. **Validação Estrutural Pré-Persistência (`ThemePartValidatorInterface`):**
   - Antes de salvar seções e blocos, o sistema valida a estrutura do payload, presets de layout e compatibilidade dos blocos. Em caso de falha em associações, é executado rollback automático das alterações anteriores.
3. **Visibilidade Responsiva (4 Resoluções) e Auto-Reflow:**
   - Ocultar um elemento específico em uma resolução não afeta a seção como um todo. A visibilidade é aplicada ao elemento individual.
   - Quando todos os elementos de uma coluna ficam ocultos em determinada resolução, o renderizador colapsa a coluna vazia e recalcula o grid CSS para as colunas visíveis restantes.
4. **Criação de Novos Blocos (`BlockProviderInterface`):**
   - É possível estender a biblioteca de blocos sem alterar o núcleo, implementando `BlockInterface` e `BlockProviderInterface`:
   ```php
   use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
   use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;

   final class MeuBlocoProvider implements BlockProviderInterface
   {
       public function registerBlocks(BlockRegistryInterface $registry): void
       {
           $registry->register(new MeuBlocoCustomizado());
       }
   }
   ```

---

## 📖 Módulos de Documentação (`ManualModuleInterface`)

Cada plugin pode fornecer sua própria documentação e checklist de testes para exibição no painel administrativo (`/admin/manual`):

1. Crie a classe que implementa `ManualModuleInterface`:
```php
<?php
namespace DomainSystem\Plugins\meu_app\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

class MeuAppManualModule implements ManualModuleInterface
{
    public function getId(): string { return 'meu_app'; }
    public function getTitle(): string { return 'Meu App — Manual'; }
    public function getSubtitle(): string { return 'Guia de operação e checklist de testes.'; }
    public function getBadge(): string { return 'Plugin v2.5'; }
    public function getAccentColor(): string { return '#38bdf8'; }
    public function getAdminUrl(): ?string { return '/admin/meu-app'; }

    public function getChecklistItems(): array
    {
        return [
            ['id' => 'chk_meu_app_1', 'label' => '1. Testar cadastro e validação de dados.'],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return '<p>Instruções de uso e configuração do módulo...</p>';
    }
}
```

2. Retorne a instância em `src/Plugins/meu_app/manual/module.php`:
```php
<?php
require_once __DIR__ . '/MeuAppManualModule.php';
return new \DomainSystem\Plugins\meu_app\manual\MeuAppManualModule();
```

---

## 🧩 Widgets para o Painel Administrativo

Widgets de dashboard utilizam o contrato `DashboardWidgetProviderInterface`:

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
                'title' => 'Vendas do Mês',
                'description' => 'Exibe o total faturado no mês atual'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string {
        if ($widgetId === 'grafico_vendas') {
            return '<div class="widget-box">Conteúdo do widget aqui...</div>';
        }
        return '';
    }
}
```

---

## 🎨 Temas e Renderização

Os temas ficam localizados em `src/Plugins/flextheme/themes/{nome_do_tema}/` (ou `themes/{nome_do_tema}/`).

No arquivo `layouts/main.php`, utilize `ThemePartRendererInterface` para renderizar os cabeçalhos e rodapés customizados criados no Flex Builder, com fallback para os arquivos parciais locais:

```php
<?php
$partRenderer = (isset($GLOBALS['app']) && $GLOBALS['app']->getContainer()->has(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class))
    ? $GLOBALS['app']->getContainer()->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class)
    : null;

$themeFolder = basename($themeDir);
$customHeader = ($partRenderer !== null) ? $partRenderer->renderHeader($themeFolder, get_defined_vars()) : '';

if (trim($customHeader) !== '') {
    echo $customHeader;
} else {
    require $themeDir . '/partials/header.php';
}
?>
```

---

## 🤖 Desenvolvimento Assistido por IA e Auditoria: `project.txt`

O repositório foi estruturado para suportar o desenvolvimento e manutenção utilizando modelos de inteligência artificial ou análise estática de código:

1. **Estrutura Agregada (`project.txt`):**
   - O arquivo `project.txt` na raiz compila a totalidade do projeto, facilitando auditorias globais sem perda de contexto em ferramentas como Claude, ChatGPT, Gemini, etc.
2. **Guia de Contratos (`AI_CONTRACT_GUIDE.md`):**
   - Especificação técnica com diretrizes de arquitetura para agentes e ferramentas de IA gerarem código aderente aos contratos do sistema.
3. **Gerador de Plugins via Terminal:**
   ```bash
   php os-cli.php make:plugin nome_do_plugin
   ```

---

## 🚀 Padrão de Releases e Atualizações (`SystemUpdater`)

Para garantir compatibilidade com o atualizador automático via GitHub Releases:

1. **Migrations Aditivas:** Dentro de uma mesma versão maior (`2.x`), todas as migrações de banco devem ser aditivas (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ... ADD COLUMN`) e utilizar `\PDO` explicitamente se não houver namespace no arquivo de migração.
2. **Contrato de Esquema (`release/schema-expects.json`):** Novas tabelas ou colunas essenciais devem ser registradas para verificação pós-atualização pelo `SchemaVerifier`.
3. **Consistência de Versão:** A versão na tag (`vX.Y.Z`) deve ser idêntica a `DS_VERSION` em `bootstrap.php` e `version` em `composer.json`.
4. **Arquivos Locais Protegidos:** O arquivo `release/exclude.txt` garante que arquivos de ambiente local (`.env`, `config/`, mídias e banco de dados) nunca sejam sobrescritos durante atualizações.
