# 🚀 Domain System (Universal Modular Framework)
> **Versão Atual:** `[v1.1.0]`

[ 🌐 Site Oficial (Docs) ](https://hadelrachid.github.io/domain-system/) | [ 📄 Documentação ](README.md) | [ 📜 Changelog ](docs/CHANGELOG.md) | [ 🕵️ Auditoria ](docs/auditoria.md)
---
[ 🇺🇸 Read in English ](README-en.md)

Um motor (framework) arquitetural hiper-resiliente, desenhado para ser infinitamente expansível. Semelhante ao conceito do WordPress, o Domain System não é apenas um software específico — ele se transforma em **qualquer coisa** dependendo dos plugins ativados.
Pode ser um **Sistema de Clínica Médica**, um **ERP Financeiro** ou um **Sistema Jurídico para Escritórios de Advocacia**. Tudo depende do pacote de plugins conectados ao núcleo.

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

### 4. Tomadas, Plugues e Repositórios (DIP e Repository Pattern)
O sistema foi construído pensando na filosofia da "Tomada e do Plugue" (SOLID).
No Autenticador, por exemplo, o 2FA apenas define a interface (`TwoFactorProviderInterface`). Outros módulos fornecem a execução (E-mail, App).
Mais profundo ainda, em nossa camada de acesso a dados, utilizamos fortemente o **Repository Pattern**. Controladores de módulos vitais (Pacientes, Médicos, Prontuários) ignoram a existência de um banco de dados e conversam apenas com interfaces de Repositórios. Isso elimina o Acoplamento Estrutural e os famosos "Fat Controllers", delegando a busca de dados a um injetor de dependência (DI Container).

### 5. Ambiente de Desenvolvimento Seguro (Dev Simulator)
Para evitar que e-mails falsos vazem em testes, o sistema possui um plugin `dev_simulator`. Quando ativado, ele intercepta as classes de comunicação (sequestrando a fiação via Injeção de Dependência) e redireciona os envios para um arquivo de texto local (`temp/auth-2fa.txt`). Em produção, basta desligar o plugin e a fiação volta ao estado natural.

## 🛠️ Tecnologias
- **Linguagem:** PHP 8+ (Vanilla/OOP avançado)
- **Banco de Dados:** SQLite (com PDO e QueryBuilder customizado encapsulado em Repositórios)
- **Frontend:** HTML5, CSS Nativo (Arquitetura limpa sem frameworks pesados)
- **Design Patterns Utilizados:** Dependency Injection, Event Dispatcher, Circuit Breaker, Strategy, Adapter, Singleton, Repository Pattern.

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
Para acompanhar toda a evolução e correções, consulte o nosso [CHANGELOG.md](https://github.com/hadelrachid/domain-system/blob/main/docs/CHANGELOG.md).

## 🤝 Colaboradores Principais
Este sistema é construído por uma equipe mista de inteligência humana e artificial:

- **Rachid** - Engenheiro de Software (Criador, Idealizador da Arquitetura e Product Owner)
- **Antigravity (Google DeepMind)** - Arquiteto I.A. (Co-desenvolvedor e Auditor de Código)
