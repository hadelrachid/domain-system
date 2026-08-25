# 🏥 Domain System Clínica (Modular)
> **Versão Atual:** `[v1.1.0]`

[ 📄 Documentação Principal ](README.md) | [ 📜 Histórico de Versões (Changelog) ](CHANGELOG.md) | [ 🕵️ Relatório de Auditoria ](auditoria.md)
---

Um sistema de gestão de clínicas desenvolvido com uma arquitetura monolítica modular, desenhado para ser hiper-resiliente, altamente expansível e seguro.

## 🏗️ Arquitetura do Sistema

Este projeto abandona a tradicional "sopa de espaguete" e adota o padrão de **Kernel e Plugins** (inspirado em arquiteturas corporativas e CMSs de ponta), com forte aderência aos princípios **SOLID**.

### 1. O Kernel e o PluginManager
O núcleo do sistema (Kernel) apenas fornece a infraestrutura básica (Banco de Dados, Motor de Templates, Injeção de Dependências e Sistema de Rotas). Todo o restante (Autenticação, Pacientes, Prontuários) são **Plugins** independentes.
- Os plugins se comunicam entre si exclusivamente via `EventDispatcher` (Hooks).
- Se um plugin precisa adicionar um menu, ele não altera o painel, ele simplesmente escuta o evento `admin.menu` e injeta sua opção.

### 2. Disjuntor V2 (Circuit Breaker)
Inspirado em microsserviços modernos, o sistema possui um **Circuit Breaker** nativo. 
Se um plugin for ativado e seu código contiver erros (ex: Erros de Sintaxe, Chamadas Indevidas, Falha de Banco de Dados), o Kernel captura o erro, isola o módulo defeituoso e desliga-o do `plugins.json` automaticamente. O sistema inteiro sobrevive e continua operando sem a "tela branca da morte".

### 3. O "QTA" (Automatic Transfer Switch)
Mesmo contra as falhas mais catastróficas (como o esgotamento total da memória RAM por um loop infinito), o sistema está protegido.
Uma função `register_shutdown_function` (O Último Suspiro) monitora a inicialização dos módulos. Se o servidor morrer repentinamente por asfixia de recursos (Fatal Error E_ERROR), o QTA ejetará cirurgicamente o plugin causador da fiação mestre nos últimos milissegundos de vida. Ao recarregar a página, o sistema ressuscitará e deixará um log de emergência.

### 4. Tomadas e Plugues (Inversão de Dependências)
O sistema foi construído pensando na filosofia da "Tomada e do Plugue". 
Por exemplo, no módulo de Autenticação, o processo de Verificação em 2 Etapas (2FA) não sabe como enviar e-mails ou como ler aplicativos. Ele apenas fornece uma interface (`TwoFactorProviderInterface`). Outros plugins (ou classes independentes) fornecem os plugues (`EmailProvider`, `AppProvider`).
Isso permite que um desenvolvedor crie um plugin de "WhatsApp" amanhã e adicione a função 2FA ao sistema de Login sem alterar uma única linha do código central!

### 5. Ambiente de Desenvolvimento Seguro (Dev Simulator)
Para evitar que e-mails falsos vazem em testes, o sistema possui um plugin `dev_simulator`. Quando ativado, ele intercepta as classes de comunicação (sequestrando a fiação via Injeção de Dependência) e redireciona os envios para um arquivo de texto local (`temp/auth-2fa.txt`). Em produção, basta desligar o plugin e a fiação volta ao estado natural.

## 🛠️ Tecnologias
- **Linguagem:** PHP 8+ (Vanilla/OOP avançado)
- **Banco de Dados:** SQLite (com PDO e QueryBuilder customizado)
- **Frontend:** HTML5, CSS Nativo (Arquitetura limpa sem frameworks pesados)
- **Design Patterns Utilizados:** Dependency Injection, Event Dispatcher, Circuit Breaker, Strategy, Adapter, Singleton.

## 👥 Controle de Acesso (ACL)
O sistema isola perfis:
- **Administrador:** Acesso mestre e global.
- **Médico:** Acesso restrito apenas a agenda, consultas e prontuários médicos.
- **Recepcionista:** Não pode visualizar prontuários (conforme LGPD), operando apenas agendamentos e triagem básica.
- **Jurídico:** Workspace isolado com relatórios confidenciais.

---
*Este sistema é um organismo vivo, programado para sobreviver a si mesmo e se expandir organicamente.*

## 📖 Versionamento e Histórico
Atualmente o projeto encontra-se na versão **[1.1.0]**.
Para acompanhar toda a evolução e correções, consulte o nosso [CHANGELOG.md](CHANGELOG.md).

## 🤝 Colaboradores Principais
Este sistema é construído por uma equipe mista de inteligência humana e artificial:

- **Rachid** - Engenheiro de Software (Criador, Idealizador da Arquitetura e Product Owner)
- **Antigravity (Google DeepMind)** - Arquiteto I.A. (Co-desenvolvedor e Auditor de Código)
