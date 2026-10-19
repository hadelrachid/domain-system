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

### 3. A Régua de Extensão (Plugin Pack) e o Academy LMS Híbrido
Pensando na escalabilidade ilimitada, o sistema adotou a filosofia da "Régua de Extensão" (Hub). Em vez de entupir o Menu Principal do Kernel com dezenas de botões, o sistema prevê "Pacotes" (como o `clinic_pack`) e Hubs educacionais como o **Academy LMS (`academy`)**, que traz edição híbrida de aulas (HTML/Markdown puro + Editor em Blocos com Preview Dinâmico e Simulador Multi-Dispositivos para Desktop, Smartphones e Tablets).

### 4. Auto-Descoberta de Manuais por Contrato (`ManualModuleInterface`)
Na **v2.3**, o Cockpit possui um rastreador automático (`PluginManualScanner`) que varre todas as pastas `src/Plugins/*/manual/module.php`. Qualquer plugin que implemente o contrato `ManualModuleInterface` tem seu guia de instruções e checklist de QA acoplados automaticamente ao menu **Manual de Instruções (`/admin/manual`)**, mantendo o Kernel 100% agnóstico.

---

## 🛡️ Resiliência e Segurança (Missão Crítica)

Como um sistema corporativo lida com dados críticos, implementamos ferramentas de proteção em nível de engenharia avançada:

### O Disjuntor (Circuit Breaker)
Se um plugin sofre um erro fatal (Ex: estouro de memória ou dependência corrompida), o sistema não exibe tela branca. O **Disjuntor** entra em ação, captura o *Fatal Error*, joga o erro para o Painel de Supervisão e **Desativa o plugin problemático automaticamente**, permitindo que o resto da plataforma continue funcionando perfeitamente.
- **Efeito Cascata:** Se o módulo "usuários" depender do módulo "Auth", e o "Auth" cair, o disjuntor entende a árvore de dependências e ejeta o "usuários" em cascata, salvando o boot do servidor.

### A Escotilha de Emergência (Emergency Hatch)
Caso uma falha de energia ocorra no núcleo de segurança (`Auth`), ativando o Disjuntor, os administradores não ficam trancados para fora. O Kernel ativa a **Rota de Fuga**. Ao acessar a URL, uma tela de terminal verde de emergência se abre, solicitando a `APP_KEY` do servidor, permitindo acesso provisório (Modo de Segurança) para o administrador religar os disjuntores.

### O Porteiro Global (ACL Centralizado & Capability Security)
Para manter os princípios **SOLID** (Single Responsibility Principle) e evitar código espaguete, os Controladores do sistema não verificam senhas nem acessam superglobais (`$_SESSION` é proibido em Controllers). A segurança é delegada ao **Router (Roteador)** e ao **IdentityManager**:
- Você declara de forma elegante na rota: `['admin', 'doctor']` ou valida *Capabilities* granulares via `SessionManagerInterface`.
- Se um usuário sem permissão tentar acessar, o Roteador cria uma barreira (Gatekeeper) e ejeta a requisição antes mesmo do Controlador entrar em ação.

---

## 📖 Como navegar (Links e Documentação Oficial)
A documentação principal do sistema evoluiu e agora está centralizada em nossa pasta `docs/`. Recomendamos fortemente a leitura da arquitetura **OS 2.3**:

- 🚀 **[Guia Interativo de Extensões (Temas e Plugins)](../docs/tutorial-dev.html)** - O padrão de Injeção de Dependências e `ManualModuleInterface`.
- 📜 **[Manifesto do Contrato do Sistema](../docs/01-o-contrato-do-sistema.html)** - Por que abandonamos o Padrão WordPress.
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

### 3. The Extension Cord (Plugin Pack) & Hybrid Academy LMS
Thinking about unlimited scalability, the system adopted the "Extension Cord" (Hub) philosophy. Instead of cluttering the Kernel's Main Menu with dozens of buttons, the system supports "Packs" (like `clinic_pack`) and full-featured hubs like **Academy LMS (`academy`)**, featuring hybrid lesson authoring (Raw HTML/Markdown + Block Editor with Real-Time Preview and Multi-Device Simulator for Desktop, Smartphones, and Tablets).

### 4. Contract-Driven Manual Auto-Discovery (`ManualModuleInterface`)
In **v2.3**, the Cockpit includes an automatic scanner (`PluginManualScanner`) that inspects `src/Plugins/*/manual/module.php`. Any plugin implementing `ManualModuleInterface` has its interactive tutorial and QA checklist automatically mounted into the **Instruction Manual (`/admin/manual`)**, keeping the Kernel 100% decoupled.

---

## 🛡️ Resiliency and Security (Mission Critical)

Because an enterprise system handles critical data, we implemented advanced engineering protection tools:

### The Circuit Breaker
If a plugin suffers a fatal error (e.g., memory overflow or corrupted dependency), the system doesn't throw a white screen. The **Circuit Breaker** steps in, catches the *Fatal Error*, logs it to the Supervision Panel, and **automatically deactivates the problematic plugin**, allowing the rest of the platform to continue operating perfectly.
- **Cascade Effect:** If the "Patients" module relies on the "Auth" module, and "Auth" crashes, the circuit breaker understands the dependency tree and cascades the ejection of "Patients" to save the server boot.

### The Emergency Hatch
If a power failure occurs in the security core (`Auth`), triggering the Circuit Breaker, administrators aren't locked out. The Kernel activates the **Escape Route**. By accessing the URL, an emergency green terminal screen opens, requesting the server's `APP_KEY`, allowing provisional access (Safe Mode) for the administrator to reset the breakers.

### The Global Gatekeeper (Centralized ACL & Capability Security)
To maintain **SOLID** principles (Single Responsibility Principle) and avoid spaghetti code, the system's Controllers do not verify passwords or access superglobals (`$_SESSION` is banned in Controllers). Security is delegated to the **Router** and **IdentityManager**:
- You elegantly declare roles in the route: `['admin', 'doctor']` or check granular capabilities via `SessionManagerInterface`.
- If an unauthorized user tries to access it, the Router creates a barrier (Gatekeeper) and ejects the user before the Controller even steps into action.

---

## 📖 How to navigate (Links and Official Docs)
The main system documentation has evolved and is now centralized in our `docs/` folder. We strongly recommend reading about the **OS 2.3** architecture:

- 🚀 **[Interactive Extension Guide (Themes & Plugins)](../docs/tutorial-dev.html)** - The Dependency Injection and `ManualModuleInterface` standard.
- 📜 **[System Contract Manifesto](../docs/01-o-contrato-do-sistema.html)** - Why we abandoned the WordPress Pattern.
- 🤖 **[The Verifiable Trust Cycle (AI)](../docs/architecture/01-ciclo-de-confianca-verificavel.html)** - How we paved the way for Artificial Intelligence.
- ⚡ **[The Circuit Breaker](Disjuntor-Circuit-Breaker.md)**
- 🚪 **[The Emergency Hatch](Emergency-Hatch.md)**
- 🛑 **[The Global Gatekeeper (ACL)](Global-Gatekeeper.md)**

