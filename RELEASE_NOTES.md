## 🚀 Domain-System OS v2.7.2 — Melhorias, Segurança & Estabilidade

Esta versão consolida auditorias cirúrgicas completas nos módulos **DomainDrop**, **DomainCommerce**, **Academy** e **Core/Roteamento**, elevando o nível de segurança financeira, proteção de margens, SEO e estabilidade.

---

### ✨ Melhorias & Novas Funcionalidades

#### 📦 DomainDrop (`src/Plugins/domain_drop`)
- **Mineração Expandida & Segmentação Brasil 🇧🇷:** Limite de mineração ampliado de 20 para **80–100 produtos por categoria** com priorização de estoque nacional (`ship_from_country: BR`, `ship_to_country: BR`), avaliações 4.8★–5.0★ e preços em BRL.
- **Paginação Administrativa & Vitrine Pública com SEO:**
  - Painel administrativo (`/admin/dropship`) paginado com **50 itens por página** e barra de navegação completa.
  - Vitrine pública (`/loja`) paginada com **48 itens por página** e meta tags `<link rel="canonical">`, `<link rel="prev">` e `<link rel="next">` para indexação completa no Google.
- **Motor de Precificação Dinâmica & Sentinela de Volatilidade:**
  - Implementado **Índice de Volatilidade ($\mathcal{I}_v = 12\%$)** no cálculo do preço de venda (`Custo Protegido = Custo × (1 + Iv)`) para proteger margens contra IOF, spread e oscilações de fornecedores.
  - **Sentinela de Preços & Estoque:** Varredura automática configurável (**3h, 4h, 6h ou 12h**) via AliExpress SDK/Scraper executada com lock exclusivo (`flock`).
- **Centralização de UX:** Painel de **Margens de Lucro, Volatilidade e Sentinela** movido de *Configurações da API* diretamente para a tela principal de **Catálogo & Mineração**.

#### 🛒 DomainCommerce (`src/Plugins/commerce`)
- **Menu Administrativo Organizado:** Submenu dedicado para **Painel & Vendas** (`/admin/commerce`) e **Gateways & Configs** (`/admin/commerce/settings`).
- **Relatório Financeiro:** `getSalesTimeline()` corrigido para exibir os **12 meses mais recentes** em vez dos 12 mais antigos.
- **Performance de Banco:** Cache estático via `WeakMap<PDO, bool>` em `PdoOrderRepository::ensureTable()`.

---

### 🛡️ Correções de Segurança & Estabilidade

#### 🛒 DomainCommerce (Segurança Financeira)
- **Webhooks Fail-Closed nos Gateways de Afiliados:** `HotmartGateway`, `EduzzGateway`, `MonetizzeGateway` e `ClickBankGateway` agora rejeitam requisições quando o token/segredo não está configurado (eliminando bypass sem autenticação). Removido vazamento de `chave_unica` em `transaction_id` no `MonetizzeGateway`.
- **Verificação Real de Webhook do PayPal:** `PayPalGateway::handleWebhook()` agora valida assinatura oficial (`/v1/notifications/verify-webhook-signature`) e confirma status e valor da captura diretamente na API do PayPal.
- **Stripe Webhook & Anti-Replay:** `StripeGateway` passa a retornar o valor pago (`total`) para checagem de divergência, trata `charge.refunded` e valida `payment_status`. `CheckoutController::stripeSuccess()` e `paypalSuccess()` agora bloqueiam replay entre pedidos, impedem fallback para modo demo quando há credenciais configuradas, exigem idempotência (`status === 'paid'`) e bloqueiam modo demo em produção (`APP_ENV=production`).
- **Proteção contra CSV Formula Injection & XSS:** Sanitização de células no CSV exportado (`OrdersAdminController::export()`), proteção contra DOM XSS nas views administrativas e de checkout, e eliminação de duplo disparo de clique em `<label onclick>` no checkout.

#### 🔗 Integração Cross-Plugin (`DomainCommerce` ↔ `DomainDrop` ↔ `Academy`)
- **Isolamento de Eventos (`payment.approved` / `payment.refunded`):** Listeners do `Academy` agora filtram estritamente por `item_type` (`''` ou `'course'`), impedindo que compras ou reembolsos do `DomainDrop` matriculem ou cancelem alunos no `Academy`.
- **Sincronização de Reembolsos no DomainDrop:** `DomainDrop` passa a escutar `payment.refunded` para marcar pedidos reembolsados.
- **Fim da Colisão de IDs no Checkout:** `/checkout/produto/{id}` força resolução exclusiva de `dropship_product` (sem colidir com cursos de mesmo ID), `checkout.php` envia `item_type` explícito e `canAccessOrder()` isola escopos de cliente vs aluno.

#### 🎓 Academy (`src/Plugins/academy`)
- **Controle de Acesso em Progresso & Anotações:** `StudentDashboardController` agora valida conta ativa, publicação do curso/aula e matrícula ativa em cursos pagos antes de ler ou gravar progresso/notas.
- **Proteção XSS & Sessão:** Corrigido XSS no painel de alunos (`views/admin/students/index.php`), respeitado `academy_logged_out` e `academy_student_role` em `CourseFrontController::showLesson()`, e preservados todos os campos de pagamento/link na exportação/importação ZIP (`CoursePackageService`).

#### 📦 DomainDrop (`src/Plugins/domain_drop`)
- Corrigido `saveSettings()` para não sobrescrever margens/Sentinela ao salvar credenciais da API; corrigidos argumentos invertidos de `setFlash()` no `runDailyCycle()`; corrigida detecção de código dos Correios (`BR$` no final); corrigido `getPriceSyncInfo()` para respeitar `price_sync_enabled` e usar markup/volatilidade configurados; removida tag `</div>` sobrando em `storefront.php`, corrigido Age Gate (+18) em filtros AJAX, eliminados erros de XSS em `settings.php`, `demand.php` e `stage.php`, e garantida atomicidade transacional em `PdoDemandMetricsRepository::incrementMetric()`.

#### ⚙️ Core, Roteamento & Modo Seguro
- **Normalização de Barra Final no Roteador (`Router::dispatch()`):** URLs como `/admin/` agora resolvem corretamente para `/admin` sem erro 404.
- **Blindagem do Modo Seguro (`/admin/emergency`):** Adicionada proteção CSRF, bloqueio contra brute-force (máx. 5 tentativas / 15 min por IP), exigência de `EMERGENCY_KEY >= 16` caracteres, regeneração de sessão pós-login e ocultação total do link de Modo Seguro em páginas 404 públicas/externas.

---

### ✅ Validação
- **Consistência de Versão (`VersionConsistencyTest`):** `DS_VERSION`, `composer.json` e todos os 20 manifestos `plugin.json` padronizados em **`2.7.2`**.
- **Suíte de Testes PHPUnit:** **701/701** testes aprovados (**4.228** asserções, 0 erros, 0 falhas).
