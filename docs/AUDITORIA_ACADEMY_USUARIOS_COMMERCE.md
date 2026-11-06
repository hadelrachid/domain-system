# Auditoria — Usuários, Academy, Alunos & Matrículas e DomainCommerce

**Data:** 05/11/2026 · **Escopo:** `SystemApps/auth`, `Plugins/academy`, `Plugins/commerce`
**Método:** leitura direta do código-fonte em `src/` (não apenas `domain-system.txt`). Os testes unitários passam (502), mas **nenhuma falha abaixo foi coberta por teste** — os testes atuais validam o "caminho feliz".

> [!IMPORTANT]
> `domain-system.txt` / `project.txt` (gerados por `update_project.py`, idênticos entre si) **incluem** o `commerce`, mas **excluem** `tests/`, `vendor/`, `temp/` e `auditoria/` — um auditor que use só o `.txt` não vê a cobertura de testes. Além disso, `src/Plugins/commerce/` está **sem rastreio no git** (`??`), então `git grep` não o encontra; use `Select-String`. Recomenda-se dar `git add` no `commerce`.

## Legenda de confiança
- ✅ **Confirmado** — li o código que prova o problema.
- ⚠️ **Provável** — consequência lógica do código, mas não executei uma requisição real.
- 🔍 **Verificar** — suspeita que depende de ambiente/configuração.

---

## Resumo executivo

| # | Severidade | Área | Problema | Confiança |
|---|---|---|---|---|
| C1 | 🔴 Crítica | Commerce | Webhooks reais provavelmente **bloqueados pelo CSRF** (403) | ✅ leitura / ⚠️ runtime |
| C2 | 🔴 Crítica | Commerce | **Matrícula grátis** via modo demonstração (`gateway` vem do POST) | ✅ |
| C3 | 🔴 Crítica | Commerce | Webhooks Stripe/PayPal **sem verificação de assinatura** | ✅ |
| C4 | 🔴 Crítica | Commerce | Fallback "primeiro curso publicado" em compra de afiliado | ✅ |
| A1 | 🟠 Alta | Commerce | Stripe/PayPal "configurados" geram **URL falsa** (pagamento real não existe) | ✅ |
| A2 | 🟠 Alta | Commerce | Reembolso/chargeback **não revoga matrícula** | ✅ |
| A3 | 🟠 Alta | Usuários/Alunos | Banir/suspender **não derruba sessão ativa** | ✅ |
| A4 | 🟠 Alta | Commerce | Idempotência frágil (evento disparado em webhook repetido) | ✅ |
| A5 | 🟠 Alta | Academy | PDFs de cursos pagos em storage **público** | ⚠️ |
| M1 | 🟡 Média | Alunos | Sem rate limit / anti força-bruta nos logins | ✅ |
| M2 | 🟡 Média | Alunos | Senha mínima de 6 caracteres; política forte só no admin | ✅ |
| M3 | 🟡 Média | Commerce | Aluno criado por afiliado nunca recebe credenciais | ✅ |
| M4 | 🟡 Média | Academy | Preço `10.00` forçado ao desmarcar "gratuito" sem preço | ✅ |
| M5 | 🟡 Média | Commerce | Segredos em `settings.json` em texto puro | ✅ |
| M6 | 🟡 Média | Commerce | Sem validação de valor pago vs. valor do pedido | ✅ |
| B1 | 🟢 Baixa | Usuários | Vários métodos de `UserController` sem validação de entrada | ✅ |
| B2 | 🟢 Baixa | Geral | Dois fluxos de login com sessão compartilhada (`user_id`) | ✅ |

---

## 🔴 Críticos

### C1 — Webhooks de pagamento provavelmente nunca chegam ao controller
**Onde:** `src/Core/Routing/Middlewares/CsrfMiddleware.php` + rota `POST /webhook/payment/{gateway}` em `commerce/Plugin.php`.

O `CsrfMiddleware` é **global** (`CoreServiceProvider.php:157`) e só isenta rotas que começam com `/api/`. O comentário do próprio middleware cita "webhook" como exceção, mas a rota está em `/webhook/...`, **fora** de `/api/`. Um POST do Mercado Pago/Hotmart/Eduzz não envia `csrf_token`, logo recebe 403.

**Impacto:** Pix e boleto (assíncronos) ficam **eternamente `pending`**; toda a integração de afiliados (Hotmart, Eduzz, Monetizze, ClickBank) é inoperante em produção. Os testes não pegam isso porque chamam `WebhookController::handle()` direto, pulando o middleware.

**Correção sugerida:**
1. Mover a rota para `/api/webhook/payment/{gateway}` (e atualizar as URLs nos painéis dos gateways), **ou** criar um contrato `CsrfExemptRouteInterface` / flag `csrf => false` na configuração da rota.
2. Só liberar o CSRF **junto** com C3 (verificação de autenticidade), senão o endpoint fica aberto.
3. Teste de integração que passe pelo `HttpKernel` completo com POST sem token.

> 🔍 Validar com: `curl -X POST https://seusite/webhook/payment/hotmart -d "{}"` — se retornar a página de CSRF/403, confirmado.

### C2 — Qualquer aluno logado consegue se matricular de graça
**Onde:** `CheckoutController::process()` (L168–171, L206) + `StripeGateway`/`PayPalGateway::processPayment()`.

`$gatewayId` vem de `$request->input('gateway')` e o registro devolve o gateway mesmo **não configurado**. Sem credenciais, `StripeGateway` e `PayPalGateway` retornam `PaymentResult::approved(... 'STRIPE_DEMO_...')`. O controller então marca o pedido `paid` e dispara `payment.approved` → matrícula liberada.

Basta um POST manual com `gateway=stripe` (ou `paypal`). O mesmo vale para o Mercado Pago se as credenciais forem removidas (`MP_DEMO_`).

**Correção sugerida:**
- Modo demonstração **somente** com flag explícita `commerce_demo_mode=true` e **nunca** em produção (`APP_ENV`).
- `process()` deve rejeitar gateway com `isConfigured() === false` (`409`).
- A listagem do checkout deve usar só gateways configurados.
- Teste: POST com gateway não configurado ⇒ pedido **não** vira `paid`.

### C3 — Stripe e PayPal aceitam qualquer webhook
**Onde:** `StripeGateway::handleWebhook()`, `PayPalGateway::handleWebhook()`.

Nenhum verifica `Stripe-Signature` (HMAC-SHA256 com *webhook signing secret*) nem a verificação de webhook do PayPal. Um atacante cria um pedido `pending` (ele conhece o próprio `order_ref`) e envia `{"type":"checkout.session.completed","data":{"object":{"client_reference_id":"ORD_..."}}}` ⇒ `paid`.

O Mercado Pago está melhor (consulta a API oficial para confirmar), **exceto** pelo "Fallback para teste manual" (L257–266): se o gateway não está configurado, aceita `order_ref` do corpo e aprova.

Os afiliados (Hotmart/Eduzz/Monetizze/ClickBank) validam segredo com `hash_equals` ✅ — esse padrão está correto.

**Correção sugerida:**
- Stripe: validar `Stripe-Signature` com tolerância de timestamp (5 min).
- PayPal: chamar `/v1/notifications/verify-webhook-signature`.
- Mercado Pago: validar também `x-signature` e **remover o fallback de teste** (ou condicioná-lo a `demo_mode`).
- Contrato novo: `WebhookVerifierInterface` injetado em cada gateway (SRP).

### C4 — Compra de afiliado cai no curso errado
**Onde:** `WebhookController::resolveCourseByProduct()` (passos 4 e 5).

Se o `product_id` não casa, o código tenta "qualquer curso da mesma plataforma" e, por fim, **"o primeiro curso publicado"** (L226–229). Um produto novo/desconhecido da Hotmart concede o primeiro curso do catálogo a quem comprou outra coisa — e um erro de cadastro vira prejuízo silencioso.

**Correção sugerida:** retornar `0` quando não houver correspondência **exata**, registrar em log/painel ("Pedido órfão — vincular manualmente") e responder `200` sem matricular.

---

## 🟠 Altos

### A1 — Stripe e PayPal "configurados" não cobram de verdade
`StripeGateway` L63 gera `https://checkout.stripe.com/pay/<hex aleatório>` e `PayPalGateway` L61 gera `https://www.paypal.com/checkoutnow?token=<hex aleatório>`. **São URLs inventadas**, não sessões reais. Ao preencher as chaves no painel, o cliente cai numa página de erro e o pedido fica `pending`. Na prática só o Mercado Pago e os afiliados funcionam. A tela de configuração sugere o contrário — risco de expectativa e de venda perdida.

**Sugestão:** implementar `POST /v1/checkout/sessions` (Stripe) e `/v2/checkout/orders` (PayPal), ou marcar os gateways como "em breve" no dashboard até existirem.

### A2 — Reembolso não revoga acesso
`MercadoPagoGateway::handleWebhook` devolve `status => 'refunded'`, mas `WebhookController` só trata `approved`. Não há caminho `refunded`/`chargeback` → `CourseEnrollment::updateStatus(..., 'cancelled')`. Também `updateOrderStatus` (admin) só dispara evento quando vira `paid`.

**Sugestão:** evento `payment.refunded` consumido pelo Academy (`revokeEnrollment`), com idempotência.

### A3 — Banir/suspender não encerra a sessão existente *(gap da implementação recente)*
O bloqueio por `status` foi colocado **apenas no login** (`AuthController`, `StudentAuthController`). `AuthMiddleware`, `StudentDashboardController` e `CourseFrontController` **não consultam `status`**. Um aluno banido que já está logado continua assistindo aulas até a sessão expirar.

**Sugestão:** `AccountStatusGuard` (contrato no `auth`, consumido via `getLink`) no `AuthMiddleware` e nas rotas `/aluno/*` e `/cursos/*`; se `status != active` ⇒ `session->destroy()` e redirecionar. Cachear o status por requisição para não pesar no MySQL.

### A4 — Idempotência fraca
No Cenário 2, o `createOrder` duplicado é engolido por `catch` vazio e o `payment.approved` é **disparado mesmo assim**; no Cenário 1, a condição `status === 'pending'` ajuda, mas não há *lock*/unique. Gateways reenviam webhooks — emails/matrículas/notificações podem duplicar.

**Sugestão:** `UNIQUE(gateway, transaction_id)` + tabela `commerce_webhook_events` (`event_id`, `payload_hash`, `processed_at`); processar uma vez; transação PDO ao redor de `createOrder + dispatch`.

### A5 — PDFs de cursos pagos acessíveis sem matrícula
`LocalLessonPdfStorage` grava em `academy/pdfs/pdf_<slug>_<12 hex>.pdf` no storage **público**. A URL é difícil de adivinhar, mas basta ser compartilhada/indexada para burlar a matrícula. A validação do upload (extensão + `%PDF-` + MIME) está boa ✅.

**Sugestão:** gravar fora do docroot e servir via rota `/aluno/arquivos/{id}` que checa `hasActiveEnrollment` (X-Sendfile/stream).

---

## 🟡 Médios

- **M1 — Sem anti força-bruta.** `/login` e `/aluno/login` não limitam tentativas. Sugestão: `LoginThrottleInterface` (por IP + e-mail, backoff exponencial, tabela `auth_attempts`). Mensagens genéricas já existem ✅.
- **M2 — Senha de aluno com 6 caracteres.** `StudentAuthController::register` ignora o `PasswordPolicyInterface` usado no admin; o reset em `StudentAdminController` também exige apenas 6. Padronizar na política única.
- **M3 — Aluno criado por webhook fica sem acesso.** `resolveOrCreateUser` gera senha aleatória que ninguém recebe, e o login é por senha. Falta e-mail de boas-vindas com link de definição de senha (token de uso único com expiração). Hoje o cliente pagou e não consegue entrar.
- **M4 — Preço `10.00` forçado.** `CourseAdminController` L334–336: ao marcar "pago" sem preço, grava R$ 10,00 silenciosamente. Deve validar e devolver erro ao administrador.
- **M5 — Segredos em texto puro.** `storage/commerce/settings.json` guarda `access_token`, `stripe_secret_key` etc. O diretório está protegido por `storage/.htaccess` ✅ (e o root reescreve tudo para `public/`), mas entra em backups/zip de release (`dist/`, `domain-system.zip`). Sugestão: criptografia em repouso (libsodium + `APP_KEY`) e máscara na UI.
- **M6 — Valor não conferido.** O webhook marca `paid` sem comparar `transaction_amount` do gateway com `orders.total`. Um pagamento parcial/Pix alterado liberaria o curso.

## 🟢 Baixos / Débito técnico

- **B1** — `UserController` (2FA, reset, delete) usa `$request->input()` sem cast/validação (`$user_id` string direto no repositório); tipado com `(int)` como já foi feito em `changeStatus`.
- **B2** — Login admin e de aluno compartilham `user_id`/`user_role` na mesma sessão; um admin que usa `/aluno/login` mistura escopos. Considerar namespaces de sessão separados.
- **B3** — `getAllUsers()` permanece no contrato sem paginação (risco de uso futuro com 50k linhas).
- **B4** — `StudentAdminService::listStudents` faz 1 consulta de matrículas **por aluno** (N+1). Com 15/página é aceitável; crie `findActiveByStudents(array $ids)` antes de aumentar a página.
- **B5** — `status` não possui `INDEX`; `role + status + created_at` composto ajuda a busca/paginação. `LIKE '%termo%'` não usa índice — considerar `FULLTEXT` ou busca por prefixo acima de ~50k alunos.
- **B6** — Falta trilha de auditoria (quem baniu/matriculou/reembolsou, quando, motivo). Tabela `audit_log` + evento.
- **B7** — `commerce/` está **fora do controle de versão**.

---

## Pontos fortes (manter)

- Preço calculado **no servidor** a partir do curso (`CheckoutController`), nunca do cliente ✅
- `order_ref` aleatório (`random_bytes`) e CSRF no checkout/admin ✅
- Afiliados usam `hash_equals` para validar segredo ✅
- Mercado Pago confirma o pagamento consultando a API oficial ✅
- Upload de PDF com assinatura mágica + MIME + nome aleatório ✅
- Default-deny em `/admin` e barreira para papéis `user/student` ✅
- Regeneração de sessão no login (anti *session fixation*) ✅
- Isolamento Ring 3: Academy não instancia classes do `auth` (corrigido via `StudentAdminRepositoryInterface`) ✅
- SQL parametrizado em todos os repositórios revisados ✅

---

## Roteiro de correção sugerido

| Fase | Itens | Esforço |
|---|---|---|
| **1 — Bloquear fraude** (imediato) | C2, C3 (Stripe/PayPal/MP fallback), C4 | Baixo |
| **2 — Fazer o dinheiro entrar** | C1 (+ teste de integração), A4, M6 | Médio |
| **3 — Ciclo de vida do aluno** | A3, A2, M3, M1, M2 | Médio |
| **4 — Gateways reais** | A1 (Stripe Checkout Session, PayPal Orders v2) | Alto |
| **5 — Endurecimento** | A5, M5, B6, B4/B5 | Médio |

## Testes que faltam (sugestão para o Claude/Gemini escrever)
1. POST `/checkout/process` com gateway não configurado ⇒ **não** deve criar matrícula.
2. Pipeline `HttpKernel` completo: POST `/webhook/payment/hotmart` sem token CSRF com `hottok` válido ⇒ 200.
3. Webhook Stripe com assinatura inválida ⇒ 400; válida ⇒ `paid` uma única vez (reenvio ⇒ idempotente).
4. Webhook de afiliado com `product_id` inexistente ⇒ **nenhuma** matrícula.
5. Aluno logado, depois banido ⇒ próximo `GET /cursos/...` redireciona ao login.
6. `payment.refunded` ⇒ matrícula `cancelled` e `/aluno/painel` deixa de listar o curso.
7. Webhook com valor menor que `orders.total` ⇒ permanece `pending` + alerta.

## Limitações desta auditoria
- Não executei requisições HTTP reais nem contra gateways; C1 está comprovado por leitura (middleware global + ausência de isenção) e deve ser confirmado com `curl`.
- Não auditei o editor de blocos do Academy (XSS em `content` das aulas) nem o `CourseFrontController` além do controle de acesso — **ficam como próxima rodada**.
- Não revisei views (`dashboard.php`, 66 KB) quanto a XSS por escape; os campos de cliente (nome/e-mail) foram adicionados nesta sessão e devem ser conferidos com `htmlspecialchars`.
