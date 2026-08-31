# 🚀 Domain System — O Motor de Negócios que não Morre

> **Versão Atual:** `v1.2.0` — *"A Era da Resiliência Arquitetural"*

[🌐 Site Oficial (Docs)](https://hadelrachid.github.io/domain-system/) | [📄 Documentação](README.md) | [📜 Changelog](docs/CHANGELOG.md) | [🕵️ Auditoria](docs/auditoria.md)

---

[🇺🇸 Read in English](README-en.md)

## 💡 O Que é o Domain System?

O **Domain System** não é apenas mais um framework ou um sistema de gestão. É um **motor de negócios hiper-resiliente**, projetado para ser a base de qualquer aplicação empresarial, desde uma clínica médica até um escritório de advocacia ou um ERP financeiro.

Ele funciona como um **sistema operacional para o seu negócio**: o núcleo (Kernel) fornece a infraestrutura essencial (banco de dados, segurança, rotas, injeção de dependências), e toda a lógica de negócio é encapsulada em **Plugins** independentes e intercambiáveis.

Este é um projeto que une o melhor dos dois mundos: a **simplicidade e performance do PHP puro** com a **robustez e escalabilidade de arquiteturas enterprise** (como microsserviços e SOLID).

---

## 🏗️ Arquitetura: Uma Obra de Engenharia

A arquitetura do Domain System foi construída para ser **imune ao caos**. Ela é baseada em três pilares fundamentais:

### 1. O Kernel Imortal (Core)
O coração do sistema é minimalista e não contém nenhuma regra de negócio. Sua única função é orquestrar plugins e fornecer ferramentas seguras (Container DI, Event Dispatcher, Router, Session Manager). O Kernel é a "tomada" universal onde qualquer plugin pode se conectar. Ele atua como **Gatekeeper Global**, gerenciando a memória e validando rotas de forma antecipada sem que os Controllers saibam como a segurança é feita.

### 2. O Ecossistema de Plugins (Módulos)
Cada funcionalidade — desde autenticação até faturamento — é um Plugin isolado. Plugins se comunicam **exclusivamente via eventos** (Event-Driven Architecture), garantindo que a quebra de um não comprometa o todo. Eles são a "carga útil" que transforma o Kernel em um sistema de clínica, jurídico ou financeiro. Tudo funciona através de injeção de dependência e desacoplamento forte (DIP e SRP).

### 3. A Camada de Apresentação (Temas e Cockpits)
A interface do usuário é totalmente desacoplada. Temas (frontend) consomem dados dos Plugins via **Shortcodes** e um sistema de **Workspaces** (perfis de usuário). Isso permite que designers e desenvolvedores frontend trabalhem de forma independente, sem nunca tocar na lógica de negócio.

---

## 🛡️ Resiliência: O Sistema que não Morre

Falhas acontecem. No Domain System, elas são **contidas, registradas e corrigidas sem derrubar o sistema**.

- **Circuit Breaker (Disjuntor V2):** Se um plugin causar um erro fatal (sintaxe, banco de dados, memória), o Kernel o desativa automaticamente e registra o incidente. O resto do sistema continua funcionando.
- **Emergency Hatch (Escotilha de Emergência):** Se o módulo de autenticação falhar, os administradores ainda podem acessar o sistema via uma rota de emergência segura, usando a `APP_KEY` do servidor.
- **Cascade Effect (Efeito Cascata):** Se um plugin A depende de um plugin B, e B é desativado pelo Circuit Breaker, A também é desativado para evitar falhas em cadeia.

---

## ✨ Principais Características

- **100% PHP 8+** — Código moderno, limpo e orientado a objetos.
- **Arquitetura SOLID** — Cada classe tem uma responsabilidade única e bem definida.
- **Event-Driven** — Plugins se comunicam via eventos, garantindo baixo acoplamento.
- **Dependency Injection** — Container de injeção de dependências com autowiring.
- **Repository Pattern** — Controllers nunca tocam no banco de dados diretamente.
- **Multi-Workspace** — Suporte nativo para múltiplos perfis de usuário (Admin, Médico, Recepcionista, Advogado).
- **Auditoria e Monitoramento** — Painel de supervisão de erros com logs detalhados e botão de "copiar stack trace".
- **AI Hub Integrado** — Gerador de plugins e formulários via Inteligência Artificial (Gemini, ChatGPT, etc.).
- **Modular e Extensível** — Adicione ou remova funcionalidades sem afetar o núcleo.

---

## 🧩 Para Quem é este Projeto?

- **Clínicas e Consultórios** — Gerencie pacientes, agendamentos, prontuários e faturamento.
- **Escritórios de Advocacia** — Controle processos, prazos e clientes com um workspace dedicado.
- **Desenvolvedores Corporativos** — Construa soluções white-label rapidamente, sem reinventar a roda.
- **Arquitetos de Software** — Um laboratório vivo de boas práticas (SOLID, DDD, Event Sourcing).

---

## 🛠️ Tecnologias Utilizadas

| Camada          | Tecnologia                                  |
|-----------------|---------------------------------------------|
| **Backend**     | PHP 8+ (Vanilla, OOP Avançado)              |
| **Database**    | SQLite (com suporte nativo para PostgreSQL) |
| **Frontend**    | HTML5, CSS3, JavaScript (Vanilla)           |
| **Design Patterns** | DI, Event Dispatcher, Repository, Strategy, Adapter, Factory, Circuit Breaker |

---

## 📖 Documentação e Recursos

- **[Guia do Desenvolvedor](DEVELOPER_GUIDE.md)** — Aprenda a criar plugins e temas do zero.
- **[Wiki do Projeto](wiki/Home.md)** — Conceitos avançados como Circuit Breaker, Emergency Hatch e ACL.
- **[Changelog](docs/CHANGELOG.md)** — Histórico completo de versões e correções.
- **[Roadmap](ROADMAP.md)** — O futuro do Domain System.

---

## 🤝 Colaboradores

Este projeto é construído por uma equipe mista de inteligência humana e artificial:

- **Rachid Hadel** — Engenheiro de Software, Arquiteto e Product Owner.
- **Antigravity (Google DeepMind)** — Co-Desenvolvedor, Arquiteto de IA e Auditor de Código.

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).
## 🌌 O Multiverso de Temas
O CockPit vive em um autêntico multiverso. Leia sobre a nossa arquitetura Multi-Theme e como hospedamos inúmeros visuais (Mundo Público, Médico, Kiosk) rodando isolados e simultaneamente na mesma aplicação acessando [docs/multiverse.md](docs/multiverse.md).
