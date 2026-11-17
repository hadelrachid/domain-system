# Por Dentro do Domain System (v2.7.0)

> **Um guia direto, sem enrolação e sem "economês de programação" sobre como o Domain System foi construído — do zero até a auditoria de falhas e segurança.**

Se você está começando agora na programação, entrando na faculdade ou nunca estudou "Padrões de Projeto" nem "Arquitetura SOLID", **este guia foi escrito para você**.

A ideia aqui não é impressionar com palavras bonitas em inglês. É abrir o capô do Domain System peça por peça, mostrar **como ele foi feito**, **por que cada arquivo existe** e **o que acontece a cada milissegundo** quando alguém abre o site no navegador.

Quando você entende o caminho real que um pedido faz por dentro do sistema, encontrar **erros de lógica** e **brechas de segurança** deixa de ser mágica e vira algo óbvio.

---

## Índice dos Capítulos

Leia na ordem. Cada capítulo continua exatamente onde o anterior parou, seguindo a vida real de um clique do usuário:

| Cap. | Capítulo | O que você vai entender | Arquivos reais vistos | Status |
| :---: | :--- | :--- | :--- | :---: |
| **00** | [Introdução — O Mapa do Sistema (sem palavras difíceis)](00-introducao.md) | O que é o Domain System, onde mora cada pasta e um tradutor simples para os nomes técnicos que aparecem no código. | Visão geral do projeto | ✅ Pronto |
| **01** | [Capítulo 01 — O "Start": o que acontece quando alguém abre o site](01-start-servidor.md) | Como o Apache redireciona o pedido, por que tudo cai no `public/index.php`, como a URL é limpa, como o modo manutenção trava o site e **4 problemas reais encontrados na porta de entrada**. | `.htaccess`, `public/.htaccess`, `public/index.php`, `public/maintenance.php`, `public/reset_opcache.php` | ✅ Pronto |
| **02** | Capítulo 02 — Acordando o Sistema (`bootstrap.php`) | Como o sistema lê o `.env`, como o PHP encontra as classes sozinho (Autoload) e como liga o "airbag" contra erros (`ErrorHandler` e `CircuitBreaker`). | `bootstrap.php`, `composer.json`, `src/Core/Error/` | ⏭️ Próximo |
| **03** | Capítulo 03 — O "Cérebro Único" (Singleton e `Application.php`) | O que é Singleton explicado de verdade, por que só pode existir um `Application`, como ele foi programado e onde estão seus limites. | `src/Core/Application.php` | ⏳ Em breve |
| **04** | Capítulo 04 — O "Almoxarifado" (`Container`) e as "Tomadas" (`Contracts`) | Como o sistema monta e entrega objetos sem virar bagunça e como trocar peças sem quebrar o resto (SOLID na vida real). | `src/Core/Container/`, `src/Core/Providers/`, `src/Core/Contracts/` | ⏳ Em breve |
| **05** | Capítulo 05 — Ligando os Módulos (`SystemApps`, `Plugins` e Eventos) | Como o sistema descobre plugins, acorda cada um na ordem certa e usa o "rádio comunicador" (`EventDispatcher`) para conversar sem acoplamento. | `src/Core/Plugin/`, `src/Core/Events/`, `src/SystemApps/` | ⏳ Em breve |
| **06** | Capítulo 06 — A Viagem de uma Requisição (`Request`, `HttpKernel`, `Router`, `Middlewares` e `Response`) | Como o pedido vira objeto, passa pelos seguranças (middlewares), encontra o `Controller` certo e volta como página HTML ou JSON. | `src/Core/Http/`, `src/Core/Routing/` | ⏳ Em breve |
| **07** | Capítulo 07 — Segurança na Prática (Login, Sessão, Permissões, CSRF e Redirects) | Como o sistema sabe quem está logado, impede invasão de rotas de admin, bloqueia ataques CSRF/XSS e evita redirecionamentos falsos. | `src/Core/Security/`, `src/SystemApps/auth/` | ⏳ Em breve |
| **08** | Capítulo 08 — Banco de Dados, Arquivos JSON Seguros, Temas e Workspaces | Como os dados são salvos (SQLite/MySQL + escrita atômica de JSON) e como a tela final é montada para cada tipo de usuário. | `src/Core/Database/`, `src/Core/Storage/`, `src/Core/Theme/`, `src/Core/Workspace/` | ⏳ Em breve |
| **09** | Capítulo 09 — Caderno Final de Auditoria (Checklist Completo) | Todos os bugs de lógica e brechas de segurança encontrados ao longo da série, organizados por prioridade de correção. | Resumo geral | ⏳ Em breve |

---

## Como aproveitar melhor este guia

- **Não tente decorar termos técnicos:** sempre que aparecer uma palavra em inglês (como *Front Controller*, *Singleton*, *Middleware*), ela virá traduzida para o português do dia a dia logo ao lado.
- **Abra o arquivo real junto:** quando o capítulo citar algo como `public/index.php (linha 64)`, abra esse arquivo no seu editor e olhe com os próprios olhos.
- **Preste atenção na "Lupa de Auditoria" no fim de cada capítulo:** é lá que mostramos falhas reais encontradas no código durante a leitura, explicando **o que está errado, o que um invasor ou bug pode causar e como resolver**.
