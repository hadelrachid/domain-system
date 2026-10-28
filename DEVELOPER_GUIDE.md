# Guia do Desenvolvedor: Domain System Web OS

Bem-vindo ao **Ambiente Operacional Web (Web OS)** definitivo. O Domain System deixou de ser um simples CMS para se tornar um Kernel puro (Ring 0) que orquestra Plugins de Nível de Usuário (Ring 3).

Este guia oficial foi atualizado para a versão **2.5** e ensina o "Padrão Ouro" da nossa arquitetura orientada a Contratos (Interfaces).

---

## 🏗️ A Estrutura de um Plugin (Ring 3)

Um Plugin é onde reside a regra de negócios. Ele não pode acessar o Banco de Dados diretamente sem pedir permissão ao SO, e não pode quebrar o sistema se falhar (graças ao Gatekeeper).

Crie uma pasta em `src/Plugins/nome_do_plugin/` contendo:
- `plugin.json`: Metadados.
- `Plugin.php`: O "Motor" do seu aplicativo.
- `manual/module.php` *(Opcional)*: Módulo de instruções e QA rastreado automaticamente pelo Cockpit (`/admin/manual`).

### O Arquivo `plugin.json`
```json
{
    "name": "meu_app",
    "version": "2.5.0",
    "description": "Um aplicativo de exemplo",
    "author": "Sua Empresa",
    "core": false,
    "os_version": "2.5"
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

## 🔒 Segurança, Capabilities (ACL) e Isolamento HTTP (`Request::json()`)

O sistema de permissões abandonou o modelo rígido de "Cargos" por um modelo microscópico de **Capabilities**.
Sempre que seu plugin for instalado, ele deve registrar suas Capabilities no método `activate()`, usando:

```php
$runtime->registerCapability('slug_da_permissao', 'Nome Visível no Painel');
```
O SysAdmin poderá, através da interface do sistema, atrelar essa permissão a qualquer grupo (Admin, Editor, Visitante).
> **Regra de Ouro (SOLID):** Jamais acesse `$_SESSION`, `$_POST`, `$_GET` ou `php://input` diretamente dentro de Controllers! Utilize sempre `SessionManagerInterface` e `Request` (`$request->input()`, `$request->json()`, `$request->withJsonBody()`) injetados via Construtor (validado pelo teste de arquitetura `NoSuperglobalsInControllersTest`).

```php
$identity = $runtime->getLink('core.identity');
$userId = (int) $this->session->get('auth_user_id', 0);

if (!$identity->userCan($userId, 'slug_da_permissao')) {
    return new Response("Acesso Negado!", 403);
}
```

---

## 🏗️ Arquitetura do Flex Builder FSE & Contratos Visuais (`src/Core/Theme/Parts`)

Na **v2.5.0**, o motor visual **Flex Builder** (`/admin/builder`) segue uma separação estrita de responsabilidades entre o **Core (`src/Core/Theme/Parts`)** e o plugin **`flextheme`**:

1. **Caminho Único de Compilação (`ThemePartCompilerInterface` & `ThemePartRendererInterface`):**
   - O `ThemePartRenderer` implementa ambos os contratos. O serviço de preview do Studio (`ThemePartPreviewService`) delega **100%** da compilação para `ThemePartCompilerInterface::compilePart()`, garantindo que regras de compatibilidade de blocos (`getAllowedPartTypes()`), estilos inline (`ElementStyleRendererInterface`), classes de visibilidade responsiva e reajuste do CSS Grid funcionem de maneira idêntica no Preview e na Produção.
2. **Validação Estrutural Pré-Persistência (`ThemePartValidatorInterface`):**
   - Antes de gravar qualquer parte visual, `ThemePartManagementService::saveFromPayload()` executa `ThemePartValidator::validate()`, verificando tipos (`header`, `footer`, `section`), presets (`LayoutGridInterface::getPresets()`), existência e compatibilidade dos blocos no `BlockRegistryInterface`, e limites de profundidade/quantidade. Se uma associação posterior (`setGlobalDefault`, `assignPart`, `assignTemplateToSection`) falhar, o serviço executa **compensação automática** restaurando o estado anterior.
3. **Visibilidade Responsiva por Elemento nas 4 Resoluções + Auto-Reflow de CSS Grid:**
   - A visibilidade responsiva nunca oculta o container da seção inteira (`.ds-section-wrapper`). As flags `hide_desktop`, `hide_laptop`, `hide_tablet` e `hide_mobile` geram as classes `.ds-hide-desktop`, `.ds-hide-laptop`, `.ds-hide-tablet` e `.ds-hide-mobile` exclusivamente no elemento individual (`.ds-element-wrapper`).
   - Quando todos os elementos de uma coluna ficam ocultos em uma resolução, `LayoutGridRenderer` aplica `ds-hide-{resolução}` na coluna vazia (`.ds-grid-col`) e recalcula automaticamente `grid-template-columns` para as colunas visíveis restantes (ex.: `1fr 2fr 1fr` $\rightarrow$ `2fr 1fr` ou `1fr`).
4. **Criando Novos Blocos via Contrato (`BlockProviderInterface` — OCP):**
   - Qualquer plugin pode registrar novos blocos sem alterar o Core implementando `BlockInterface` e `BlockProviderInterface`:
   ```php
   use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
   use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;

   final class MeuPluginBlockProvider implements BlockProviderInterface
   {
       public function registerBlocks(BlockRegistryInterface $registry): void
       {
           $registry->register(new MeuBlocoCustomizado());
       }
   }
   ```

---

## 📖 Auto-Descoberta de Manuais por Contrato (`ManualModuleInterface`)

Qualquer plugin pode fornecer sua própria documentação interativa e checklist de QA que serão carregados **automaticamente** no menu **Manual de Instruções (`/admin/manual` e `/manual`)** do Cockpit.

O motor `PluginManualScanner` varre todos os plugins em `src/Plugins/*/manual/module.php` (e `bundled_plugins/*/manual/module.php`) procurando instâncias que assinem o contrato `DomainSystem\Core\Contracts\ManualModuleInterface`:

### 1. Crie a classe do Manual (`src/Plugins/meu_app/manual/MeuAppManualModule.php`)
```php
<?php
namespace DomainSystem\Plugins\meu_app\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

class MeuAppManualModule implements ManualModuleInterface
{
    public function getId(): string { return 'meu_app'; }
    public function getTitle(): string { return 'Meu App — Guia de Operação'; }
    public function getSubtitle(): string { return 'Resumo rápido do módulo.'; }
    public function getBadge(): string { return 'Plugin v2.5 • SOLID'; }
    public function getAccentColor(): string { return '#38bdf8'; }
    public function getAdminUrl(): ?string { return '/admin/meu-app'; }

    public function getChecklistItems(): array
    {
        return [
            ['id' => 'chk_meu_app_1', 'label' => '1. Testar criação de registro e validação CSRF.'],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return '<p>Instruções detalhadas e interativas do plugin...</p>';
    }
}
```

### 2. Retorne a instância em `src/Plugins/meu_app/manual/module.php`
```php
<?php
require_once __DIR__ . '/MeuAppManualModule.php';
return new \DomainSystem\Plugins\meu_app\manual\MeuAppManualModule();
```
Veja as implementações de referência em `src/Plugins/flextheme/manual/FlexBuilderManualModule.php` e `src/Plugins/academy/manual/AcademyManualModule.php`.

---

## 🧩 Criando Widgets para o Dashboard Profissional

No Painel Administrativo (`/admin`), os Widgets seguem um Contrato estrito (`DashboardWidgetProviderInterface`):

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

---

## 🎨 Como criar Temas Visuais Integrados ao Flex Builder

Os Temas vivem em `src/Plugins/flextheme/themes/{nome_do_tema}/` (ou `themes/{nome_do_tema}/`) e focam exclusivamente na **Renderização Front-end**.
No `layouts/main.php`, integre o `ThemePartRendererInterface` para suportar Headers e Footers dinâmicos do Flex Builder com fallback automático para `partials/header.php` e `partials/footer.php`:

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

## 🏭 Fábrica Automática de Pastas e Aulas PHP (`LessonFileScaffolderInterface`)

No plugin **Academy**, a criação e vinculação de páginas físicas `.php` dentro do tema ativo é governada por dois contratos principais:
1. **`ThemePageResolverInterface`**: Resolve de forma segura (`realpath`) o diretório do tema público ativo e lista páginas disponíveis em `pages/`.
2. **`LessonFileScaffolderInterface`**: Fabrica pastas (`createFolder`) e arquivos `.php` pré-configurados (`fabricateLessonFile`) com CSS/JS responsivos (Desktop, Tablet e Mobile) e estado de blocos embutido (`DS_BLOCKS_B64`).

---

## 🤖 "Vibe Coding sobre Trilhos": Programando com IAs no Domain-System

O Domain-System OS (ocupando apenas **~30 MB** descompactado em servidores Linux) foi desenhado para que você possa usar assistentes de Inteligência Artificial (ChatGPT, Claude, DeepSeek, Gemini, GitHub Copilot, Cursor, Cline, Kilo Code) em velocidade máxima **sem gerar código espaguete**:

1. **Documento Mestre Universal ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md))**:
   - Na raiz do repositório encontra-se o guia canônico para LLMs contendo as **10 Leis Inegociáveis do Ring 3**, árvores de diretórios e templates prontos.
2. **Gerador de Boilerplate SOLID via Terminal (`os-cli.php`)**:
   ```bash
   php os-cli.php make:plugin nome_do_plugin
   ```
3. **Agnosticismo Total do SO (Ring 0)**:
   - O Sistema Operacional (Ring 0) nunca conhece detalhes de SGBDs ou de sistemas operacionais. Toda comunicação ocorre via **Injeção de Dependências e Contratos**, validada por **433 testes automatizados (2.340+ asserções)**.

---

## 🚀 Releases e Migrations (Regras do Atualizador Automático — Modelo A)

Para que o atualizador automático via GitHub Releases (`SystemUpdater` / `CoreUpdater`) opere com segurança em hospedagens compartilhadas (MySQL/SQLite):

1. **Migrations Estritamente Aditivas e Sem `use` Simples no Namespace Global:** Dentro da mesma versão maior (`2.x`), toda migration deve ser **aditiva** (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ADD COLUMN`) e referenciar `\PDO` diretamente caso o arquivo de migration não declare `namespace`.
2. **Atualização Obrigatória do Contrato `release/schema-expects.json`:** Registre novas tabelas/colunas essenciais em `release/schema-expects.json` para que o `SchemaVerifier` valide sua presença após a atualização.
3. **Consistência de Versão (`Tag = DS_VERSION = composer.json`):** Antes de publicar uma tag `vX.Y.Z`, atualize `DS_VERSION` em `bootstrap.php`, `version` em `composer.json` e os `plugin.json` oficiais. O teste `VersionConsistencyTest` e o workflow `.github/workflows/release.yml` bloqueiam pacotes divergentes.
4. **Exclusão de Estado Local (`release/exclude.txt`):** Arquivos de configuração locais (`config/theme_parts/`, `config/active_theme.json`, `.env*`, `project.txt`, `manual/`, `content/`, `resources/`) são filtrados por `release/exclude.txt` para que o pacote oficial passe 100% na política de `CoreProtectedPaths` na Hostinger.
