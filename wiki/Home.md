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
Módulos de negócio e funcionalidades da plataforma:
- **Academy LMS:** Gestão completa de cursos, aulas com vídeos e apostilas em PDF, área do aluno e certificados.
- **DomainCommerce:** Pagamentos integrados via Mercado Pago (Pix instantâneo e Cartão até 12x), PayPal oficial e webhooks de plataformas de afiliados (Hotmart, Eduzz, Monetizze, ClickBank).
- **Flex Builder:** Construtor visual de cabeçalhos, rodapés e seções em grid responsivo.
- **Páginas e SEO:** Criação de páginas de conteúdo e otimização para motores de busca.

---

## 🛡️ Resiliência e Estabilidade

- **Circuit Breaker:** Isolamento de falhas em tempo de execução para evitar que erros em extensões derrubem o site.
- **Persistência Segura (`AtomicJsonStore`):** Gravação segura de arquivos de configuração com bloqueio concorrente (`LOCK_EX`).
- **Verificação de Banco (`SchemaDoctorInterface`):** Verificação da integridade das tabelas de banco de dados (SQLite e MySQL).
- **Segurança de Acesso:** Proteção anti-CSRF, controle de permissões por perfil e tipagem estrita no PHP 8.2+.

---

## 🔍 Visão Geral do Código: `project.txt`

O repositório disponibiliza na raiz o arquivo **[`project.txt`](../project.txt)**, contendo o mapeamento consolidado da arquitetura, contratos e código-fonte, facilitando auditorias e revisões completas da base de código.

---

## 📚 Documentos da Wiki

* **[Flex Builder FSE](Flex-Builder.md)** — Funcionamento dos blocos semânticos, visibilidade responsiva em 4 resoluções com Auto-Reflow e contratos visuais.
* **[Atualizações e Deploy](Atualizacoes.md)** — Guia do atualizador automático via GitHub Releases e rotinas de backup.
* **[Disjuntor (Circuit Breaker)](Disjuntor-Circuit-Breaker.md)** — Isolamento de falhas em plugins para garantir alta disponibilidade.
* **[Controle de Acesso Centralizado](Global-Gatekeeper.md)** — Autorização declarativa no roteador e validação de permissões.
* **[Modo de Recuperação Administrativa](Emergency-Hatch.md)** — Mecanismo de contingência para acesso seguro ao painel administrativo.

---

<br>

# 🇺🇸 Domain System Overview

**Domain System** is a lightweight, modular web platform in PHP 8.2+ designed for managing websites, online courses, and content, built with clean, decoupled architecture.

The platform operates with a minimal footprint (~30 MB on disk and ~5 MB of Zend RAM), where business features live in decoupled modules.

---

## 🏛️ System Architecture

The architecture is organized into clear layers:

### 1. Core Infrastructure (`src/Core`)
Provides essential foundation services:
- **Dependency Injection:** Container for service resolution.
- **Event Dispatcher (Hooks):** Inter-module communication through event publication.
- **Environment-Agnostic Router:** Native execution in domain root or subfolder paths.

### 2. Infrastructure Modules (`src/SystemApps`)
Core system components (Database, Authentication, Cockpit Admin, Monitoring, and Auto-Updater).

### 3. Feature Extensions (`src/Plugins`)
Business modules and platform capabilities:
- **Academy LMS:** Complete course management, video and PDF lessons, student dashboard, and automated certificates.
- **DomainCommerce:** Integrated checkout with Mercado Pago (Instant Pix, credit cards up to 12x), official PayPal, and affiliate webhooks (Hotmart, Eduzz, Monetizze, ClickBank).
- **Flex Builder:** Visual layout builder for headers, footers, and page sections on a responsive 12-column grid.
- **Pages & SEO:** Content pages and search engine optimization.

---

## 🛡️ Resilience and Stability

- **Circuit Breaker:** Runtime failure isolation ensuring plugins cannot crash the core system.
- **Safe Persistence (`AtomicJsonStore`):** Concurrency-safe configuration writes using `LOCK_EX`.
- **Database Schema Validation (`SchemaDoctorInterface`):** Automatic integrity checks for MySQL and SQLite tables.
- **Access Security:** Strict CSRF validation, role-based capabilities, and modern PHP 8.2+ type safety.

---

## 🔍 Codebase Overview: `project.txt`

The repository provides a consolidated **[`project.txt`](../project.txt)** file at the root, mapping out the complete architecture and code tree for quick technical reviews.
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
