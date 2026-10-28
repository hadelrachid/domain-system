> **ALINHAMENTO ARQUITETURAL / PARADIGM SHIFT (2026-10-07)**
> 
> O Domain System evoluiu sua identidade conceitual. Ele é uma **Plataforma de Execução de Aplicações Web** e um **Framework de Ecossistema**. 
> Ele atua primariamente como um **Administrador de Páginas da Web (CMS Avançado)** e um **Orquestrador de Serviços e APIs**. 
> Embora ele adote a *arquitetura e o jargão* de Sistemas Operacionais (Micro-Kernel, Anéis de Proteção - Rings, Isolamento de Processos, IPC), ele não é um OS bare-metal (como Linux/Windows), mas sim um **Ambiente Operacional Web (Web Operating Environment)** que roda no topo da stack PHP/Linux.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.5.0-blueviolet?style=for-the-badge" alt="Version 2.5.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~30MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>🛸 Domain System (v2.5)</h1>
  <p><strong>Uma Plataforma Web alternativa, leve, modular e resiliente, estruturada em SOLID e Contratos.</strong></p>
</div>

---

[🇺🇸 Read in English](README-en.md) | [📖 Documentação & Tutoriais](https://hadelrachid.github.io/domain-system/) | [🤖 Guia de Contratos para IAs](AI_CONTRACT_GUIDE.md) | [🛡️ Auditoria Arquitetural](docs/auditoria.md)

## 🧠 O Que é o Domain System?

O **Domain System** não é apenas mais um CMS ou Framework de prateleira. Ele nasceu com o objetivo de ser uma **alternativa leve, modular e resiliente** dentro do ecossistema web tradicional, ocupando atualmente apenas **~30 MB** de espaço descompactado em servidores Linux.

Construído sob a rigorosa cartilha do **SOLID** e utilizando puramente **Injeção de Dependências (DIP)**, ele se comporta como um **Ambiente Operacional Web**.
O limite é a sua imaginação:
- O **Núcleo (Micro-Kernel / Ring 0)** fornece a infraestrutura de baixo nível, 100% agnóstica de banco de dados e de Sistema Operacional (Linux, Windows ou macOS): Roteamento Dinâmico Agnóstico de Ambiente (Subpasta ou Raiz), Telemetria em Tempo Real, Persistência Atômica (`AtomicJsonStore`), Diagnóstico por Contrato (`SchemaDoctorInterface`), Snapshot & Rollback 1-Clique, Segurança Baseada em Capabilities (ACL, Anti-CSRF) e Auto-Descoberta de Manuais (`ManualModuleInterface`).
- Toda a **lógica de negócio** e **gestão de páginas/serviços** é injetada através de **Módulos (Ring 3)** por meio de **Contratos (Interfaces)**. O núcleo nunca sabe o que a aplicação faz ou qual banco de dados está conectado — ele apenas orquestra os contratos!

## 🚀 Principais Diferenciais

### 🛡️ 1. Governança Arquitetural Ring 0 / Ring 3 & Isolamento de Falhas (DI Boundary)
Inspirado na organização em camadas de Sistemas Operacionais, o sistema separa seus módulos em duas categorias arquiteturais dentro do runtime PHP:
- **SystemApps (`src/SystemApps` — Ring 0):** Módulos essenciais de infraestrutura (Banco de Dados, Auth, Admin, SystemMonitor, SystemUpdater). Possuem prioridade na fila topológica de boot e **nunca são desativados automaticamente** pelo Circuit Breaker em caso de erro transitório.
- **UserPlugins (`src/Plugins` — Ring 3):** Módulos de domínio e extensões que interagem com o núcleo por meio do mediador `OsRuntime` (que valida permissões declaradas no manifesto `osRegister()` antes de liberar serviços ou hooks do Container DI) e estão sujeitos a **quarentena automática pelo Disjuntor (Circuit Breaker)** se lançarem exceções fatais no boot ou nas rotas. *(Nota técnica: como todo código PHP executa no mesmo processo Zend, essa divisão provê governança arquitetural de dependências e tolerância a falhas, não um sandbox de processo a nível de SO — instale apenas plugins de fontes confiáveis, assim como no Composer/WordPress).*

### 🛡️ 2. Gatekeeper & Extração Segura em Quarentena (`ZipArchiveExtractor`)
Validação estrutural estrita no upload de pacotes `.zip` de plugins e temas: o extrator bloqueia ataques de *Zip-Slip* (caminhos relativos `../`, caminhos absolutos Unix/Windows `/`, `\`, `C:`, wrappers `phar://` e *symlinks* UNIX), realiza extração controlada via stream para diretório temporário de quarentena validado por `realpath()` e impede colisões de namespace com `DomainSystem\SystemApps` ou sobrescrita de módulos `core`.

### 🔋 3. No-Break Shield, Persistência Atômica & Snapshot 1-Clique
Esqueça a "Tela Branca da Morte" (WSOD). O Domain System roda com o **No-Break Shield**: um *Circuit Breaker* inteligente (no boot e por rota com limiar configurável) aliado ao `AtomicJsonStore` (`LOCK_EX` + escrita atômica), **Schema Doctor** (`SchemaDoctorInterface`) e **Snapshot & Rollback em 1 Clique** no painel de Supervisão (`/admin/monitor`).

### 🏗️ 4. Flex Builder FSE (Full Site Editing, 14 Blocos, 4 Resoluções & Auto-Reflow de CSS Grid)
O ecossistema conta com o **Flex Builder (`/admin/builder`)** em arquitetura 100% SOLID (`src/Core/Theme/Parts`):
- **Compilador Único (`ThemePartCompilerInterface`) & Validador Estrutural (`ThemePartValidatorInterface`):** O Preview do Studio e o Frontend publicado compartilham exatamente o mesmo motor de compilação, com validação pré-persistência de tipos, presets, compatibilidade de blocos e compensação transacional nas associações.
- **Visibilidade Responsiva Isolada por Elemento (4 Resoluções) + Auto-Reflow de Grid:** Oculte elementos individualmente em **Desktop (`100%`)**, **Laptop (`1024px`)**, **Tablet (`768px`)** e **Mobile (`375px`)** sem jamais ocultar a seção inteira. Quando todos os elementos de uma coluna ficam ocultos em uma resolução, o `LayoutGridRenderer` colapsa a coluna vazia e recalcula automaticamente o `grid-template-columns` para as colunas restantes (`1fr 2fr 1fr` $\rightarrow$ `2fr 1fr` ou `1fr`).
- **14 Blocos Semânticos, Design Tokens Globais & Vinculação Direta a Templates:** Componha *Headers*, *Footers* e *Seções Centrais* vinculadas diretamente a templates do tema (`home.php`, `sobre.php`, etc.) ou via shortcode `[section id="..."]`, com fallback 100% seguro para `partials/header.php` e `partials/footer.php`. Saiba mais em [wiki/Flex-Builder.md](wiki/Flex-Builder.md).

### 🎓 5. Academy LMS Híbrido, Fábrica Automática de Arquivos PHP & Simulador Multi-Dispositivos
O ecossistema conta com o **Academy (Cursos)** em arquitetura 100% SOLID:
- **Fábrica Automática de Pastas e Aulas PHP (`LessonFileScaffolderInterface`):** Crie pastas físicas de módulos (`📁 Criar Pasta`) e fabrique automaticamente arquivos `.php` pré-configurados com CSS e JS responsivos (Mobile, Tablet e Desktop) a partir do título da aula. Exemplo: ao salvar a aula **"Estrutura de Dados"**, o sistema gera automaticamente `pages/estrutura-de-dados.php` e disponibiliza a rota amigável `/cursos/estrutura-de-dados`.
- **Modo Híbrido de Aulas:** Suporta **Editor em Blocos Estruturado** (`header`, `paragraph`, `video`, `code`, `callout`, `list`, `image`, `delimiter`), **Arquivos PHP Fabricados/Tema** (`ThemePageResolverInterface`) e **Importação de Pasta em 1 Clique**.
- **Simulador Multi-Dispositivos & 3 Perfis de Layout Mobile:** Alterne entre `🖥️ Desktop` e `📱 Mobile` com **5 tamanhos de dispositivos padrão** (`320×568`, `375×667`, `430×932`, `768×1024`, `1024×768`) e escolha entre **3 Perfis Mobile** por curso/aula: **Gaveta Deslizante (`drawer`)**, **Barra Inferior Fixa (`bottom_bar`)** e **Modo Cinema 16:9 Sticky (`cinema`)**.

### 🤖 6. "Vibe Coding sobre Trilhos" (DX Oficial para Inteligência Artificial)
O Domain System foi preparado para a era do desenvolvimento acelerado por IA sem permitir código espaguete:
- **Documento Mestre Universal ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md))** + regras nativas (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`): qualquer IA (ChatGPT, Claude, DeepSeek, Gemini, Copilot, Cursor, Kilo Code) lê as **10 Leis do Ring 3** e gera plugins, aulas e temas 100% tipados e aderentes aos contratos SOLID.
- **Gerador de Boilerplate (`os-cli.php make:plugin <nome>`):** Cria instantaneamente toda a estrutura de contratos (`Plugin.php`, `Contracts/`, `Controllers/`, `views/`, `manual/module.php`).

### 📖 7. Auto-Descoberta de Manuais por Contrato (`ManualModuleInterface`)
Qualquer plugin em `src/Plugins/<slug>/manual/module.php` que implemente a interface `ManualModuleInterface` é rastreado e injetado automaticamente pelo `PluginManualScanner` dentro do **Manual de Instruções & QA do Cockpit (`/admin/manual` e `/manual`)**, adicionando sua documentação interativa e seus itens de checklist de homologação sem acoplamento com o Kernel.

### 🎨 8. Administrador de Conteúdo, SEO e Segurança Anti-CSRF / ACL
Gerencie páginas web (CMS), Menus Dinâmicos Atômicos, SEO OpenGraph/Schema.org e Controle de Acesso baseado em **Capabilities** com proteção nativa Anti-CSRF, Anti-XSS (whitelist positiva de URLs e sanitização XML de SVG via `DOMDocument`) e suporte a 2FA.

### 🔄 9. Atualizador Automático via GitHub Releases por Contrato (`SystemUpdater` — Modelo A)
Notificação automática de novas versões (painel, widget e sino), download em streaming com validação dupla SHA-256 + `manifest.json`, pré-checagem, backup automático de código e banco (MySQL/SQLite com fallback `\PharData` para ambientes Windows/XAMPP), aplicação atômica preservando `.env`/`config/`/`storage/`, execução de migrations verificadas por contrato (`SchemaVerifier`) e rollback automático em 1 clique ou via CLI (`php os-cli.php system:update`). Saiba mais em [wiki/Atualizacoes.md](wiki/Atualizacoes.md).

## ⚙️ Como Instalar

1. **Clone o repositório** para a pasta pública do seu servidor Apache/Nginx (ex: `htdocs` ou `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **Permissões (Linux/Mac):** Certifique-se de que a pasta tenha permissões de escrita para o servidor web.
   ```bash
   chmod -R 755 domain-system
   ```

3. **Inicie o Instalador:** Acesse a pasta do projeto pelo seu navegador (ex: `http://localhost/domain-system`). Você será redirecionado para o **Assistente de Instalação (Setup)**.

4. **Siga os Passos:** Insira os dados do seu banco de dados (MySQL/MariaDB ou SQLite via PDO). Crie seu usuário Master, e pronto. O Setup se auto-destruirá após a configuração por questões de segurança.

## 👨‍💻 Para Desenvolvedores & IAs (SDK)

O sistema possui uma engine de **Hooks (Event-Driven)** e **Contratos (`src/Core/Contracts`)** validada por **433 testes automatizados (2.340+ asserções)** no PHPUnit.
Para criar seu próprio módulo com ou sem auxílio de IA, consulte:
- [Guia de Desenvolvimento de Plugins (Humanos)](DEVELOPER_GUIDE.md)
- [Guia Oficial de Contratos para IAs (`AI_CONTRACT_GUIDE.md`)](AI_CONTRACT_GUIDE.md)
