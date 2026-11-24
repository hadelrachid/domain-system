## 🚀 Domain-System OS v2.8.5 — Bloqueio Inteligente no Atualizador de Extensões & Refinamento Operacional

A versão **v2.8.5** aprimora o motor nativo de atualizações do Cockpit Administrativo, introduzindo travas visuais e no backend para evitar downloads e execuções desnecessárias quando as extensões instaladas já estão nas versões mais recentes.

---

### 🔄 1. Botão de Atualização Inteligente no Painel de Plugins
- **Desativação Visual Preventiva:**  
  Quando um plugin já está atualizado com a versão mais recente do catálogo oficial, o botão de ação fica desabilitado (`disabled`) exibindo o rótulo *"Atualizado"* com um ícone de verificação.
- **Destaque Dinâmico para Versões Superiores:**  
  O botão de atualização só fica habilitado quando o repositório remoto declarar uma versão comprovadamente maior (`version_compare($remote, $local, '>')`), destacando a versão de destino: *"Atualizar para vX.Y.Z"*.
- **Contador Realista de Atualizações:**  
  O indicador de atualizações pendentes no topo da página e as notificações do sistema agora contabilizam apenas extensões que realmente necessitam de upgrade.

---

### 🛡️ 2. Trava de Segurança no Backend (`PluginUpdateChecker`)
- **Bloqueio de Requisições Redundantes:**  
  O manipulador `updatePluginFromRemote` confere a versão local antes de disparar o download. Caso o plugin já esteja na versão mais recente, a requisição é encerrada com mensagem amigável: *"O plugin já está na versão mais recente. Nenhuma atualização disponível."*

---

### 🧪 3. Bateria de Testes do Core
- **766 testes automatizados** e **4.611 asserções** validados no PHPUnit com 100% de aprovação.
- Novos testes unitários cobrindo o bloqueio do atualizador e a renderização condicional dos botões no Cockpit.

---

## 🚀 Domain-System OS v2.8.4 — Catraca de Segurança no Cockpit, Desacoplamento Total do Core & Sincronização em Tempo Real

A versão **v2.8.4** fecha o ciclo de isolamento e blindagem do **Domain-System Core OS**, garantindo que o núcleo seja 100% agnóstico em relação a qualquer plugin de aplicação ou tema visual.

---

### 🛡️ 1. Catraca de Segurança no Login Administrativo (Boundary Protection)
- **Bloqueio Imediato de Contas Comuns no Cockpit:**  
  A tela de login do sistema (`/login`) agora atua como uma catraca estrita do ambiente administrativo. Contas comuns (clientes da loja, alunos ou membros do site) que tentarem autenticar por esta rota são imediatamente barradas.
- **Mensagem Amigável e Anti-Enumeração:**  
  Em caso de tentativa de login não administrativo, o formulário responde com o erro padrão seguro: *"Credenciais inválidas. Verifique seu e-mail e senha."*, sem vazar informações sobre a existência da conta.
- **Limpeza Automática de Sessões:**  
  Ao acessar a tela de login do Cockpit, qualquer resquício de sessão não-administrativa anterior é destruído de forma limpa, evitando travamentos em loops de redirecionamento.

---

### 🔄 2. Preservação de Rotas e Suporte Agnóstico a Subpastas (XAMPP / Apache)
- **Preservação de `redirect` no Login:**  
  Ao tentar acessar `/admin` deslogado, o destino original é transportado e mantido no formulário de login, garantindo que o gestor caia diretamente na página solicitada após autenticar.
- **Resolução de `BASE_URL` Confiável:**  
  Caminhos relativos são combinados dinamicamente com o subdiretório da aplicação (como `/domain-system/admin`), eliminando redirecionamentos quebrados fora da raiz do servidor.

---

### ⚡ 3. Sincronização de Papéis e Permissões em Tempo Real
- **Atualização Instantânea de Papéis:**  
  Se um usuário for promovido a administrador no banco de dados, o `AuthMiddleware` detecta a nova permissão no primeiro acesso e atualiza a sessão ativa na hora, sem exigir que o gestor limpe cookies manualmente.
- **Fallback Robusto no ACL (`IdentityManager`):**  
  O validador de papéis agora confere de forma resiliente tanto a tabela relacional `user_roles` quanto o campo direto `role` da tabela de usuários.

---

### 🏛️ 4. Desacoplamento Estrito do Core OS
- **Núcleo 100% Neutro:**  
  Remoção de referências rígidas a regras de negócio de plugins específicos dentro das telas administrativas e controladores do Core.
- **Botão de Troca de Conta:**  
  Inclusão do botão *"Sair / Trocar de Conta"* em telas de restrição de acesso para facilitar a troca direta entre diferentes credenciais.

---

### 🧪 5. Bateria de Testes do Core
- **764 testes automatizados** e **4.605 asserções** validados no PHPUnit com 100% de sucesso.
- Novos testes unitários específicos para catraca de login, preservação de rotas e isolamento de privilégios.

---

## 🚀 Domain-System OS v2.8.3 — Arquitetura dos Três Pilares & Atualizador em 1 Clique

A versão **v2.8.3** estabeleceu a divisão definitiva da plataforma em três pilares independentes e introduziu o motor nativo de atualização de extensões estilo WordPress.

---

### 🏛️ 1. Arquitetura em Três Pilares (Core OS Ultra Leve)
- **Desacoplamento do Repositório Central:**  
  O repositório principal (`domain-system`) foca unicamente no essencial: o Sistema Operacional Web, barramento de eventos, injeção de dependências e segurança do Cockpit.
- **Ciclo de Vida Independente:**  
  Plugins e Temas passam a ter seus próprios repositórios e versionamento SemVer autônomo.

---

### 🔄 2. Motor de Atualização de Plugins em 1 Clique
- **Notificação Visual no Painel:**  
  Alerta no topo do painel administrativo indicando quando existem versões atualizadas de extensões disponíveis.
- **Instalação Segura com 1 Clique:**  
  Download direto de arquivos ZIP assinados, conferência de integridade por hash SHA-256 e extração automatizada sem necessidade de intervenção manual no servidor.

---

### 🧪 3. Suíte de Testes Automatizados
- **760 testes automatizados** e **4.593 asserções** validados no PHPUnit com 100% de sucesso.

---

## 🚀 Domain-System OS v2.7.4 — Resiliência OAuth2 AliExpress & Sessão Persistente

Esta versão **v2.7.4** resolve a preservação de sessão e o fluxo de redirecionamento no retorno da autorização OAuth 2.0 do AliExpress em ambientes de produção (como Hostinger).

### 📦 Correções & Melhorias no DomainDrop & Core
- **Padronização de Cookie de Sessão com `SameSite=Lax`:**
  Permite que o navegador preserve a sessão do administrador ao ser redirecionado de volta da tela de autorização do AliExpress (`api-sg.aliexpress.com` → painel da loja).
- **Lista de Rotas Seguras nos Middlewares:**
  Inclusão das rotas `/admin/dropship/callback` e `/admin/dropship/oauth/callback` na lista segura, evitando deslogar o usuário durante o retorno do login internacional.
- **Contrato Oficial do Cliente AliExpress (`AliExpressApiClientInterface`):**
  Definição explícita de métodos para geração de link de autorização, criação e renovação de tokens de acesso.

---

## 🚀 Domain-System OS v2.7.3 — OAuth2 AliExpress, Estoque ao Vivo & Blindagem de Segurança

Esta versão **v2.7.3** consolida o fluxo oficial **OAuth 2.0 Server-side** com o AliExpress, verificação de disponibilidade em tempo real e melhorias de segurança nas extensões.

### 📦 Novidades no DomainDrop
- **Conexão Oficial com Conta AliExpress:**
  Botão no painel administrativo para autorizar o aplicativo oficial via OAuth 2.0 com renovação automática de token antes do vencimento.
- **Verificação de Estoque e Frete ao Vivo:**
  Conferência em tempo real dos preços e prazos de entrega antes da confirmação do pedido.
