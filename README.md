> **ALINHAMENTO ARQUITETURAL / PARADIGM SHIFT (2026-10-07)**
> 
> O Domain System evoluiu sua identidade conceitual. Ele Ã© uma **Plataforma de ExecuÃ§Ã£o de AplicaÃ§Ãµes Web** e um **Framework de Ecossistema**. 
> Ele atua primariamente como um **Administrador de PÃ¡ginas da Web (CMS AvanÃ§ado)** e um **Orquestrador de ServiÃ§os e APIs**. 
> Embora ele adote a *arquitetura e o jargÃ£o* de Sistemas Operacionais (Micro-Kernel, AnÃ©is de ProteÃ§Ã£o - Rings, Isolamento de Processos, IPC), ele nÃ£o Ã© um OS bare-metal (como Linux/Windows), mas sim um **Ambiente Operacional Web (Web Operating Environment)** que roda no topo da stack PHP/Linux.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.1.0-blueviolet?style=for-the-badge" alt="Version 2.1.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~26MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>ð¸ Domain System (v2.1)</h1>
  <p><strong>Uma Plataforma Web hiper-resiliente e estruturada em SOLID para substituir gigantes como WordPress e frameworks monolÃ­ticos.</strong></p>
</div>

---

[ðºð¸ Read in English](README-en.md) | [ð DocumentaÃ§Ã£o & Tutoriais]( https://hadelrachid.github.io/domain-system/) | [ð¡ï¸ Auditoria Arquitetural](docs/auditoria.md)

## ð§  O Que Ã© o Domain System?

O **Domain System** nÃ£o Ã© apenas mais um CMS ou Framework de prateleira. Ele nasceu com o objetivo ambicioso de ser uma alternativa leve e blindada ao ecossistema do WordPress e do Laravel, ocupando atualmente apenas **~26.2 MB** de espaÃ§o no servidor.

ConstruÃ­do sob a rigorosa cartilha do **SOLID** e utilizando puramente **InjeÃ§Ã£o de DependÃªncias**, ele se comporta como um **Ambiente Operacional Web**.
O limite Ã© a sua imaginaÃ§Ã£o:
- O **NÃºcleo (Micro-Kernel)** fornece a infraestrutura de baixo nÃ­vel, completamente agnÃ³stica: ConexÃ£o com Banco de Dados, Roteamento DinÃ¢mico, Telemetria, SeguranÃ§a Baseada em Capabilities (ACL, Anti-CSRF) e Gerenciamento do Ciclo de Vida.
- Toda a **lÃ³gica de negÃ³cio** e **gestÃ£o de pÃ¡ginas/serviÃ§os** Ã© injetada atravÃ©s de **MÃ³dulos**. O nÃºcleo nunca sabe o que a aplicaÃ§Ã£o faz, ele apenas orquestra os eventos!

## ð Principais Diferenciais

### ð¡ï¸ 1. Arquitetura Ring 0 / Ring 3 (PrivilÃ©gios Isolados)
Inspirado na seguranÃ§a de Sistemas Operacionais reais, o sistema isola suas extensÃµes em dois anÃ©is de privilÃ©gios. **SystemApps (Ring 0)** sÃ£o componentes vitais do sistema (ex: Banco de Dados, Identidade/Auth, Painel Admin) que gerenciam serviÃ§os essenciais. **UserPlugins (Ring 3)** sÃ£o adiÃ§Ãµes de terceiros que rodam com privilÃ©gios limitados e sÃ£o impedidos de injetar ou sequestrar instÃ¢ncias do nÃºcleo.

### ð¡ï¸ 2. Gatekeeper (O GuardiÃ£o de InstalaÃ§Ã£o)
ProteÃ§Ã£o total contra ZIPs maliciosos. O instalador nÃ£o confia cegamente em uploads; ele extrai o pacote em uma Ã¡rea de quarentena, escaneia o cÃ³digo fonte (procurando por vulnerabilidades de Path Traversal/Zip-Slip) e impede qualquer tentativa de EscalaÃ§Ã£o de PrivilÃ©gios (ex: plugins tentando usurpar o namespace do Ring 0).

### ð 3. No-Break Shield (O Disjuntor Imortal)
EsqueÃ§a a "Tela Branca da Morte" (WSOD). O Domain System roda com o **No-Break Shield**: um *Circuit Breaker* centralizado que intercepta falhas fatais geradas por plugins malfeitos. Se um plugin do Ring 3 causa um erro sintÃ©tico ou de lÃ³gica grave, ele Ã© isolado, e o restante do painel continua vivo.

### ð¨ 4. Administrador de ConteÃºdo e ServiÃ§os
O sistema possui a flexibilidade para gerenciar pÃ¡ginas web (CMS) usando o **Builder Flex** integrado, alÃ©m de fornecer rotas para APIs RESTful, permitindo que a plataforma opere nÃ£o apenas como um site, mas como o back-end de serviÃ§os modernos.

### ð 5. SeguranÃ§a Anti-CSRF e Controle de Acessos (ACL)
O nÃºcleo foi rigorosamente auditado para bloquear ataques CSRF e Cross-Site Scripting (XSS). O gerenciamento de Identidade Ã© baseado num motor robusto de **Capability Security**, onde os PrivilÃ©gios sÃ£o interceptados no Middleware antes da execuÃ§Ã£o. Suporte nativo a 2FA (Google Authenticator) protege a camada administrativa.

## âï¸ Como Instalar

1. **Clone o repositÃ³rio** para a pasta pÃºblica do seu servidor Apache/Nginx (ex: `htdocs` ou `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **PermissÃµes (Linux/Mac):** Certifique-se de que a pasta tenha permissÃµes de escrita para o servidor web.
   ```bash
   chmod -R 755 domain-system
   ```

3. **Inicie o Instalador:** Acesse a pasta do projeto pelo seu navegador (ex: `http://localhost/domain-system`). VocÃª serÃ¡ redirecionado para o **Assistente de InstalaÃ§Ã£o (Setup)**.

4. **Siga os Passos:** Insira os dados do seu banco de dados (MySQL nativamente suportado via PDO) ou escolha SQLite. Crie seu usuÃ¡rio Master, e pronto. O Setup se auto-destruirÃ¡ apÃ³s a configuraÃ§Ã£o por questÃµes de seguranÃ§a.

## ð¨âð» Para Desenvolvedores (SDK)

O sistema possui uma engine massiva de **Hooks (Event-Driven)** para vocÃª alterar o fluxo sem encostar no cÃ³digo-fonte do Kernel.
A arquitetura foi inteiramente varrida para remover Anti-Patterns (como `Service Locator`), forÃ§ando injeÃ§Ãµes de dependÃªncia declarativas e interfaces puras.
Para criar o seu prÃ³prio componente, consulte o nosso [Guia de Desenvolvimento de Plugins](DEVELOPER_GUIDE.md).
