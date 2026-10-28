> 🇧🇷 [Ler em Português](#-visão-geral-do-domain-system) | 🇺🇸 [Read in English](#-domain-system-overview)

---

# 🇧🇷 Visão Geral do Domain System

O **Domain System** é uma plataforma modular em PHP 8.2+ para gerenciamento de páginas, serviços e temas, projetada sob os princípios de **Clean Architecture**, **SOLID** e **Inversão de Dependência (DIP)**.

O sistema opera com um núcleo enxuto (~30 MB em disco e ~5 MB de RAM no motor Zend), onde as funcionalidades de negócio são desacopladas em módulos independentes. O projeto encontra-se em ciclo ativo de desenvolvimento e melhorias contínuas.

---

## 🏛️ Arquitetura do Sistema

A arquitetura organiza-se em camadas bem definidas:

### 1. Núcleo e Despacho de Eventos (`src/Core`)
O núcleo do sistema provê apenas a infraestrutura básica:
- **Injeção de Dependências:** Container desacoplado para resolução de serviços.
- **Event Dispatcher (Hooks):** Comunicação entre módulos por meio de publicação e escuta de eventos.
- **Roteador Flexível:** Suporte nativo a execução em raiz de domínio ou subpastas de forma agnóstica.

### 2. Módulos de Infraestrutura (`src/SystemApps`)
Módulos essenciais para o funcionamento da plataforma (Banco de Dados, Autenticação, Painel Administrativo, Monitoramento e Atualizador). Possuem prioridade na ordem de inicialização do sistema.

### 3. Extensões de Domínio (`src/Plugins`)
Módulos de negócio e funcionalidades específicas (Construtor de Temas, Academy LMS, Páginas e SEO). Comunicam-se com os serviços do núcleo através do mediador `OsRuntime`.

### 4. Flex Builder (FSE)
Construtor visual de cabeçalhos, rodapés e seções centrais em CSS Grid de 12 colunas, com validação de payload antes do salvamento e controle de visibilidade responsiva por elemento em 4 resoluções.

### 5. Atualizador Automático (`SystemUpdater`)
Módulo integrado para verificação de versões e aplicação de atualizações via GitHub Releases com verificação de integridade por SHA-256 e substituição atômica de arquivos.

---

## 🛡️ Resiliência e Estabilidade

- **Circuit Breaker:** Captura de exceções e isolamento de falhas em tempo de execução para evitar interrupções completas do serviço.
- **Persistência Atômica (`AtomicJsonStore`):** Gravação segura de arquivos de configuração com bloqueio exclusivo (`LOCK_EX`) e gravação temporária com renomeação atômica.
- **Verificação de Esquema (`SchemaDoctorInterface`):** Verificação da integridade das tabelas de banco de dados (SQLite e MySQL).
- **Controle de Acesso por Capabilities:** Permissões granulares associadas a perfis no painel administrativo, com proteção anti-CSRF e tipagem estrita.

---

## 🔍 Auditoria Aberta: `project.txt`

O repositório disponibiliza na raiz o arquivo **[`project.txt`](../project.txt)**, contendo o mapeamento consolidado da arquitetura, interfaces e código do sistema. 

Esse recurso permite que desenvolvedores ou modelos de IA (Claude, ChatGPT, Gemini) realizem análises estáticas e auditorias completas com contexto integral do projeto.

---

## 📚 Documentos da Wiki

* **[Flex Builder FSE](Flex-Builder.md)** — Funcionamento dos 14 blocos semânticos, visibilidade responsiva em 4 resoluções com Auto-Reflow e contratos visuais.
* **[Atualizações e Deploy](Atualizacoes.md)** — Guia do atualizador automático via GitHub Releases e rotinas de backup.
* **[Disjuntor (Circuit Breaker)](Disjuntor-Circuit-Breaker.md)** — Funcionamento do isolamento de falhas em plugins e desativação em cascata.
* **[Controle de Acesso Centralizado](Global-Gatekeeper.md)** — Autorização declarativa no roteador e validação antecipada de permissões.
* **[Modo de Recuperação Administrativa](Emergency-Hatch.md)** — Mecanismo de contingência para acesso ao painel em caso de indisponibilidade de autenticação.

---

<br>

# 🇺🇸 Domain System Overview

**Domain System** is a modular web platform in PHP 8.2+ designed for managing pages, services, and themes, structured around **Clean Architecture**, **SOLID**, and **Dependency Inversion (DIP)** principles.

The platform operates with a lightweight footprint (~30 MB on disk and ~5 MB of Zend RAM), where business features are decoupled into independent modules.

---

## 🏛️ System Architecture

The architecture is organized into distinct layers:

### 1. Core Infrastructure (`src/Core`)
Provides fundamental runtime services:
- **Dependency Injection:** Container for service resolution and lifecycle management.
- **Event Dispatcher (Hooks):** Inter-module communication through event publication and listeners.
- **Environment-Agnostic Router:** Native execution in domain root or subfolder paths.

### 2. Infrastructure Modules (`src/SystemApps`)
Core system components (Database, Authentication, Cockpit Admin, Monitoring, and Auto-Updater). These modules receive boot sequence priority.

### 3. Domain Extensions (`src/Plugins`)
Application-level features (Theme Builder, Academy LMS, Pages, and SEO). Plugins interact with core services via the `OsRuntime` mediator.

### 4. Flex Builder (FSE)
Visual layout engine for headers, footers, and page sections using a 12-column CSS Grid, featuring payload validation and per-element responsive visibility across 4 resolutions.

### 5. Automated Updates (`SystemUpdater`)
Built-in update manager that retrieves releases from GitHub Releases, validates packages with SHA-256 checksums, and performs atomic file swaps.

---

## 🛡️ Resilience and Stability

- **Circuit Breaker:** Exception trapping and fault isolation during runtime to prevent cascading failures.
- **Atomic Persistence (`AtomicJsonStore`):** Safe configuration writes using exclusive file locking (`LOCK_EX`) and atomic renames.
- **Schema Doctor (`SchemaDoctorInterface`):** Table structure verification across SQLite and MySQL engines.
- **Capability-Based Access Control:** Granular permissions configurable via the admin panel, featuring CSRF tokens and strict typing.

---

## 🔍 Open Auditing: `project.txt`

The root directory provides **[`project.txt`](../project.txt)**, compiling the complete system architecture, interface contracts, and source code into a single document.

This enables developers and AI models (Claude, ChatGPT, Gemini) to perform comprehensive audits and static analysis with complete context.

---

## 📚 Wiki Guides

* **[Flex Builder FSE](Flex-Builder.md)** — Guide to the 14 semantic blocks, 4-resolution responsive visibility with Auto-Reflow, and visual contracts.
* **[Updates & Deployment](Atualizacoes.md)** — Documentation on the GitHub Releases auto-updater and backup workflows.
* **[Circuit Breaker & Resilience](Disjuntor-Circuit-Breaker.md)** — Mechanics of runtime fault isolation for extensions and cascading deactivation.
* **[Centralized Access Control](Global-Gatekeeper.md)** — Declarative routing authorization and preemptive permission validation.
* **[Administrative Recovery Mode](Emergency-Hatch.md)** — Core contingency fallback route for recovery when auth services are unavailable.
