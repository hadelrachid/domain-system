> 🇧🇷 [Ler em Português](#-bem-vindo-ao-domain-system-cockpit-) | 🇺🇸 [Read in English](#-welcome-to-domain-system-cockpit-)

---

# 🇧🇷 Bem-vindo ao Domain-System (Cockpit) 🚀

O **Domain-System** não é apenas mais um sistema de gestão de clínicas. É uma plataforma **Event-Driven (Orientada a Eventos)** altamente modular, resiliente e desenhada sob os rigorosos princípios da arquitetura **SOLID**. 

Inspirado nas engrenagens de grandes ecossistemas (como o WordPress e sistemas operacionais de missão crítica), o projeto foi concebido para ser altamente escalável, permitindo que componentes sejam conectados ou desconectados em tempo real sem afetar o núcleo do sistema.

---

## 🏛️ Arquitetura em Camadas

A arquitetura do Domain-System é dividida em três pilares principais:

### 1. O Kernel (SystemAdmin) e o Event Dispatcher
No coração do sistema, não existe regra de negócio (Pacientes, Prontuários, Finanças). O Kernel atua puramente como um **Sistema Operacional**. Sua principal ferramenta é o **Event Dispatcher** (Despachante de Eventos).
- O Kernel não chama os módulos. Ele apenas "grita" eventos no sistema (ex: `router.register`, `admin.menu`, `appointment.created`).
- Os módulos (Plugins), que estão "escutando" essas frequências, reagem e injetam seus dados no Kernel. Isso garante **desacoplamento absoluto**.

### 2. O Ecossistema de Plugins (Módulos)
Cada funcionalidade do sistema (Triagem, WhatsApp, Prontuários, Jurídico) é um **Plugin**. 
Os plugins são independentes, injetam suas próprias rotas, menus e escutam eventos do Kernel.
- **Injeção de Dependências (DIP):** Os módulos usam Containers e Contratos (Interfaces) para realizar o "trabalho sujo" com o banco de dados via Padrão Repository (Ex: `WhatsAppProviderInterface`). O controlador nunca toca no banco de dados diretamente.

### 3. A Régua de Extensão (Plugin Pack)
Pensando na escalabilidade ilimitada, o sistema adotou a filosofia da "Régua de Extensão" (Hub). Em vez de entupir o Menu Principal do Kernel com dezenas de botões, o sistema prevê "Pacotes" (como o `clinic_pack`). O Pacote se conecta ao Kernel e, por sua vez, fornece *sub-tomadas* para os módulos médicos (Médicos, Pacientes, Histórico), organizando tudo em submenus elegantes.

---

## 🛡️ Resiliência e Segurança (Missão Crítica)

Como um sistema de saúde lida com dados críticos, implementamos ferramentas de proteção em nível de engenharia avançada:

### O Disjuntor (Circuit Breaker)
Se um plugin sofre um erro fatal (Ex: estouro de memória ou dependência corrompida), o sistema não tela branco. O **Disjuntor** entra em ação, captura o *Fatal Error*, joga o erro para o Painel de Supervisão e **Desativa o plugin problemático automaticamente**, permitindo que o resto do hospital continue funcionando perfeitamente.
- **Efeito Cascata:** Se o módulo "Pacientes" depender do módulo "Auth", e o "Auth" cair, o disjuntor entende a árvore de dependências e ejeta o "Pacientes" em cascata, salvando o boot do servidor.

### A Escotilha de Emergência (Emergency Hatch)
Caso uma falha de energia ocorra no núcleo de segurança (`Auth`), ativando o Disjuntor, os administradores não ficam trancados para fora. O Kernel ativa a **Rota de Fuga**. Ao acessar a URL, uma tela de terminal verde de emergência se abre, solicitando a `APP_KEY` do servidor, permitindo acesso provisório (Modo de Segurança) para o administrador religar os disjuntores.

### O Porteiro Global (ACL Centralizado)
Para manter os princípios **SOLID** (Single Responsibility Principle) e evitar código espaguete, os Controladores do sistema não verificam senhas nem cargos de usuários. A segurança é delegada 100% ao **Router (Roteador)**. O Roteador age como um Porteiro Global:
- Você declara de forma elegante na rota: `['admin', 'doctor']`.
- Se uma recepcionista tentar acessar, o Roteador cria uma barreira (Gatekeeper) e ejeta o usuário antes mesmo do Controlador e da Memória entrarem em ação.

---

## 📖 Como navegar na Wiki
*(Aqui você pode criar sub-páginas, por exemplo:)*
- **1. Guia do Desenvolvedor:** Como criar um Plugin do zero.
- **2. Entendendo o Injetor de Dependências.**
- **3. Como conectar uma IA via API (Hooks e Eventos).**
# Welcome to Domain-System (Cockpit) 🚀

The **Domain-System** is not just another clinic management system. It is a highly modular, resilient, **Event-Driven** platform designed under strict **SOLID** architecture principles.

Inspired by the gears of large ecosystems (such as WordPress and mission-critical operating systems), the project was designed to be highly scalable, allowing components to be plugged in or unplugged in real-time without affecting the system's core.

---

## 🏛️ Layered Architecture

The architecture of the Domain-System is divided into three main pillars:

### 1. The Kernel (SystemAdmin) and Event Dispatcher
At the heart of the system, there are no business rules (Patients, Medical Records, Finances). The Kernel acts purely as an **Operating System**. Its main tool is the **Event Dispatcher**.
- The Kernel does not directly call modules. It simply "shouts" events into the system (e.g., `router.register`, `admin.menu`, `appointment.created`).
- The modules (Plugins), which are "listening" to these frequencies, react and inject their data into the Kernel. This ensures **absolute decoupling**.

### 2. The Plugin Ecosystem (Modules)
Every system feature (Triage, WhatsApp, Medical Records, Legal) is a **Plugin**.
Plugins are independent—they inject their own routes, menus, and listen to Kernel events.
- **Dependency Injection (DIP):** The modules use Containers and Contracts (Interfaces) to do the "dirty work" with the database via the Repository Pattern (e.g., `WhatsAppProviderInterface`). The controller never touches the database directly.

### 3. The Extension Cord (Plugin Pack)
Thinking about unlimited scalability, the system adopted the "Extension Cord" (Hub) philosophy. Instead of cluttering the Kernel's Main Menu with dozens of buttons, the system anticipates "Packs" (like the `clinic_pack`). The Pack connects to the Kernel and, in turn, provides *sub-sockets* for medical submodules (Doctors, Patients, History), elegantly organizing everything into submenus.

---

## 🛡️ Resiliency and Security (Mission Critical)

Because a healthcare system handles critical data, we implemented advanced engineering protection tools:

### The Circuit Breaker
If a plugin suffers a fatal error (e.g., memory overflow or corrupted dependency), the system doesn't throw a white screen. The **Circuit Breaker** steps in, catches the *Fatal Error*, logs it to the Supervision Panel, and **automatically deactivates the problematic plugin**, allowing the rest of the hospital to continue operating perfectly.
- **Cascade Effect:** If the "Patients" module relies on the "Auth" module, and "Auth" crashes, the circuit breaker understands the dependency tree and cascades the ejection of "Patients" to save the server boot.

### The Emergency Hatch
If a power failure occurs in the security core (`Auth`), triggering the Circuit Breaker, administrators aren't locked out. The Kernel activates the **Escape Route**. By accessing the URL, an emergency green terminal screen opens, requesting the server's `APP_KEY`, allowing provisional access (Safe Mode) for the administrator to reset the breakers.

### The Global Gatekeeper (Centralized ACL)
To maintain **SOLID** principles (Single Responsibility Principle) and avoid spaghetti code, the system's Controllers do not verify passwords or user roles. Security is delegated 100% to the **Router**. The Router acts as a Global Gatekeeper:
- You elegantly declare it in the route: `['admin', 'doctor']`.
- If a receptionist tries to access it, the Router creates a barrier (Gatekeeper) and ejects the user before the Controller and Memory even step into action.

---

## 📖 How to navigate the Wiki
*(Here you can link to sub-pages, for example:)*
- **1. Developer's Guide:** How to create a Plugin from scratch.
- **2. Understanding the Dependency Injector.**
- **3. How to connect an AI via API (Hooks and Events).**
 
<br><br><br><hr>
 
# 🇺🇸 Welcome to Domain-System (Cockpit) 🚀

The **Domain-System** is not just another clinic management system. It is a highly modular, resilient, **Event-Driven** platform designed under strict **SOLID** architecture principles.

Inspired by the gears of large ecosystems (such as WordPress and mission-critical operating systems), the project was designed to be highly scalable, allowing components to be plugged in or unplugged in real-time without affecting the system's core.

---

## 🏛️ Layered Architecture

The architecture of the Domain-System is divided into three main pillars:

### 1. The Kernel (SystemAdmin) and Event Dispatcher
At the heart of the system, there are no business rules (Patients, Medical Records, Finances). The Kernel acts purely as an **Operating System**. Its main tool is the **Event Dispatcher**.
- The Kernel does not directly call modules. It simply "shouts" events into the system (e.g., `router.register`, `admin.menu`, `appointment.created`).
- The modules (Plugins), which are "listening" to these frequencies, react and inject their data into the Kernel. This ensures **absolute decoupling**.

### 2. The Plugin Ecosystem (Modules)
Every system feature (Triage, WhatsApp, Medical Records, Legal) is a **Plugin**.
Plugins are independent—they inject their own routes, menus, and listen to Kernel events.
- **Dependency Injection (DIP):** The modules use Containers and Contracts (Interfaces) to do the "dirty work" with the database via the Repository Pattern (e.g., `WhatsAppProviderInterface`). The controller never touches the database directly.

### 3. The Extension Cord (Plugin Pack)
Thinking about unlimited scalability, the system adopted the "Extension Cord" (Hub) philosophy. Instead of cluttering the Kernel's Main Menu with dozens of buttons, the system anticipates "Packs" (like the `clinic_pack`). The Pack connects to the Kernel and, in turn, provides *sub-sockets* for medical submodules (Doctors, Patients, History), elegantly organizing everything into submenus.

---

## 🛡️ Resiliency and Security (Mission Critical)

Because a healthcare system handles critical data, we implemented advanced engineering protection tools:

### The Circuit Breaker
If a plugin suffers a fatal error (e.g., memory overflow or corrupted dependency), the system doesn't throw a white screen. The **Circuit Breaker** steps in, catches the *Fatal Error*, logs it to the Supervision Panel, and **automatically deactivates the problematic plugin**, allowing the rest of the hospital to continue operating perfectly.
- **Cascade Effect:** If the "Patients" module relies on the "Auth" module, and "Auth" crashes, the circuit breaker understands the dependency tree and cascades the ejection of "Patients" to save the server boot.

### The Emergency Hatch
If a power failure occurs in the security core (`Auth`), triggering the Circuit Breaker, administrators aren't locked out. The Kernel activates the **Escape Route**. By accessing the URL, an emergency green terminal screen opens, requesting the server's `APP_KEY`, allowing provisional access (Safe Mode) for the administrator to reset the breakers.

### The Global Gatekeeper (Centralized ACL)
To maintain **SOLID** principles (Single Responsibility Principle) and avoid spaghetti code, the system's Controllers do not verify passwords or user roles. Security is delegated 100% to the **Router**. The Router acts as a Global Gatekeeper:
- You elegantly declare it in the route: `['admin', 'doctor']`.
- If a receptionist tries to access it, the Router creates a barrier (Gatekeeper) and ejects the user before the Controller and Memory even step into action.

---

## 📖 How to navigate the Wiki
*(Here you can link to sub-pages, for example:)*
- **1. Developer's Guide:** How to create a Plugin from scratch.
- **2. Understanding the Dependency Injector.**
- **3. How to connect an AI via API (Hooks and Events).**
