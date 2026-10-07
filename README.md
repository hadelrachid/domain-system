> **ALINHAMENTO ARQUITETURAL / PARADIGM SHIFT (2026-10-07)**
> 
> O Domain System evoluiu sua identidade conceitual. Ele é uma **Plataforma de Execução de Aplicações Web** e um **Framework de Ecossistema**. 
> Ele atua primariamente como um **Administrador de Páginas da Web (CMS Avançado)** e um **Orquestrador de Serviços e APIs**. 
> Embora ele adote a *arquitetura e o jargão* de Sistemas Operacionais (Micro-Kernel, Anéis de Proteção - Rings, Isolamento de Processos, IPC), ele não é um OS bare-metal (como Linux/Windows), mas sim um **Ambiente Operacional Web (Web Operating Environment)** que roda no topo da stack PHP/Linux.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.1.0-blueviolet?style=for-the-badge" alt="Version 2.1.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~26MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>🛸 Domain System (v2.1)</h1>
  <p><strong>Uma Plataforma Web hiper-resiliente e estruturada em SOLID para substituir gigantes como WordPress e frameworks monolíticos.</strong></p>
</div>

---

[🇺🇸 Read in English](README-en.md) | [📖 Documentação & Tutoriais]( https://hadelrachid.github.io/domain-system/) | [🛡️ Auditoria Arquitetural](docs/auditoria.md)

## 🧠 O Que é o Domain System?

O **Domain System** não é apenas mais um CMS ou Framework de prateleira. Ele nasceu com o objetivo ambicioso de ser uma alternativa leve e blindada ao ecossistema do WordPress e do Laravel, ocupando atualmente apenas **~26.2 MB** de espaço no servidor.

Construído sob a rigorosa cartilha do **SOLID** e utilizando puramente **Injeção de Dependências**, ele se comporta como um **Ambiente Operacional Web**.
O limite é a sua imaginação:
- O **Núcleo (Micro-Kernel)** fornece a infraestrutura de baixo nível, completamente agnóstica: Conexão com Banco de Dados, Roteamento Dinâmico, Telemetria, Segurança Baseada em Capabilities (ACL, Anti-CSRF) e Gerenciamento do Ciclo de Vida.
- Toda a **lógica de negócio** e **gestão de páginas/serviços** é injetada através de **Módulos**. O núcleo nunca sabe o que a aplicação faz, ele apenas orquestra os eventos!

## 🚀 Principais Diferenciais

### 🛡️ 1. Arquitetura Ring 0 / Ring 3 (Privilégios Isolados)
Inspirado na segurança de Sistemas Operacionais reais, o sistema isola suas extensões em dois anéis de privilégios. **SystemApps (Ring 0)** são componentes vitais do sistema (ex: Banco de Dados, Identidade/Auth, Painel Admin) que gerenciam serviços essenciais. **UserPlugins (Ring 3)** são adições de terceiros que rodam com privilégios limitados e são impedidos de injetar ou sequestrar instâncias do núcleo.

### 🛡️ 2. Gatekeeper (O Guardião de Instalação)
Proteção total contra ZIPs maliciosos. O instalador não confia cegamente em uploads; ele extrai o pacote em uma área de quarentena, escaneia o código fonte (procurando por vulnerabilidades de Path Traversal/Zip-Slip) e impede qualquer tentativa de Escalação de Privilégios (ex: plugins tentando usurpar o namespace do Ring 0).

### 🔋 3. No-Break Shield (O Disjuntor Imortal)
Esqueça a "Tela Branca da Morte" (WSOD). O Domain System roda com o **No-Break Shield**: um *Circuit Breaker* centralizado que intercepta falhas fatais geradas por plugins malfeitos. Se um plugin do Ring 3 causa um erro sintético ou de lógica grave, ele é isolado, e o restante do painel continua vivo.

### 🎨 4. Administrador de Conteúdo e Serviços
O sistema possui a flexibilidade para gerenciar páginas web (CMS) usando o **Builder Flex** integrado, além de fornecer rotas para APIs RESTful, permitindo que a plataforma opere não apenas como um site, mas como o back-end de serviços modernos.

### 🔐 5. Segurança Anti-CSRF e Controle de Acessos (ACL)
O núcleo foi rigorosamente auditado para bloquear ataques CSRF e Cross-Site Scripting (XSS). O gerenciamento de Identidade é baseado num motor robusto de **Capability Security**, onde os Privilégios são interceptados no Middleware antes da execução. Suporte nativo a 2FA (Google Authenticator) protege a camada administrativa.

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

4. **Siga os Passos:** Insira os dados do seu banco de dados (MySQL nativamente suportado via PDO) ou escolha SQLite. Crie seu usuário Master, e pronto. O Setup se auto-destruirá após a configuração por questões de segurança.

## 👨‍💻 Para Desenvolvedores (SDK)

O sistema possui uma engine massiva de **Hooks (Event-Driven)** para você alterar o fluxo sem encostar no código-fonte do Kernel.
A arquitetura foi inteiramente varrida para remover Anti-Patterns (como `Service Locator`), forçando injeções de dependência declarativas e interfaces puras.
Para criar o seu próprio componente, consulte o nosso [Guia de Desenvolvimento de Plugins](DEVELOPER_GUIDE.md).
