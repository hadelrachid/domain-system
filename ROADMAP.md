> **ATENÇÃO ARQUITETURAL / PARADIGM SHIFT (2026-10-04)**
> 
> O Domain System **NÃO** é mais um sistema de "SaaS Multi-Tenant". Ele evoluiu para se tornar um **Ambiente Operacional Web (WOE) independente** e puro.
> 
> No passado, o núcleo foi desenhado focado em separar ambientes de SaaS, mas esse acoplamento limitava o projeto. Hoje, o Domain System funciona como um Ambiente Operacional Web (Web Operating Environment), que pode ser instalado em uma máquina ou container.
> Se for desejado que ele preste serviços Multi-Tenant, isso deverá ser feito virtualmente ou através de um aplicativo (Plugin Ring 3) construído especificamente para isso, **sem afetar o Micro-Kernel (Ring 0)**.

# 🗺️ Roteiro de Desenvolvimento — Domain System (v2.5)

Este documento descreve a evolução e visão de futuro para o **Domain System** (~30 MB descompactado em servidores Linux). As prioridades são definidas com base no impacto para o usuário final, na resiliência do sistema e na consolidação de uma **alternativa leve, modular e resiliente** orientada a Contratos (SOLID).

---

## 🟢 Fase 1: Consolidação e Resiliência (Concluída)

*   [x] **No-Break Shield (Circuit Breaker V2 + Limiar por Rota)** — Proteção contra falhas fatais no boot e por rota com painel de telemetria visual.
*   [x] **Persistência Atômica (`AtomicJsonStore`) & Snapshot/Rollback 1-Clique** — Escrita atômica com `LOCK_EX` e pontos de restauração instantâneos do estado do Kernel.
*   [x] **Schema Doctor Agnóstico (`SchemaDoctorInterface`)** — Auto-descoberta e auto-reparo de migrações de plugins 100% desacoplado do SO via contrato.
*   [x] **Dependency Injection Container Puro** — Migração total para injeção de dependências declarativa e eliminação de Service Locators e superglobais em Controllers.
*   [x] **RBAC / Capabilities e Auth Nativo** — Segurança baseada em Capabilities, Anti-CSRF e 2FA.
*   [x] **Instalador Visual (Zero-Friction)** — Setup wizard com auto-destruição para proteção de rotas.

---

## 🟢 Fase 2: Experiência do Desenvolvedor (IA), Academy LMS e Flex Builder FSE (Concluída na v2.5)

### 2.1. Flex Builder FSE (Full Site Editing, 14 Blocos & Grid Responsivo com Auto-Reflow)
- [x] **Motor Visual FSE em Contratos (`src/Core/Theme/Parts`):** Composição visual de Headers, Footers e Seções Centrais com 14 blocos semânticos nativos e extensibilidade aberta via `BlockProviderInterface` (OCP).
- [x] **Compilador Único (`ThemePartCompilerInterface`) & Validador Estrutural (`ThemePartValidatorInterface`):** Paridade absoluta entre o Preview do Studio e a publicação no Frontend, com validação pré-gravação e compensação transacional nas associações.
- [x] **Visibilidade Responsiva por Elemento nas 4 Resoluções + Auto-Reflow de CSS Grid:** Ocultação individual de elementos em `Desktop (100%)`, `Laptop (1024px)`, `Tablet (768px)` e `Mobile (375px)` sem jamais ocultar o container da seção inteira, com recálculo automático de `grid-template-columns` quando colunas ficam ocultas.
- [x] **Vinculação Direta de Seções a Templates & Shortcode `[section id="..."]`:** Atribuição visual de seções centrais a qualquer template do tema (`home.php`, `sobre.php`, etc.) e proteção anti-recursão.

### 2.2. Construtor Visual em Blocos & Fábrica Automática de Páginas PHP (Academy LMS)
- [x] **Editor Visual em 8 Blocos + JSON Estruturado:** Editor completo (`header`, `paragraph`, `video`, `code`, `callout`, `list`, `image`, `delimiter`) com sanitização server-side Anti-XSS.
- [x] **Fábrica Automática de Pastas e Aulas PHP (`LessonFileScaffolderInterface`):** Botão de criar pasta/módulo e geração automática de arquivos `.php` pré-configurados (Desktop/Mobile) a partir do título da aula (ex: *"Estrutura de Dados"* $\rightarrow$ `pages/estrutura-de-dados.php` $\rightarrow$ `/cursos/estrutura-de-dados`).
- [x] **Simulador Multi-Dispositivos & 3 Perfis Mobile:** Preview em tempo real em 5 resoluções mobile/tablet e 3 perfis de layout (`drawer`, `bottom_bar`, `cinema` 16:9 sticky).

### 2.3. Ferramentas para Criação Assistida por IA ("Vibe Coding sobre Trilhos")
- [x] **Guia Mestre de Contratos para IAs (`AI_CONTRACT_GUIDE.md`):** Regras oficiais para ChatGPT, Claude, DeepSeek, Gemini, GitHub Copilot (`.github/copilot-instructions.md`), Cursor (`.cursorrules`) e Cline/Kilo (`.clinerules`) gerarem código Ring 3 sem espaguete.
- [x] **Scaffolding Completo via OS-CLI (`make:plugin`):** Geração automática de `Plugin.php`, `Contracts/`, `Controllers/`, `views/` e `manual/module.php`.
- [x] **Auto-Descoberta de Manuais (`ManualModuleInterface`):** Módulos `manual/module.php` carregados dinamicamente em `/admin/manual` e `/manual`.
- [ ] **Editor de Plugins Code-in-Browser:** Mini IDE integrada no painel do OS.

### 2.4. Dashboard Inteligente e Modular & View Engine
- [x] **Telemetria Centralizada em Tempo Real:** Gráficos ao vivo de consumo/liberação de memória Zend, picos de requisições e PIDs do Kernel Singleton em `/admin/monitor`.
- [x] **Interfaces de Resposta (`ResponseInterface`, `ViewResponse`, `JsonResponse`) e `Request::json()`:** Padronização das entradas e saídas HTTP nos Controllers com encapsulamento automático pelo `WorkspaceManager`.
- [ ] **Widgets Drag-and-Drop Persistentes por Usuário:** Personalização livre da ordem dos cards na tela inicial.

---

## 🔵 Fase 3: Escalabilidade e Virtualização de Serviços (3 a 6 Meses)

### 3.1. Arquitetura de Subdomínios (Virtual Instance Engine)
- [ ] **Roteador Avançado:** Consolidar a leitura dinâmica do `tenants.json` no boot do sistema para separar o banco de dados antes que qualquer plugin inicie.
- [ ] **Tenant Provisioner Plugin:** Um plugin oficial que permite criar um novo ambiente (banco de dados + subdomínio) com 1 clique a partir de um painel Super Admin.
- [ ] **Gestão de Assinaturas (Stripe):** Bloqueio ou liberação de tenants baseado no status da fatura do cliente.

### 3.2. Abstração Total de Banco de Dados & Atualizador Automático por Contrato
- [x] **Schema Builder:** Em vez de arquivos `.sql` ou queries hardcoded, os plugins usarão uma classe `Schema` para criar tabelas que funcione tanto no MySQL (Produção Hostinger) quanto no SQLite (Testes).
- [x] **Atualizador Automático via GitHub Releases (`SystemUpdater` / `CoreUpdater` — Modelo A):** Verificação semântica com ETag, pacote assinado SHA-256 + `manifest.json`, pré-checagem, backup de código/banco (`SqliteDatabaseBackup`/`MysqlDatabaseBackup`), migrações aditivas verificadas por contrato (`SchemaPlanner`/`SchemaVerifier`), health-check em duas fases e rollback automático.
- [ ] **Atualização Individual de Plugins (Milestone M3):** Suporte à atualização seletiva de plugins do Ring 3 a partir de releases independentes mantendo compatibilidade de contrato (`os_version`).

### 3.3. API Pública (REST & Webhooks)
- [ ] **Central de Webhooks (Event Gateway):** Um ponto único e seguro onde serviços externos disparam eventos no OS.
- [ ] **Autenticação OAuth2:** Permitir que aplicativos móveis ou terceiros consigam gerar chaves API para acessar os dados do SO.

---

## 🟣 Fase 4: A Grande Interface (Iniciada)

### 4.1. CockPit OS 2.5 (Interface do Usuário & Skin Engine)
- [x] **Design System Unificado & Design Tokens Globais:** Contrato visual oficial e editor de Design Tokens (`/admin/builder/tokens`) gerando variáveis CSS globais (`--color-*`, `--font-*`).
- [x] **Skin Engine Cyberpunk Dinâmica:** Sistema baseado em arquivos `.json` e variáveis CSS que permitem injetar temas dark/light sem relar no PHP.
- [ ] **Painel Totalmente SPA:** Transição de abas e menus via "Live Fetch" AJAX, acabando com a sensação de recarregamento (F5) ao transitar nas configurações.

### 4.2. Performance e Otimização de SEO
- [x] **Otimização de Assets (SEO Engine):** Minificação e compressão de CSS/JS e HTML via `SeoMiddleware` e `HtmlOptimizerInterface`.
- [ ] **Auto-Fixer de Velocidade Baseado em IA:** Integração para o Kernel propor ou gerar automaticamente correções de performance caso a nota da página comece a cair.

---

## ⚙️ Dívida Técnica (Refatoração Contínua)

| Item | Prioridade | Status |
|------|------------|--------|
| Implementar Padronização Completa de Erros (Dicionário de Códigos) | P0 (Crítico) | 🟢 Concluído |
| Expurgo Absoluto de Dependências de Negócio (Desacoplamento para Web Platform) | P0 (Crítico) | 🟢 Concluído |
| Compilador Único (`ThemePartCompilerInterface`) e Validador (`ThemePartValidatorInterface`) no Flex Builder | P0 (Crítico) | 🟢 Concluído |
| Decompor `ErrorHandler` nativo e mesclar com o No-Break Shield | P1 (Alta) | 🟡 Em Progresso |
| Migrar Queries Manuais para um QueryBuilder unificado | P2 (Média) | 🕒 Pendente |

---

## 📊 Critérios de Sucesso

- **Agnóstico Total**: O Kernel não deve ter dependências que deduzam que negócio está rodando nele.
- **Zero downtime** durante falhas de código ou ativação de plugins.
- **Tempo de resposta** < 150ms para renderização de páginas front-end públicas.
- **Ecossistema:** Ter a mesma facilidade de criar temas, partes visuais e extensões que o WordPress oferece aos seus desenvolvedores, mantendo 100% de rigor SOLID.
