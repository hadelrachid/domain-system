> 🇧🇷 [Ler em Português](#-bem-vindo-ao-domain-system-cockpit-) | 🇺🇸 [Read in English](#-welcome-to-domain-system-cockpit-)

---

# 🇧🇷 Bem-vindo ao Domain-System (Cockpit) 🚀

O **Domain-System** não é apenas um sistema de sistema. Ele é um autêntico **Ambiente Operacional Web** (CMS / Framework Universal). O "pacote de sistema médica" é apenas um exemplo do que ele pode rodar. É uma plataforma **Event-Driven (Orientada a Eventos)** altamente modular, resiliente e desenhada sob os rigorosos princípios da arquitetura **SOLID**. 

Inspirado nas engrenagens de grandes ecossistemas (como o WordPress e sistemas operacionais de missão crítica), o projeto foi concebido para ser altamente escalável, permitindo que componentes sejam conectados ou desconectados em tempo real sem afetar o núcleo do sistema.

---

## 🏛️ Arquitetura em Camadas

A arquitetura do Domain-System é dividida em três pilares principais:

### 1. O Kernel (SystemAdmin) e o Event Dispatcher
No coração do sistema, não existe regra de negócio (usuários, Prontuários, Finanças). O Kernel atua puramente como um **Ambiente Operacional**. Sua principal ferramenta é o **Event Dispatcher** (Despachante de Eventos).
- O Kernel não chama os módulos. Ele apenas "grita" eventos no sistema (ex: `router.register`, `admin.menu`, `appointment.created`).
- Os módulos (Plugins), que estão "escutando" essas frequências, reagem e injetam seus dados no Kernel. Isso garante **desacoplamento absoluto**.

### 2. O Ecossistema de Plugins (Módulos)
Cada funcionalidade do sistema (Triagem, WhatsApp, Prontuários, Jurídico) é um **Plugin**. 
Os plugins são independentes, injetam suas próprias rotas, menus e escutam eventos do Kernel.
- **Injeção de Dependências (DIP):** Os módulos usam Containers e Contratos (Interfaces) para realizar o "trabalho sujo" com o banco de dados via Padrão Repository (Ex: `WhatsAppProviderInterface`). O controlador nunca toca no banco de dados diretamente.

### 3. A Régua de Extensão (Plugin Pack) e o Academy LMS Híbrido (Fábrica de `.php` & 3 Perfis Mobile)
Pensando na escalabilidade ilimitada (com todo o ecossistema descompactado ocupando apenas **~30MB** em servidores Linux), o sistema adotou a filosofia da "Régua de Extensão" (Hub). Em vez de entupir o Menu Principal do Kernel com dezenas de botões, o sistema prevê "Pacotes" (como o `clinic_pack`) e Hubs educacionais como o **Academy LMS (`academy`)**, que traz:
- **Fábrica Automática de Pastas e Aulas `.php` (`LessonFileScaffolderInterface`):** Cria subpastas de módulos e fabrica automaticamente arquivos `.php` físicos no tema ativo (ex: `themes/<tema>/pages/estrutura-de-dados.php`) acessíveis em `/cursos/estrutura-de-dados`.
- **4 Modos de Aula & 3 Perfis Mobile (`drawer`, `bottom_bar`, `cinema`):** Edição híbrida de aulas (HTML/Markdown puro, Editor em Blocos com 8 blocos, Aulas `.php` fabricadas com código ou blocos, e Páginas do Tema) com Preview Dinâmico e Simulador Multi-Dispositivos (5 viewports).

### 4. Auto-Descoberta de Manuais (`ManualModuleInterface`), Atualizador GitHub (`SystemUpdater`) e "Vibe Coding nos Trilhos" (`AI_CONTRACT_GUIDE.md`)
Na **v2.5.0**, o Cockpit possui:
- **Atualizador Automático via GitHub Releases (`SystemUpdater` / `CoreUpdater` — Modelo A):** Notificações de nova versão, validação dupla SHA-256 + manifesto, backup automático de código e banco (MySQL/SQLite) e auto-rollback em 1 clique ou via CLI (`php os-cli.php system:update`).
- **Rastreador Automático de Manuais (`PluginManualScanner`):** Varre `src/Plugins/*/manual/module.php` (incluindo `academy`, `flextheme`, `pages`, `seo`, `SystemUpdater`) e acopla automaticamente seus guias e checklists ao **Manual de Instruções (`/admin/manual`)**.
- **Guia Mestre de Contratos para IA (`AI_CONTRACT_GUIDE.md`):** Regras nativas (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`) e gerador CLI (`php os-cli.php make:plugin <slug>`) para que qualquer IA programe em alta velocidade (*Vibe Coding*) respeitando 100% os contratos do Ring 3.

---

## 🛡️ Resiliência e Segurança (Missão Crítica)

Como um sistema corporativo lida com dados críticos, implementamos ferramentas de proteção em nível de engenharia avançada:

### O Disjuntor (Circuit Breaker com Threshold de Rota & AtomicJsonStore)
Se um plugin sofre um erro fatal (Ex: estouro de memória ou dependência corrompida), o sistema não exibe tela branca. O **Disjuntor** entra em ação, captura o *Fatal Error*, isola apenas a rota falha (só ejetando o plugin inteiro após atingir o threshold de falhas consecutivas), grava o estado atomicamente via `AtomicJsonStore` (`flock` + `rename`) e permite que o resto da plataforma continue funcionando perfeitamente.
- **Efeito Cascata:** Se o módulo "usuários" depender do módulo "Auth", e o "Auth" cair, o disjuntor entende a árvore de dependências e ejeta o "usuários" em cascata, salvando o boot do servidor.
- **Schema Doctor (`SchemaDoctorInterface`) & Snapshot 1-Clique:** O Kernel permanece 100% agnóstico a bancos de dados (`PDO`/SQLite/MySQL) e sistemas operacionais (Windows/Linux/macOS), delegando diagnóstico/auto-reparo de tabelas e pontos de restauração instantâneos a contratos injetados.

### A Escotilha de Emergência (Emergency Hatch)
Caso uma falha de energia ocorra no núcleo de segurança (`Auth`), ativando o Disjuntor, os administradores não ficam trancados para fora. O Kernel ativa a **Rota de Fuga**. Ao acessar a URL, uma tela de terminal verde de emergência se abre, solicitando a `APP_KEY` do servidor, permitindo acesso provisório (Modo de Segurança) para o administrador religar os disjuntores.

### O Porteiro Global (ACL Centralizado & Capability Security)
Para manter os princípios **SOLID** (Single Responsibility Principle) e evitar código espaguete, os Controladores do sistema não verificam senhas nem acessam superglobais (`$_SESSION` é proibido em Controllers). A segurança é delegada ao **Router (Roteador)** e ao **IdentityManager**:
- Você declara de forma elegante na rota: `['admin', 'doctor']` ou valida *Capabilities* granulares via `SessionManagerInterface`.
- Se um usuário sem permissão tentar acessar, o Roteador cria uma barreira (Gatekeeper) e ejeta a requisição antes mesmo do Controlador entrar em ação.

---

## 📖 Como navegar (Links e Documentação Oficial)
A documentação principal do sistema evoluiu e agora está centralizada em nossa pasta `docs/` e no guia oficial para IA. Recomendamos fortemente a leitura da arquitetura **OS 2.5.0**:

- 🔄 **[Sistema de Atualizações Automáticas](Atualizacoes.md)** - O fluxo de atualização contínua via GitHub Releases.
- 🤖 **[AI Contract Guide (`AI_CONTRACT_GUIDE.md`)](../AI_CONTRACT_GUIDE.md)** - O Guia Mestre para IAs (*Vibe Coding on Rails*).
- 🚀 **[Guia Interativo de Extensões (Temas e Plugins)](../docs/tutorial-dev.html)** - O padrão de Injeção de Dependências e `ManualModuleInterface`.
- 📜 **[Manifesto do Contrato do Sistema](../docs/01-o-contrato-do-sistema.html)** - Uma alternativa leve e resiliente baseada em Contratos e Injeção de Dependências.
- 🤖 **[O Ciclo de Confiança Verificável (IA)](../docs/architecture/01-ciclo-de-confianca-verificavel.html)** - Como preparamos o terreno para a Inteligência Artificial.
- ⚡ **[O Disjuntor (Circuit Breaker)](Disjuntor-Circuit-Breaker.md)**
- 🚪 **[A Escotilha de Emergência](Emergency-Hatch.md)**
- 🛑 **[O Porteiro Global (ACL)](Global-Gatekeeper.md)**

---

# 🇺🇸 Welcome to Domain-System (Cockpit) 🚀

The **Domain-System** is not just a clinic management system. It is a genuine **Web Operating Environment** (Universal CMS / Framework). The "clinic pack" and "Academy LMS" are examples of what it can run. It is a highly modular, resilient, **Event-Driven** platform designed under strict **SOLID** architecture principles.

Inspired by the gears of large ecosystems (such as WordPress and mission-critical operating systems), the project was designed to be highly scalable, allowing components to be plugged in or unplugged in real-time without affecting the system's core.

---

## 🏛️ Layered Architecture

The architecture of the Domain-System is divided into four main pillars:

### 1. The Kernel (SystemAdmin) and Event Dispatcher
At the heart of the system, there are no business rules (Patients, Medical Records, Courses, Finances). The Kernel acts purely as an **Operating Environment**. Its main tool is the **Event Dispatcher**.
- The Kernel does not directly call modules. It simply "shouts" events into the system (e.g., `router.register`, `admin.menu`, `appointment.created`).
- The modules (Plugins), which are "listening" to these frequencies, react and inject their data into the Kernel. This ensures **absolute decoupling**.

### 2. The Plugin Ecosystem (Modules)
Every system feature (Academy LMS, Triage, WhatsApp, Medical Records) is a **Plugin**.
Plugins are independent—they inject their own routes, menus, and listen to Kernel events.
- **Dependency Injection (DIP):** The modules use Containers and Contracts (Interfaces) to do the "dirty work" with the database via the Repository Pattern (e.g., `CourseRepositoryInterface`). The controller never touches the database or PHP superglobals directly.

### 3. The Extension Cord (Plugin Pack) & Hybrid Academy LMS (`.php` Factory & 3 Mobile Profiles)
Thinking about unlimited scalability (with the entire unpacked folder occupying only **~30MB** on Linux servers), the system adopted the "Extension Cord" (Hub) philosophy. Instead of cluttering the Kernel's Main Menu with dozens of buttons, the system supports "Packs" (like `clinic_pack`) and full-featured hubs like **Academy LMS (`academy`)**, featuring:
- **Automatic Folder & `.php` Lesson Factory (`LessonFileScaffolderInterface`):** Automatically creates module folders and physical `.php` lesson files inside the active theme (`themes/<theme>/pages/estrutura-de-dados.php`) routed at `/cursos/estrutura-de-dados`.
- **4 Lesson Modes & 3 Mobile Layout Profiles (`drawer`, `bottom_bar`, `cinema`):** Hybrid lesson authoring (Raw HTML/Markdown, 8-block Visual Editor, Fabricated `.php` Lessons with code or blocks, and Theme Pages) with Real-Time Preview and a 5-Viewport Multi-Device Simulator.

### 4. Contract-Driven Manual Auto-Discovery (`ManualModuleInterface`), Self-Updater (`SystemUpdater`) & "Vibe Coding on Rails" (`AI_CONTRACT_GUIDE.md`)
In **v2.5.0**, the Cockpit includes:
- **Automatic GitHub Releases Self-Updater (`SystemUpdater` / `CoreUpdater` — Model A):** New version alerts, dual SHA-256 + manifest verification, automatic code & database backup (MySQL/SQLite), and 1-click or CLI auto-rollback (`php os-cli.php system:update`).
- **Automatic Manual Scanner (`PluginManualScanner`):** Inspects `src/Plugins/*/manual/module.php` (`academy`, `flextheme`, `pages`, `seo`, `SystemUpdater`) and mounts their interactive tutorials and QA checklists into the **Instruction Manual (`/admin/manual`)**.
- **Master AI Contract Guide (`AI_CONTRACT_GUIDE.md`):** Native IDE rule files (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`) and CLI generator (`php os-cli.php make:plugin <slug>`) enabling high-speed AI-assisted coding (*Vibe Coding on Rails*) with zero spaghetti code.

---

## 🛡️ Resiliency and Security (Mission Critical)

Because an enterprise system handles critical data, we implemented advanced engineering protection tools:

### The Circuit Breaker (Route Threshold & AtomicJsonStore)
If a plugin suffers a fatal error (e.g., memory overflow or corrupted dependency), the system doesn't throw a white screen. The **Circuit Breaker** steps in, catches the *Fatal Error*, isolates the failing route first (only ejecting the full plugin after reaching the consecutive failure threshold), persists state atomically via `AtomicJsonStore` (`flock` + `rename`), and allows the rest of the platform to continue operating perfectly.
- **Cascade Effect:** If the "Patients" module relies on the "Auth" module, and "Auth" crashes, the circuit breaker understands the dependency tree and cascades the ejection of "Patients" to save the server boot.
- **Schema Doctor (`SchemaDoctorInterface`) & 1-Click Snapshot:** The Kernel remains 100% agnostic to databases (`PDO`/SQLite/MySQL) and OS platforms (Windows/Linux/macOS), delegating schema auto-healing and 1-click state rollback to injected contracts.

### The Emergency Hatch
If a power failure occurs in the security core (`Auth`), triggering the Circuit Breaker, administrators aren't locked out. The Kernel activates the **Escape Route**. By accessing the URL, an emergency green terminal screen opens, requesting the server's `APP_KEY`, allowing provisional access (Safe Mode) for the administrator to reset the breakers.

### The Global Gatekeeper (Centralized ACL & Capability Security)
To maintain **SOLID** principles (Single Responsibility Principle) and avoid spaghetti code, the system's Controllers do not verify passwords or access superglobals (`$_SESSION` is banned in Controllers). Security is delegated to the **Router** and **IdentityManager**:
- You elegantly declare roles in the route: `['admin', 'doctor']` or check granular capabilities via `SessionManagerInterface`.
- If an unauthorized user tries to access it, the Router creates a barrier (Gatekeeper) and ejects the user before the Controller even steps into action.

---

## 📖 How to navigate (Links and Official Docs)
The main system documentation has evolved and is now centralized in our `docs/` folder and the official AI guide. We strongly recommend reading about the **OS 2.5.0** architecture:

- 🔄 **[Automatic Update System](Atualizacoes.md)** - Continuous delivery and update pipeline via GitHub Releases.
- 🤖 **[AI Contract Guide (`AI_CONTRACT_GUIDE.md`)](../AI_CONTRACT_GUIDE.md)** - The Master Guide for AI Assistants (*Vibe Coding on Rails*).
- 🚀 **[Interactive Extension Guide (Themes & Plugins)](../docs/tutorial-dev.html)** - The Dependency Injection and `ManualModuleInterface` standard.
- 📜 **[System Contract Manifesto](../docs/01-o-contrato-do-sistema.html)** - A lightweight, resilient alternative built on Contracts and Dependency Injection.
- 🤖 **[The Verifiable Trust Cycle (AI)](../docs/architecture/01-ciclo-de-confianca-verificavel.html)** - How we paved the way for Artificial Intelligence.
- ⚡ **[The Circuit Breaker](Disjuntor-Circuit-Breaker.md)**
- 🚪 **[The Emergency Hatch](Emergency-Hatch.md)**
- 🛑 **[The Global Gatekeeper (ACL)](Global-Gatekeeper.md)**

