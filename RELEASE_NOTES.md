## 🚀 Domain-System OS v2.7.4 — Resiliência OAuth2 AliExpress & SameSite Cookie Lax

Esta versão **v2.7.4** resolve a preservação de sessão e o fluxo de redirecionamento no retorno da autorização OAuth 2.0 do AliExpress em ambientes de produção (como Hostinger), aplicando rigorosamente os princípios **SOLID** e a **Inversão de Dependência (DIP)**.

### 📦 Correções & Melhorias no DomainDrop & Core
- **Padronização de Cookie de Sessão com `SameSite=Lax`:**
  - Em `src/Core/Security/SessionManager.php`, o cookie de sessão `DS_SESS_*` foi padronizado para `'samesite' => 'Lax'`. Isso permite que o navegador envie o cookie de sessão durante o redirecionamento top-level GET do AliExpress (`https://api-sg.aliexpress.com` -> `https://rachidd.com/admin/dropship/callback`), mantendo o usuário autenticado no painel.
- **Allowlist nos Hooks de Segurança e Middlewares:**
  - Atualizados `auth/Plugin.php`, `SystemAdmin/Plugin.php` e `AuthMiddleware.php` para incluir `/admin/dropship/callback` e `/admin/dropship/oauth/callback` na allowlist de rotas de callback de autenticação, evitando o descarte prematuro do código de autorização `?code=...` com redirecionamento antecipado para `/login`.
- **Contrato Oficial do AliExpress Client (`AliExpressApiClientInterface`):**
  - Declarados os métodos `getOAuthAuthorizeUrl()`, `createAccessToken()` e `refreshAccessToken()` na interface `AliExpressApiClientInterface`, permitindo injeção de dependência pura e substituição por mocks na suíte de testes.
- **Tratamento Resiliente no Controlador (`DropshipAdminController`):**
  - Implementado método atômico de persistência `saveConfig()` e injeção do `AliExpressApiClientInterface`.
  - Tratamento inteligente: se a sessão estiver ativa, o usuário é redirecionado para `/admin/dropship/settings` com mensagem de sucesso; se houver isolamento temporário de sessão, o token é salvo com sucesso e o usuário é guiado ao `/login` preservando a mensagem de confirmação.
- **Suíte de Testes:**
  - Adicionados testes unitários específicos em `DomainDropHubTest` para validar a rejeição de `state` incorreto, a troca do código pelo token de acesso e a consistência da configuração gravada.

---

## 🚀 Domain-System OS v2.7.3 — OAuth2 AliExpress, Estoque ao Vivo & Blindagem de Segurança

Esta versão **v2.7.3** consolida o fluxo oficial **OAuth 2.0 Server-side** com o AliExpress, verificação de disponibilidade/estoque em tempo real, páginas dedicadas de moda íntima e sexshop, correções de variações de SKU e um ciclo completo de endurecimento de segurança nos três plugins (`DomainCommerce`, `Academy` e `DomainDrop`).

### 📦 Novidades no DomainDrop
- **OAuth 2.0 Server-side AliExpress (`Configurações & API`):**
  - Botão **Conectar Conta AliExpress (OAuth2)** com rotas `/admin/dropship/oauth/authorize` e `/admin/dropship/oauth/callback`.
  - Troca automática `code -> access_token / refresh_token` via `/rest/auth/token/create` e renovação automática via `/rest/auth/token/refresh` 30 minutos antes do vencimento.
  - Detecção automática de `HTTPS` atrás de proxy (`X-Forwarded-Proto`) e fallback seguro para modo *Test* sem bloqueio de sincronização.
- **Verificação SDK-first, Sentinela & Botão "Comprar Agora":**
  - Verificação de disponibilidade via SDK (`aliexpress.ds.product.get`) e exclusão automática de anúncios `404` / `"Página Não Encontrada"` (mesmo com imagem indexada) no ciclo de 24h, na Sentinela de Volatilidade (6h/12h) e na fila **"Atualizar Todas as Imagens"**.
  - Checagem ao vivo no clique de **"Comprar Agora"** (`POST /api/drop/check-availability`) consultando estoque real do SDK e bloqueando compra de itens esgotados/removidos.
- **Variações de SKU, Páginas Dedicadas & Paginação:**
  - Extração de eixos únicos de SKU (`Color`/`Size` → `Cor`/`Tamanho`), `skuPropertyList` no scraper e matriz multi-cores em lingeries e ferramentas (ex.: alicates desencapadores) no catálogo curado.
  - Páginas dedicadas `/moda-intima` (`/roupas`) e `/sexshop` (com Age Gate +18).
  - Paginação do **DomainDrop Hub** ajustada para **48 itens por página** (múltiplo exato de 2, 3 e 4 colunas).

### 🔐 Segurança & Hardening (`DomainCommerce`, `Academy`, `DomainDrop` & Core)
- **DomainCommerce:** Bloqueio de IDOR em `canAccessOrder()` (com suporte seguro a `checkout_owned_order_refs` e checagem de `academy_logged_out`), proteção contra sobrescrita de endereço de terceiros, validação de host em `resolveAbsoluteUrl()` (`StripeGateway`/`PayPalGateway`), assinatura obrigatória em produção no webhook do PayPal, bloqueio de `$paid <= 0.0` e gateway desativado em `WebhookController`, e `chmod 0600` em `JsonCommerceSettings`.
- **Academy:** Neutralização de *docblock breakout* (RCE) em `ThemeLessonFileScaffolder`, remoção de fallback não autenticado em `CourseFrontController::showCourse()` com trava estrita em `pages/`, proteção anti-ZipSlip e anti-ZIP bomb (500 arquivos / 50MB) em `CoursePackageService`, bloqueio de `javascript:`/`data:` em links e blocos PDF (`CourseService`, `LessonBlockRenderer`, `editor.php`) e ocultação de segredos no HTML admin.
- **DomainDrop & Infraestrutura:** Remoção de enumeração pública via `LIKE` em `/rastrear`, checagem de propriedade em `submitComplaint()`, proteção anti-SSRF em `AliExpressLinkScraperService`, remoção de *spoofing* de identidade/conversão em `trackDemand`, sanitização de XSS em `product.php` e `customer_account.php`, senha mínima de 8 caracteres + *rate limiting* no login de clientes, `.htaccess` em `config/` e suporte a `APP_KEY` como fallback em `EmergencyController`.

### ✅ Qualidade & Testes
- **Suíte PHPUnit:** `719/719` testes aprovados (`4347` asserções), incluindo regressões de segurança (`SecurityAuditRegressionTest`), variações (`DomainDropProductVariantsTest`), mídia (`DomainDropMediaSyncTest`) e OAuth2/disponibilidade (`DomainDropHubTest`).
- **Consistência de Versão (`VersionConsistencyTest`):** `DS_VERSION`, `composer.json` e todos os 20 manifestos `plugin.json` padronizados em **`2.7.3`**.
