> **ATENÇÃO ARQUITETURAL / PARADIGM SHIFT (2026-10-04)**
> 
> O Domain System **NÃO** é mais um sistema Sistema Operacional Web. Ele evoluiu para se tornar um **Sistema Operacional Web (Web OS) independente**.
> 
> No passado, o núcleo foi desenhado focado em separar ambientes de SaaS, mas esse acoplamento limitava o projeto. Hoje, o Domain System funciona como um Sistema Operacional puro (como Linux/Windows), que pode ser instalado em uma máquina ou container.
> Se for desejado que ele preste serviços Multi-Tenant, isso deverá ser feito virtualmente ou através de um aplicativo (Plugin Ring 3) construído especificamente para isso, **sem afetar o Micro-Kernel (Ring 0)**.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.0.0-blueviolet?style=for-the-badge" alt="Version 2.0.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~26MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>⚙️ Domain System OS (v2.0)</h1>
  <p><strong>Um Sistema Operacional Web de 26MB. Uma alternativa limpa, hiper-resiliente e estruturada em SOLID para substituir gigantes como WordPress e frameworks monolíticos.</strong></p>
</div>

---

[🇺🇸 Read in English](README-en.md) | [📚 Documentação & Tutoriais]( https://hadelrachid.github.io/domain-system/) | [🔍 Auditoria Arquitetural](docs/auditoria.md)

## 🧠 O Que é o Domain System?

O **Domain System** não é apenas mais um CMS ou Framework de prateleira. Ele nasceu com o objetivo ambicioso de ser uma alternativa leve e poderosa ao ecossistema do WordPress e do Laravel, ocupando atualmente apenas **~26.2 MB** de espaço no servidor.

Construído sob a rigorosa cartilha do **SOLID** e utilizando puramente **Injeção de Dependências**, ele se comporta como um **Verdadeiro Sistema Operacional Web**.
O limite é a sua imaginação:
- O **Núcleo (Micro-Kernel)** fornece a infraestrutura de baixo nível, completamente agnóstica: Conexão com Banco de Dados, Roteamento Dinâmico, Telemetria, Segurança (Anti-CSRF, Gatekeeper) e Gerenciamento de Processos (PIDs).
- Toda a **lógica de negócio** é injetada através de **Módulos (Ring 0 / Ring 3)**. O núcleo nunca sabe o que a aplicação faz, ele apenas orquestra o ciclo de vida!

## ⚡ Principais Diferenciais

### 🛡️ 1. Arquitetura Ring 0 / Ring 3 (Privilégios Isolados)
Inspirado em Sistemas Operacionais reais (como Linux/Windows), o sistema isola suas extensões em dois anéis de privilégios. **SystemApps (Ring 0)** são componentes vitais do sistema (ex: Banco de Dados, Auth, Admin) que não podem ser excluídos ou desativados. **UserPlugins (Ring 3)** são adições de terceiros que rodam com privilégios limitados e são monitorados estritamente.

### 🛡️ 2. Gatekeeper (O Guardião de Instalação)
Proteção total contra ZIPs maliciosos. O instalador não confia cegamente em uploads; ele extrai o pacote em uma área de quarentena, escaneia o código fonte (procurando por vulnerabilidades de Path Traversal/Zip-Slip) e impede qualquer tentativa de Escalação de Privilégios (ex: plugins tentando usurpar o namespace do Ring 0).

### 🧯 3. No-Break Shield (O Disjuntor Imortal)
Esqueça a "Tela Branca da Morte" (WSOD). O Domain System roda com o **No-Break Shield**: um *Circuit Breaker* em nível de Kernel que intercepta falhas fatais geradas por plugins malfeitos. Se um plugin do Ring 3 causa um erro sintático ou de lógica grave, ele é ejetado da memória em tempo de execução, e o restante do painel continua vivo.

### 🏗️ 4. Builder Flex Integrado e Hub de Temas
O sistema de temas não é apenas para carregar CSS. A Engine FlexTheme atua como um hub visual poderoso, integrando o **Builder Flex**, um construtor de páginas drag-and-drop limpo e modularizado que se comunica diretamente com a API do SO.

### 🔐 5. Segurança Anti-CSRF e 2FA (TOTP)
O núcleo foi auditado por inteligências artificiais para bloquear ataques CSRF com validação forçada em todas as requisições (incluindo rotas via API). Além disso, o módulo nativo de Auth inclui suporte ao Google Authenticator, blindando o acesso administrativo.

## 🚀 Como Instalar

1. **Clone o repositório** para a pasta pública do seu servidor Apache/Nginx (ex: `htdocs` ou `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **Permissões (Linux/Mac):** Certifique-se de que a pasta tenha permissões de escrita para o servidor web.
   ```bash
   chmod -R 755 domain-system
   ```

3. **Inicie o Instalador:** Acesse a pasta do projeto pelo seu navegador (ex: `http://localhost/domain-system`). Você será redirecionado para o **Assistente de Instalação (Setup)**.

4. **Siga os Passos:** Insira os dados do seu banco de dados MySQL ou escolha SQLite. Crie seu usuário Master, e pronto. O Setup se auto-destruirá após a configuração por questões de segurança.

## 🛠️ Para Desenvolvedores (SDK)

O SO possui um sistema massivo de **Hooks (Event-Driven)** para você alterar o fluxo do sistema sem encostar no código-fonte do Kernel.
A arquitetura foi inteiramente varrida para remover Anti-Patterns (como `Service Locator` / `Application::getInstance()`), forçando injeções de dependência declarativas e interfaces puras.
Para criar o seu próprio componente, consulte o nosso [Guia de Desenvolvimento de Plugins](DEVELOPER_GUIDE.md).

