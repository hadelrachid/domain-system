# 📚 Wiki Oficial do Domain System (v2.7.0)

> 🇧🇷 [Ler em Português](#-visão-geral-e-boas-vindas) | 🇺🇸 [Read in English](#-english-overview--welcome)  
> 🌐 **Documentação Online:** [https://hadelrachid.github.io/domain-system/](https://hadelrachid.github.io/domain-system/)  
> 💻 **Repositório GitHub:** [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

# 🇧🇷 Visão Geral e Boas-Vindas

Olá! Seja muito bem-vindo à Wiki oficial do **Domain System**.

Eu sou o Hadel, um desenvolvedor autodidata. Criei este projeto porque estava cansado da lentidão e complexidade de softwares que exigem dezenas de plugins e servidores caros para rodar uma loja ou uma escola online. 

O Domain System foi feito em **PHP 8.2 puro**, ocupa apenas **~30 MB de espaço em disco** e consome **~5 MB de memória RAM**, funcionando de forma rápida e estável até na hospedagem compartilhada mais barata.

---

## 🤝 O Papel da I.A. e o Chamado à Comunidade

Como programo a maior parte do tempo sozinho, utilizo modelos de Inteligência Artificial como **auditores diários de código de bancada**. Passo linha por linha com eles caçando falhas de concorrência, brechas de segurança e redundâncias. Hoje o sistema conta com mais de **650 testes automatizados no PHPUnit** e quase **4.000 asserções**.

No entanto, **I.A. tem limites e nunca vai substituir a experiência, o olhar crítico e o bom senso de desenvolvedores humanos reais**.

Esta Wiki e o repositório estão abertos para você:
- **Testar e auditar:** Tente quebrar o sistema, aponte falhas e nos ajude a blindar o código.
- **Simplificar a arquitetura:** Ajude a manter os contratos limpos e diretos, sem complicar o que deve ser simples.
- **Criar extensões:** Desenvolva novos plugins e temas visuais leves.

---

## 🚀 Guias de Início Rápido

Quer começar a programar ou criar algo novo no sistema? Siga nossos tutoriais passo a passo:

* 🧩 **[Como Criar um Plugin do Zero](Criando-Plugins.md)** — Crie seu primeiro módulo em 5 minutos com rotas, menu no admin e banco de dados.
* 🎨 **[Como Criar um Tema Visual](Criando-Temas.md)** — Construa um layout limpo, moderno e responsivo com PHP puro e HTML.
* 📦 **[DomainDrop (Dropshipping & AliExpress SDK)](DomainDrop.md)** — Como funciona a integração oficial com o AliExpress, cálculo de frete e rastreio.
* 🏗️ **[Flex Builder (Construtor Visual de Páginas)](Flex-Builder.md)** — Como funcionam os blocos visuais, grid responsivo e isolamento de temas.
* ⚡ **[Disjuntor (Circuit Breaker)](Disjuntor-Circuit-Breaker.md)** — Como o sistema isola plugins com erro para nunca derrubar sua loja em produção.
* 🔄 **[Atualizações e Deploy](Atualizacoes.md)** — Como funciona o atualizador com 1 clique e backups automáticos.
* 🛡️ **[Controle de Acesso Centralizado](Global-Gatekeeper.md)** — Como funcionam as permissões e segurança por capabilities.
* 🚨 **[Modo de Recuperação Administrativa](Emergency-Hatch.md)** — Procedimento de contingência para acesso seguro ao painel.

---

## 🏛️ A Estrutura em Camadas (Sem Complicação)

1. **Núcleo (`src/Core`):** Cuida apenas do essencial — roteamento de URLs, injeção de dependências e eventos (Hooks). Não possui regras de negócio.
2. **Aplicativos do Sistema (`src/SystemApps`):** Funções internas vitais (Login de Admin, Banco de Dados, Atualizador).
3. **Módulos e Recursos (`src/Plugins`):** Onde o negócio acontece:
   - `domain_drop`: Catálogo, mineração 24h e rastreio do AliExpress.
   - `commerce`: Checkout com Pix instantâneo, cartão e webhooks de afiliados.
   - `academy`: Gestão de cursos, aulas em vídeo, leitor de PDF e certificados.
   - `flextheme`: Motor de temas e editor visual.

---

<br>

# 🇺🇸 English Overview & Welcome

> 🌐 **Online Documentation:** [https://hadelrachid.github.io/domain-system/](https://hadelrachid.github.io/domain-system/)  
> 💻 **GitHub Repository:** [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

Welcome to the official **Domain System** Wiki!

I am Hadel, a self-taught software developer. I built Domain System because I was frustrated with bloated platforms requiring heavy frameworks, dozens of unvetted plugins, and expensive servers just to run an e-commerce store or an online school.

Domain System is built on pure **PHP 8.2+**, takes up only **~30 MB on disk**, and uses just **~5 MB of RAM** per request, running fast and smooth on any basic shared hosting or cheap VPS.

---

## 🤝 Craftsmanship, AI Auditing, and Open Collaboration

Working mostly on my own, I use modern AI models as **relentless daily code-auditing partners**. I review the codebase line by line alongside them, hunting down edge cases, concurrency pitfalls, and security vulnerabilities. Today, the project has over **650 automated tests in PHPUnit** with nearly **4,000 assertions**.

Yet, **AI has strict limits and can never replace the intuition, real-world experience, and sanity checks of experienced human engineers**.

This project is completely open for you to:
- **Audit and test:** Try to break it, find flaws, and help us make it rock-solid.
- **Refine architecture:** Help us keep explicit contracts clean, readable, and bloat-free.
- **Build extensions:** Create new payment gateways, shipping integrations, or lightweight visual themes.

---

## 🚀 Quick Start Guides

* 🧩 **[How to Build a Plugin from Scratch](Criando-Plugins.md)** — Step-by-step guide to building your first module in 5 minutes.
* 🎨 **[How to Build a Visual Theme](Criando-Temas.md)** — Create a modern, lightweight, responsive theme using clean PHP and HTML.
* 📦 **[DomainDrop (Dropshipping & AliExpress SDK)](DomainDrop.md)** — How the official AliExpress Open Platform integration works.
* 🏗️ **[Flex Builder (Visual Page Builder)](Flex-Builder.md)** — Semantic blocks, responsive grid, and theme isolation.
* ⚡ **[Circuit Breaker & Resilience](Disjuntor-Circuit-Breaker.md)** — How runtime failure isolation keeps your business online during errors.
* 🔄 **[Updates & Deployment](Atualizacoes.md)** — 1-click updates via GitHub Releases with automated rollbacks.
* 🛡️ **[Access Control & Security](Global-Gatekeeper.md)** — Capability-based permissions and route protection.
* 🚨 **[Emergency Recovery Mode](Emergency-Hatch.md)** — Administrative recovery workflows when access is impaired.
