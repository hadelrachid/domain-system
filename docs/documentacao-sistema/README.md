# Por Dentro do Domain System (v2.7.0)

> 🇧🇷 **Português** | [🇺🇸 English version](en/README.md)

> **"A arquitetura não é sobre o código que você escreve hoje. É sobre as conexões que você cria, a dor que você evita e o futuro que você constrói."**  
> *— Do livro ["Entendendo Arquitetura SOLID: Do Objeto ao Contrato, do Acoplamento à Arquitetura"](https://www.amazon.com.br/dp/B0H74L5GFL) (disponível na Amazon Kindle / KDP)*

---

Se você está começando agora na programação, nunca estudou "Padrões de Projeto" ou sempre achou as explicações sobre **SOLID** chatas e cheias de jargões acadêmicos (com exemplos de *Cachorro que late* e *Gato que mia*), **este guia foi escrito sob medida para você**.

Aqui, abrimos o capô do **Domain System** peça por peça usando a **física e a biologia do mundo real**:
- **A Tomada e o Plugue:** *"Primeiro a fêmea (contrato/interface), depois os machos (classes concretas/plugues)"*.
- **A Placa Arduino:** O Kernel não precisa saber se você conectou um LED ou um sensor; ele só conhece o padrão dos pinos.
- **A Colmeia da Natureza:** O Kernel como a **Rainha única (Singleton)**, as operárias essenciais como **Eager Loading**, os zangões sob demanda como **Lazy Loading**, e as interfaces e eventos como os **feromônios** que comunicam o ecossistema com total desacoplamento.
- **O Disjuntor Imortal (`Circuit Breaker`):** O sistema imunológico que desarma apenas a peça em pane para não deixar a colmeia morrer.

Ao entender o ciclo de vida real de um clique pelo sistema, encontrar **falhas de lógica** e **brechas de segurança** deixa de ser um mistério e se torna algo natural e transparente.

---

## 🗂️ Índice dos Capítulos

Leia na ordem. Cada capítulo acompanha a vida real de uma requisição pelo ecossistema:

| Cap. | Capítulo | O que você vai entender com as analogias | Arquivos reais dissecados | Status |
| :---: | :--- | :--- | :--- | :---: |
| **00** | [Introdução — O Mapa do Sistema e a Filosofia da Arquitetura](00-introducao.md) | A dor do acoplamento, a revelação da tomada e do Arduino, a metáfora biológica de Alan Kay, os 5 pilares do SOLID na vida real e o mapa geral do projeto. | Visão geral do projeto e arquitetura | ✅ Pronto |
| **01** | [Capítulo 01 — O "Start": O Nascimento da Requisição e a Portaria Única](01-start-servidor.md) | O silêncio antes do Big Bang, os filtros do `.htaccess`, o papel do *Front Controller* (`public/index.php`), a escotilha de isolamento (`.maintenance`) e **4 achados reais na Lupa de Auditoria**. | `.htaccess`, `public/.htaccess`, `public/index.php`, `public/maintenance.php`, `public/reset_opcache.php` | ✅ Pronto |
| **02** | [Capítulo 02 — O Ritual do Bootstrap](02-o-ritual-do-bootstrap.md) | O ritual de inicialização (o BIOS do sistema), o carregamento do `.env`, os autoloaders do Composer e o sistema imunológico contra falhas fatais (`ErrorHandler`). | `bootstrap.php`, `composer.json`, `src/Core/Error/` | ✅ Pronto |
| **03** | Capítulo 03 — A Rainha da Colmeia (O Padrão Singleton Blindado) | Por que duas rainhas geram colapso, como a classe `Application` foi blindada contra clonagem e desserialização, e como ela se assenta no trono do Container DI. | `src/Core/Application.php` | ⏭️ Próximo |
| **04** | Capítulo 04 — O Almoxarifado Central (`Container`) e as Tomadas (`Contracts`) | A inversão de dependência na veia (DIP), como registrar serviços sem acoplamento e como trocar implementações sem mexer no motor principal. | `src/Core/Container/`, `src/Core/Providers/`, `src/Core/Contracts/` | ⏳ Em breve |
| **05** | Capítulo 05 — O Despertar da Colmeia (`boot()`, Plugins e Feromônios) | Como o `PluginManager` escaneia manifestos (`plugin.json`), acorda as operárias e usa o `EventDispatcher` como rádio comunicador. | `src/Core/Plugin/`, `src/Core/Events/`, `src/SystemApps/` | ⏳ Em breve |
| **06** | Capítulo 06 — O Porteiro Global e o Despacho da Requisição | O Roteador como catraca de metrô (OCP), a verificação antecipada de permissões e CSRF, a injeção automática de dependências e a execução do Controller. | `src/Core/Routing/`, `src/Core/Http/` | ⏳ Em breve |
| **07** | Capítulo 07 — Segurança em Camadas (Autenticação, Sessão, ACL e Proteções) | Como o sistema sabe quem é o usuário, protege rotas administrativas, previne ataques de redirecionamento, sequestro de sessão e formulários falsos. | `src/Core/Security/`, `src/SystemApps/auth/` | ⏳ Em breve |
| **08** | Capítulo 08 — A Persistência Desacoplada e os Espaços Visuais | O Repository Pattern (o Controller pergunta *"o quê"*, o Repository responde *"como"*), gravação atômica em JSON e os Workspaces por perfil de usuário. | `src/Core/Database/`, `src/Core/Storage/`, `src/Core/Theme/`, `src/Core/Workspace/` | ⏳ Em breve |
| **09** | Capítulo 09 — Caderno Consolidado de Auditoria | O dossiê com todas as brechas de segurança, gargalos de performance e anomalias de lógica identificadas ao longo da análise com planos de correção. | Consolidação global | ⏳ Em breve |

---

## 💡 Como Aproveitar ao Máximo Este Guia

1. **Abra o código correspondente no editor:** ao ver uma referência a uma linha ou método, abra o arquivo real do projeto para checar com seus próprios olhos.
2. **Pense em conexões, não em sintaxe:** foque em entender *quem precisa de quem*, *quem cria o quê* e *o que acontece se uma peça for trocada*.
3. **Estude a Lupa de Auditoria no fim de cada capítulo:** é ali que a teoria se transforma em habilidade prática de encontrar bugs e vulnerabilidades antes que o cliente ou um invasor encontre.
