# Domain System Kernel 🚀

[🇧🇷 Versão em Português](#-português) | [🇺🇸 English Version](#-english)

---

## 🇧🇷 Português

**Domain System** é um *Kernel* ultra-rápido, modular e de altíssima coesão construído do zero em PHP puro. Inspirado na flexibilidade do ecossistema do WordPress, porém utilizando arquitetura de software moderna, Test-Driven Development (TDD) e princípios SOLID estritos (Validado com nota 10/10 em auditorias estruturais). Ele atua como a fundação invisível para a criação de SaaS e sistemas web escaláveis.

A filosofia principal do projeto é: **Lógica de negócio não se mistura com interface visual.**

### 🏗️ Arquitetura

O sistema é construído como um quebra-cabeça dividido em 3 camadas principais (MVC evoluído):

#### 1. O Kernel (Core)
O núcleo absoluto que orquestra tudo. Ele não conhece regras de negócio, apenas sabe injetar dependências, disparar eventos, e resolver rotas.
- **Container (PSR-11)**: Injeção de dependências e Singleton (Dependency Inversion).
- **EventDispatcher**: Sistema poderoso de *Hooks* e *Callbacks* para extensibilidade (Open-Closed Principle).
- **Router**: Roteamento fluente e seguro.
- **PluginManager**: Descobre, inicializa e blinda o carregamento de módulos isolados.

#### 2. Plugins (Lógica de Negócio)
Qualquer funcionalidade do sistema é um **Plugin**. 
Os plugins vivem na pasta `src/Plugins/` e são os únicos responsáveis pela lógica de domínio e banco de dados. Eles se conectam ao Kernel através de rotas e eventos, mas **não geram HTML (views)**. 
- **Database**: Gerencia conexões PDO e provê o `QueryBuilder` contra SQL Injection.
- **Auth**: Cuida do acesso, criptografia, proteção de middlewares e isolamento de rotas fechadas.
- **SystemAdmin**: Provê a interface de painel de controle (Upload de ZIPs, ativar/desativar plugins nativamente).

#### 3. Themes (Motor de Apresentação)
A camada visual. O **Theme Engine** (`ThemeManager`) aplica renderização do lado do servidor (SSR) de altíssima performance. 
Toda a parte de Frontend (HTML, CSS, assets) mora na pasta `themes/`. Os controllers dos plugins enviam os dados processados para o ThemeManager, que os injeta no HTML limpo usando métodos globais como `$this->getHeader()`.

### ⚙️ Funcionalidades e Diferenciais
- **Zero Acoplamento**: Você pode arrancar um tema ou um plugin e o núcleo não vai quebrar. Existe proteção de *Crash* no boot.
- **Instalação via ZIP**: Suporte nativo a envio de plugins compactados (.zip) direto pelo painel, extraindo em tempo real.
- **Metadados Modernos**: Plugins gerenciados via arquivos `plugin.json` independentes.
- **100% TDD**: Cada pedaço do Core (Hooks, DI, Plugins, Themes) possui testes unitários (PHPUnit).

### 🚀 Como Iniciar

1. Clone este repositório e inicie um servidor no diretório raiz (XAMPP ou PHP Built-in Server).
2. Acesse a pasta do projeto no terminal e instale o primeiro administrador:
   ```bash
   php src/Plugins/auth/scripts/create_admin.php
   ```
3. Acesse `http://localhost/seu-projeto/login` usando `admin@daherclinica.com.br` e `senha123`.
4. Entre no **Painel de Controle** (`/admin/plugins`) para gerenciar as extensões do seu sistema.

Para rodar os testes:
```bash
php phpunit.phar --bootstrap tests/bootstrap.php tests/Unit
```

---

## 🇺🇸 English

**Domain System** is an ultra-fast, highly cohesive, and modular *Kernel* built from scratch in pure PHP. Inspired by the flexibility of the WordPress ecosystem, but leveraging modern software architecture, Test-Driven Development (TDD), and strict SOLID principles (validated with a 10/10 score in structural audits). It acts as the invisible foundation for building scalable SaaS and web systems.

The core philosophy of the project is: **Business logic must never mix with visual interfaces.**

### 🏗️ Architecture

The system is built like a puzzle, divided into 3 main layers (evolved MVC):

#### 1. The Kernel (Core)
The absolute core that orchestrates everything. It doesn't know any business rules; it only knows how to inject dependencies, dispatch events, and resolve routes.
- **Container (PSR-11)**: Dependency Injection and Singleton management (Dependency Inversion).
- **EventDispatcher**: A powerful *Hooks* and *Callbacks* system for extensibility (Open-Closed Principle).
- **Router**: Fluent and secure HTTP routing.
- **PluginManager**: Discovers, boots, and shields the loading of isolated modules with Crash Protection.

#### 2. Plugins (Business Logic)
Every single feature of the system is a **Plugin**. 
Plugins live in the `src/Plugins/` folder and are exclusively responsible for domain logic and database operations. They connect to the Kernel via routes and events but **do not generate HTML (views)**. 
- **Database**: Manages PDO connections and provides a secure `QueryBuilder` against SQL Injection.
- **Auth**: Handles access, cryptography, middleware protection, and isolated route shielding.
- **SystemAdmin**: Provides the dashboard interface (ZIP uploads, native enable/disable toggles).

#### 3. Themes (Presentation Engine)
The visual layer. The **Theme Engine** (`ThemeManager`) applies high-performance Server-Side Rendering (SSR). 
All Frontend code (HTML, CSS, assets) lives in the `themes/` folder. Plugin controllers send processed data to the ThemeManager, which injects it into clean HTML using global methods like `$this->getHeader()`.

### ⚙️ Features and Highlights
- **Zero Coupling**: You can rip out a theme or a plugin and the core won't break. Features native Boot Crash Protection.
- **ZIP Installation**: Native support for uploading zipped plugins directly through the dashboard, extracting them on the fly.
- **Modern Metadata**: Plugins are managed via independent `plugin.json` files.
- **100% TDD**: Every piece of the Core (Hooks, DI, Plugins, Themes) has unit tests (PHPUnit).

### 🚀 Getting Started

1. Clone this repository and start a server in the root directory (XAMPP or PHP Built-in Server).
2. Open your terminal in the project folder and install the first administrator:
   ```bash
   php src/Plugins/auth/scripts/create_admin.php
   ```
3. Access `http://localhost/your-project/login` using `admin@daherclinica.com.br` and `senha123`.
4. Enter the **Control Panel** (`/admin/plugins`) to manage your system's extensions.

To run the test suite:
```bash
php phpunit.phar --bootstrap tests/bootstrap.php tests/Unit
```

---
*Built with an obsession for clean code and software architecture.*
