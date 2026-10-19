# 🛡️ Plano de Melhoria: Estabilidade, Resiliência e Evolução do Cockpit (v2.4.0)

> **Status Atual:** Planejado (Pós-Lançamento `v2.3.0`)  
> **Base Atual:** `Domain-System v2.3.0` (`180 testes`, `713 asserções` passando no PHPUnit)  
> **Objetivo:** Elevar o **Domain-System Cockpit** a um nível de resiliência industrial (Zero Corrupção de Estado, Auto-Cura de Banco de Dados, Tolerância a Falhas por Threshold, Desacoplamento Total de Views e Auto-Descoberta Universal de Migrations e Manuais).

---

## 📊 1. Diagnóstico do Sistema Atual (`v2.3.0`)

Com a entrega da versão `2.3.0`, o **Domain-System** consolidou pilares arquiteturais fundamentais:
1. **Isolamento Ring 0 / Ring 3:** O Micro-Kernel (`Application`, `HttpKernel`, `CoreServiceProvider`) é 100% agnóstico das regras de negócio.
2. **Guardrails Arquiteturais Automatizados (`tests/Architecture/`):**
   - `Psr4CaseSensitivityTest`: Garante compatibilidade estrita de namespaces e diretórios entre Windows e Linux.
   - `NoSuperglobalsInControllersTest`: Impede qualquer uso direto de `$_SESSION`, `$_POST` ou `$_GET` nos Controllers.
   - `NoExitOrHeaderTest`: Garante que todo fluxo HTTP retorne objetos `Response` puros.
   - `RingIsolationTest`: Impede que plugins do Ring 3 instanciem ou violem classes exclusivas do Ring 0.
3. **Padrão Ouro com o Plugin Academy (`v2.3.0`):**
   - Editor Híbrido (HTML/PHP do Tema + Editor em Blocos com Preview em Tempo Real e Simulador Multi-Dispositivos para Desktop, 3 Celulares e 2 Tablets).
4. **Auto-Descoberta de Manuais por Contrato (`ManualModuleInterface`):**
   - O `PluginManualScanner` varre automaticamente `src/Plugins/*/manual/module.php` e acopla tutoriais e itens de QA ao menu **Manual de Instruções (`/admin/manual`)** do Cockpit.

---

## 🚀 2. Os 6 Pilares do Próximo Ciclo (`v2.4.0`)

### Pilar 1: Escrita Atômica e Concorrência Segura (`AtomicJsonStore`)
- **Problema Identificado:** Arquivos críticos de estado do sistema (`config/plugins.json`, `temp/disarmed.json`, `temp/error_logs.json`, `temp/metrics.json`) utilizam `file_put_contents()` direto. Sob requisições simultâneas (ex: *polling* de telemetria em tempo real concorrendo com salvamento de configurações), existe risco de *race condition* ou leitura de JSON truncado.
- **Solução Arquitetural (`src/Core/Storage/AtomicJsonStore.php`):**
  1. Implementar contrato `JsonStoreInterface` e classe `AtomicJsonStore`.
  2. Gravar sempre em arquivo temporário no mesmo diretório (`arquivo.json.tmp.{pid}`) com bloqueio exclusivo (`LOCK_EX` e `fflush()`).
  3. Executar `rename()` atômico para o arquivo destino, garantindo que nenhuma leitura concorrente enxergue um arquivo incompleto.
  4. Injetar `AtomicJsonStore` em `CircuitBreaker`, `ErrorLogger`, `RequestMetricsRecorder` e `PluginConfigManager`.

---

### Pilar 2: Circuit Breaker Inteligente em Tempo de Execução (Threshold & Isolamento por Rota)
- **Problema Identificado:** O `PluginBootstrapper` protege a fase de inicialização (`osRegister` e `osBoot`), e o `ErrorHandler` intercepta erros fatais globais no `shutdown`. Porém, quando uma exceção ocorre durante a execução de um Controller específico no `Router`, um único erro transitório não deve derrubar o plugin se for isolado, mas erros repetitivos em uma rota precisam ser rastreados e contidos.
- **Solução Arquitetural:**
  1. **Rastreamento de Origem por Namespace no `HttpKernel`:** Identificar automaticamente qual plugin (`DomainSystem\Plugins\{slug}`) originou a exceção durante o despacho da rota.
  2. **Política de Threshold (Limite de Tolerância):** Registrar falhas consecutivas por plugin em janela de tempo (ex: 3 falhas fatais/exceções não tratadas em 60 segundos desarmam o plugin automaticamente no `CircuitBreaker`, enquanto 1 falha isolada apenas registra o incidente no Painel de Supervisão e exibe tela de erro amigável).
  3. **Health-Check Pré-Ativação no Cockpit:** Antes de religar um plugin desarmado em `/admin/supervision`, rodar um *Dry-Run (Smoke Test)* em sandbox para validar sintaxe e dependências antes de persistir a reativação.

---

### Pilar 3: Auto-Descoberta de Migrations & "Schema Doctor" (Auto-Cura do Banco)
- **Problema Identificado:** Enquanto o plugin `academy` já utiliza `src/Plugins/academy/migrations/` com o `MigrationRunner`, plugins anteriores (`pages`, `settings`, `nav_menus`, `auth`) ainda possuem `CREATE TABLE IF NOT EXISTS` espalhados em métodos `activate()` ou repositórios.
- **Solução Arquitetural:**
  1. **Padronização Universal da Pasta `migrations/`:** Assim como fizemos com a pasta `manual/`, todo `SystemApp` e `Plugin` terá sua pasta `migrations/` escaneada automaticamente pelo `MigrationRunner`.
  2. **Painel "Schema Doctor" no Cockpit (`/admin/monitor` ou `/admin/supervision`):**
     - Verifica em tempo real se há migrations pendentes em qualquer plugin ativo.
     - Botão **"🔧 Verificar e Reparar Estrutura do Banco (Auto-Heal)"** que executa migrations pendentes com transação segura e exibe o relatório de integridade das tabelas.

---

### Pilar 4: Desacoplamento Total de Views no Plugin Academy & Novos Perfis Mobile
- **Problema Identificado:** Os Controllers `LessonAdminController`, `CourseAdminController` e `CourseFrontController` possuem lógica de negócio limpa, mas ainda concentram blocos extensos de HTML/CSS/JS inline.
- **Solução Arquitetural:**
  1. **Extração para Camada de Views (`src/Plugins/academy/views/`):**
     - `views/admin/course_list.php`
     - `views/admin/course_edit.php`
     - `views/admin/lesson_editor.php`
     - `views/front/course_show.php`
     - `views/front/lesson_show.php`
  2. **Perfis de Layout Mobile Selecionáveis (`Mobile Layout Profiles`):**
     - Permitir escolher o perfil visual mobile do curso/aula no Editor:
       - **Perfil 1 — Drawer Clássico (Off-Canvas):** Menu hambúrguer + gaveta lateral deslizante (padrão atual).
       - **Perfil 2 — App Bar Inferior (Bottom Sheet Estilo App Nativo):** Barra fixa no rodapé do celular com botões rápidos (*Anterior*, *Índice em Bottom-Sheet*, *Modo Leitura Focado*, *Próxima*).
       - **Perfil 3 — Modo Cinema / Vídeo-First:** Otimizado para cursos em vídeo, mantendo o player 16:9 fixo (*sticky*) no topo da tela do celular enquanto o aluno rola o texto e os blocos de código abaixo.

---

### Pilar 5: Fábrica Automática de Pastas e Arquivos PHP de Aulas via Contrato (`LessonFileScaffolderInterface`)
- **Problema Identificado:** Hoje, para usar o modo `📄 Arquivo PHP do Tema`, o desenvolvedor/administrador precisa criar manualmente a pasta e os arquivos `.php` no diretório do tema ativo (`themes/.../pages/`) antes de importá-los ou vinculá-los no Academy.
- **Solução Arquitetural (`src/Plugins/academy/Contracts/LessonFileScaffolderInterface.php`):**
  1. **Contrato de Fabricação (`LessonFileScaffolderInterface` & `ThemeLessonFileScaffolder`):**
     - Permite que o próprio painel do **Academy** fabrique automaticamente as pastas (módulos/cursos) e os arquivos `.php` das aulas diretamente dentro da pasta `pages/` do tema ativo, com validação estrita de segurança (`realpath` e sanitização de slug para impedir *Path Traversal*).
  2. **Arquivos `.php` Pré-Configurados (Boilerplate Responsivo Desktop & Mobile + Edição em Blocos):**
     - Cada arquivo `.php` fabricado já nasce com a estrutura padrão pré-configurada (CSS e JS responsivos para as resoluções **Desktop**, **Mobile** e **Tablet**, cabeçalho/rodapé integrados e marcação compatível com o **Editor de Blocos**).
     - Quando o usuário editar a aula pelo **Editor de Blocos** e salvar, o serviço sincroniza/atualiza automaticamente tanto o JSON estruturado no banco quanto o arquivo `.php` pré-configurado no disco!
  3. **Fluxo no Painel (`Botão Criar Pasta / Módulo` + `Auto-Geração de Arquivo ao Salvar Aula`):**
     - **Botão "📁 Criar Pasta (ou Módulo)":** Cria fisicamente o diretório da pasta/módulo em `pages/` (caso o usuário deseje agrupar em subpasta) ou permite usar a raiz `pages/`.
     - **Geração Automática pelo Título:** Quando o usuário digitar o título da aula ou página — por exemplo, **"Estrutura de Dados"** — e clicar em salvar:
       - O sistema converte automaticamente o título em slug (`estrutura-de-dados`),
       - Fabrica o arquivo físico `estrutura-de-dados.php`,
       - Registra o caminho relativo `pages/estrutura-de-dados.php` (ou `pages/{pasta}/estrutura-de-dados.php`),
       - E disponibiliza imediatamente a rota amigável pública (ex: `http://localhost/domain-system/cursos/estrutura-de-dados` ou `http://localhost/domain-system/cursos/{curso}/estrutura-de-dados`).

---

### Pilar 6: Snapshot & Rollback Instantâneo no Cockpit + Expansão dos Manuais Auto-Descobertos
- **Snapshot de Segurança Automático:**
  - Antes de operações críticas no Cockpit (instalação/remoção de plugin ZIP, ativação em massa, execução de migrations), gerar um snapshot automático em `temp/snapshots/` (`plugins.json` + backup compacto do estado).
  - Adicionar botão **"⏪ Restaurar Último Ponto Estável"** no Painel de Supervisão (`/admin/supervision`).
- **Manuais Auto-Descobertos (`ManualModuleInterface`) para os Demais Módulos:**
  - Criar `src/Plugins/flextheme/manual/module.php` (Guia do Builder Flex & Temas Visuais).
  - Criar `src/Plugins/pages/manual/module.php` (Guia de Páginas, Hierarquia e Shortcodes).
  - Criar `src/Plugins/seo/manual/module.php` (Guia de SEO, Meta Tags e Indexação).

---

## 🔍 3. Consolidação das Auditorias Multi-IA (`auditoria/ChatGPT.txt`, `DeepSeek.txt` e `Kilo.md`)

As auditorias independentes realizadas pelo **ChatGPT**, **DeepSeek** e **Kilo Code (NVIDIA)** convergiram exatamente com o nosso diagnóstico e trouxeram apontamentos cirúrgicos linha-a-linha para elevar o plugin **Academy** de **7/10 para 9.5+/10 (Padrão Ouro Definitivo)**:

### 🔴 P0 — Correções Cirúrgicas Imediatas (Zero Efeitos Colaterais e DI Pura)
1. **`LessonAdminController` (Remoção de Dependências Mortas):**
   - Remover `?ThemeManagerInterface $themeManager` e `?LessonBlockRenderer $blockRenderer` do construtor de `LessonAdminController`, pois não são utilizados no corpo da classe, e eliminar o anti-pattern `?? new LessonBlockRenderer()`.
2. **`CourseFrontController::resolveActiveThemePath()` (Fim de `new` Interno no Controller):**
   - Remover a instanciação direta `new SafeThemeResolver(...)` e `new EnvSiteState()` de dentro do `CourseFrontController`. Injetar a resolução via Container/Contrato (`ThemeManagerInterface`).
3. **Fonte Única de Verdade para o Schema (Migrations vs `ensureTableExists`):**
   - Eliminar o *side-effect* `ensureTableExists()` / `ensureTables()` de dentro dos construtores de `PdoCourseRepository` e `PdoCourseStructureRepository`.
   - Consolidar toda a criação de tabelas (`academy_courses`, `academy_modules`, `academy_lessons`) exclusivamente via `migrations/` + `MigrationRunner`.
4. **Blindagem Defensiva de Escaping (`CourseAdminController`):**
   - Aplicar `htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8')` nas saídas de `$this->storage->getPublicUrl(...)` (linhas 96 e 306) e substituir `onsubmit` com `addslashes()` por atributos `data-confirm-title` + listener JS limpo.

### 🟠 P1 — Desacoplamento de Views e Contratos de Renderização
5. **Extração Completa das Views Inline (`src/Plugins/academy/views/`):**
   - Mover todo o HTML/CSS/JS inline de `CourseAdminController` (`index`, `edit`), `LessonAdminController` (`editor`) e `CourseFrontController` (`index`, `showCourse`, `showLesson`) para arquivos de template dedicados em `src/Plugins/academy/views/`.
6. **Contratos para Renderizadores e Storage Obrigatório:**
   - Criar `CourseCatalogRendererInterface` e `LessonBlockRendererInterface` para injeção por contrato no `CourseFrontController`.
   - Tornar `PublicStorageInterface` obrigatório no construtor de `CourseCatalogRenderer` e `LocalCourseImageStorage`.

### 🟡 P2 — Alta Coesão e Segregação de Interfaces (ISP & SRP)
7. **Extração do Sincronizador de Estrutura (`CourseStructureSynchronizer`):**
   - Retirar a lógica pesada de diff/merge (`syncCourseStructure`) de dentro de `PdoCourseStructureRepository` para um serviço de domínio dedicado (`CourseStructureSynchronizer`), mantendo o repositório focado apenas em persistência.
8. **Extração do Resolvedor de Páginas do Tema (`ThemePageResolver`):**
   - Mover `resolvePublicThemePages()` do `CourseAdminController` para um serviço dedicado injetado via contrato.
9. **Separação de Controllers Administrativos:**
   - Dividir `CourseAdminController` em `CourseAdminController` (CRUD do curso) e `CourseStructureAdminController` (gestão de módulos, aulas e auto-importação/fabricação de pastas).

---

## ✅ 4. Checklist de Execução (Roadmap `v2.4.0`)

- [ ] **Etapa 1 (P0 Academy):** Limpar dependências mortas de `LessonAdminController`, remover `new SafeThemeResolver`/`EnvSiteState` de `CourseFrontController`, aplicar `htmlspecialchars($thumbSrc)` e unificar o schema exclusivamente nas `migrations/` (removendo `ensureTableExists` dos construtores).
- [ ] **Etapa 2 (P1 Academy Views & Contratos):** Extrair todas as views inline do `academy` para `src/Plugins/academy/views/`, criar `CourseCatalogRendererInterface` / `LessonBlockRendererInterface`, `CourseStructureSynchronizer` e `ThemePageResolver`, e adicionar os novos perfis de layout Mobile (*Bottom Sheet* e *Modo Cinema Sticky Video*).
- [ ] **Etapa 3 (Pilar 5 — Fábrica de Aulas PHP):** Implementar o contrato `LessonFileScaffolderInterface` (`ThemeLessonFileScaffolder`) no plugin `academy` com o botão **"📁 Criar Pasta/Módulo"** e a fabricação automática de arquivos `.php` pré-configurados (Desktop/Mobile + Editor de Blocos) a partir do Título (ex: `"Estrutura de Dados"` $\rightarrow$ `pages/estrutura-de-dados.php`).
- [ ] **Etapa 4 (Pilar 1 — Core Storage):** Implementar `JsonStoreInterface` + `AtomicJsonStore` com testes de concorrência em `tests/Unit/AtomicJsonStoreTest.php` e integrá-lo no `CircuitBreaker`, `ErrorLogger`, `RequestMetricsRecorder` e configuração de plugins.
- [ ] **Etapa 5 (Pilar 2 — Core Resiliência):** Implementar rastreamento de exceções de rota por plugin e política de *Threshold* no `HttpKernel` / `CircuitBreaker`.
- [ ] **Etapa 6 (Pilar 3 — Schema Doctor):** Criar o scanner automático de `migrations/` para todos os plugins e adicionar o diagnóstico **Schema Doctor** no Cockpit.
- [ ] **Etapa 7 (Pilar 6 — Manuais & Snapshot):** Criar os módulos `manual/module.php` (`ManualModuleInterface`) para `flextheme`, `pages` e `seo`, implementar o sistema de **Snapshot & Rollback Rápido** no Painel de Supervisão e publicar a versão **`v2.4.0`**.
