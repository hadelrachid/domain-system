# Domain System Kernel 🚀

**Domain System** é um *Kernel* ultra-rápido, modular e de altíssima coesão construído do zero em PHP puro. Inspirado no ecossistema do WordPress, porém utilizando arquitetura de software moderna, Test-Driven Development (TDD) e princípios SOLID, ele atua como a fundação invisível para a criação de sistemas web robustos.

A filosofia principal do projeto é: **Lógica de negócio não se mistura com interface visual.**

## 🏗️ Arquitetura

O sistema é construído como um quebra-cabeça (peças de lego) dividido em 3 camadas principais:

### 1. O Kernel (Core)
O núcleo absoluto que orquestra tudo. Ele não conhece regras de negócio, apenas sabe injetar dependências, disparar eventos, e resolver rotas.
- **Container (PSR-11)**: Injeção de dependências e Singleton.
- **EventDispatcher**: Sistema poderoso de *Hooks* e *Callbacks* (`addListener`, `dispatch`).
- **Router**: Roteamento fluente e seguro de requisições web.

### 2. Plugins (Lógica de Negócio)
Qualquer funcionalidade (Aplicativo) do sistema é um **Plugin**. 
Os plugins vivem na pasta `src/Plugins/` e são os únicos responsáveis pela lógica, regras de negócio e consultas ao banco de dados. Eles se conectam ao Kernel através de rotas e eventos, mas **não geram HTML (views)**. 
- *Exemplo*: `Database Plugin` - Gerencia as conexões seguras PDO e provê o `QueryBuilder` contra injeção de SQL.

### 3. Themes (Motor de Apresentação)
A camada visual. O **Theme Engine** (`ThemeManager`) aplica renderização do lado do servidor (SSR) de altíssima performance. 
Toda a parte de Frontend (HTML, CSS, assets) moram na pasta `themes/`. Os plugins enviam as "variáveis" processadas para o ThemeManager, que injeta no HTML limpo usando métodos globais como `$this->getHeader()` e `$this->getFooter()`.

## ⚙️ Funcionalidades e Diferenciais
- **Zero Acoplamento**: Você pode arrancar um tema ou um plugin e o núcleo não vai quebrar.
- **Metadados Modernos (JSON)**: Os plugins são descobertos e gerenciados via arquivos `plugin.json` independentes.
- **QueryBuilder Integrado**: Proteção total contra SQL Injection.
- **100% Coberto por Testes**: Cada pedaço do Core (Hooks, DI, Plugins, Themes) possui testes unitários (PHPUnit) usando a metodologia TDD.

## 🚀 Como Iniciar

### Requisitos
- PHP 8.1+
- Composer (para gerenciar autoloader e PHPUnit)

### Instalação
1. Clone este repositório:
   ```bash
   git clone https://github.com/seu-usuario/domain-system.git
   ```
2. Inicie o servidor embutido do PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
3. Acesse `http://localhost:8000` e a mágica do roteamento e dos temas acontecerá.

## 🧪 Rodando os Testes
Para rodar a suíte completa de testes da arquitetura, execute:
```bash
php phpunit.phar --bootstrap tests/bootstrap.php tests/Unit
```

---
*Construído com obsessão por código limpo e arquitetura de software.*
