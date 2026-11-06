# 💳 Plano de Implementação: Plugin Commerce & Gateway de Pagamentos (Pix, Cartão de Crédito e Webhooks)

> **Status:** 🎯 Alta Prioridade (Próximo Ciclo — v2.6.0)  
> **Arquitetura:** Orientada a Contratos (SOLID / DIP), desacoplada de fornecedores específicos.  
> **Compatibilidade:** PHP 8.2+, SQLite e MySQL/MariaDB via PDO, isolamento com Circuit Breaker.

---

## 🎯 1. Visão Geral e Proposta de Valor

O objetivo deste projeto é dotar o **Domain System** de um motor nativo de comércio eletrônico e liquidação financeira (**Commerce**), operando como uma alternativa leve, segura e desacoplada aos CMSs tradicionais e seus módulos pesados de e-commerce.

### Principais Benefícios:
1. **Multi-Gateway Desacoplado:** O núcleo de pedidos não depende de nenhuma instituição financeira; provedores (Mercado Pago, Stripe, Asaas, Efí/Gerencianet) implementam contratos padronizados (`PaymentGatewayInterface`).
2. **Liquidação Instantânea via Pix:** Geração nativa de QR Code e payload copia-e-cola com confirmação assíncrona em tempo real via Webhook criptografado.
3. **Cartão de Crédito com Tokenização Segura:** Processamento de pagamentos parcelados com suporte a tokenização no client-side (PCI-DSS compliance) sem tráfego de dados sensíveis pelo servidor.
4. **Integração Nativa com o Academy LMS:** Evento `commerce.order_paid` dispara automaticamente a matrícula do aluno no curso correspondente sem intervenção manual.
5. **Blocos Visuais para o Flex Builder FSE:** Componentes visuais arrastáveis para cabeçalho, rodapé e páginas centrais (`product_card`, `cart_button`, `checkout_embed`).

---

## 🏛️ 2. Arquitetura de Contratos (SOLID & DIP)

O ecossistema será dividido em contratos estritos em `src/Plugins/commerce/Contracts/`:

```
src/Plugins/commerce/
├── plugin.json
├── Plugin.php                           # Registra rotas, menus no Cockpit e hooks de eventos
├── Contracts/
│   ├── OrderInterface.php
│   ├── OrderItemInterface.php
│   ├── OrderRepositoryInterface.php
│   ├── ProductInterface.php
│   ├── ProductRepositoryInterface.php
│   ├── PaymentGatewayInterface.php      # Contrato fundamental para qualquer gateway
│   ├── PaymentGatewayRegistryInterface.php
│   └── WebhookHandlerInterface.php
├── Repositories/
│   ├── PdoOrderRepository.php           # ANSI SQL puro para SQLite e MySQL
│   └── PdoProductRepository.php
├── Services/
│   ├── CheckoutService.php              # Cálculo de totais, cupons e emissão de cobrança
│   ├── PaymentGatewayRegistry.php       # Registro e seleção de gateways ativos
│   ├── WebhookDispatcherService.php     # Validação de assinatura criptográfica e disparo de eventos
│   └── Gateways/
│       ├── MercadoPagoGateway.php       # Pix nativo + Cartão de Crédito
│       └── StripeGateway.php            # Cartão internacional e carteiras digitais
├── Controllers/
│   ├── CheckoutController.php           # Telas públicas de carrinho e finalização
│   ├── WebhookController.php            # Endpoint público de recebimento de notificações
│   └── OrdersAdminController.php        # Gestão de vendas e relatórios no painel administrativo
├── migrations/
│   ├── 2026_11_03_000001_create_commerce_products_table.php
│   ├── 2026_11_03_000002_create_commerce_orders_table.php
│   └── 2026_11_03_000003_create_commerce_order_items_table.php
├── manual/
│   └── module.php                       # Manual auto-descoberto no Cockpit (/admin/manual)
└── views/
    ├── admin/                           # Listagem de pedidos, faturamento e status
    └── front/                           # Layouts responsivos de checkout e comprovante Pix
```

---

## ⚙️ 3. O Contrato do Gateway de Pagamento

```php
declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\DTO\CreditCardPayload;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use DomainSystem\Plugins\commerce\DTO\PixResult;
use DomainSystem\Plugins\commerce\DTO\WebhookResult;

interface PaymentGatewayInterface
{
    /** Identificador interno único (ex: 'mercadopago', 'stripe', 'asaas') */
    public function getId(): string;

    /** Nome legível para o cliente no checkout */
    public function getName(): string;

    /** Indica se o gateway suporta Pix instantâneo */
    public function supportsPix(): bool;

    /** Indica se o gateway suporta Cartão de Crédito */
    public function supportsCreditCard(): bool;

    /** Gera cobrança Pix com QR Code em Base64 e Copia-e-Cola */
    public function generatePix(OrderInterface $order): PixResult;

    /** Processa cobrança via Cartão de Crédito com token seguro */
    public function processCreditCard(OrderInterface $order, CreditCardPayload $payload): PaymentResult;

    /** Valida assinatura, extrai os dados do webhook e retorna o status atualizado */
    public function handleWebhook(RequestInterface $request): WebhookResult;
}
```

---

## 🔄 4. Fluxo de Execução e Eventos

### 4.1. Fluxo de Compra e Pagamento Pix:
```
[Cliente] -> Clica em "Comprar Agora"
   ↓
[CheckoutService] -> Cria pedido em 'ds_orders' com status 'pending'
   ↓
[PaymentGatewayInterface::generatePix()] -> Comunica com o banco/gateway via CurlHttpClient
   ↓
[Tela de Checkout] -> Exibe QR Code + Linha Copia-e-Cola + Polling/SSE de confirmação
   ↓
[Cliente Efetua o Pagamento no App Bancário]
   ↓
[Webhook do Banco] -> Envia POST para /api/commerce/webhook/{gateway}
   ↓
[WebhookController] -> Valida assinatura criptográfica e chama $gateway->handleWebhook()
   ↓
[CheckoutService] -> Atualiza status para 'paid' em transação atômica
   ↓
[EventDispatcher] -> Dispara evento 'commerce.order_paid'
   ├── Listener Academy: Matricula o aluno e libera as aulas imediatamente
   └── Listener Email: Envia comprovante de pagamento e boas-vindas
```

---

## 🛡️ 5. Blindagem e Segurança (OWASP / Zero-Trust)

1. **Validação Estrita de Webhook:** Rejeição imediata (`401 Unauthorized`) de webhooks que não possuam assinatura HMAC válida ou segredo de token compatível.
2. **Proteção contra Race Conditions:** Atualização do status do pedido com controle de versão/lock atômico, impedindo duplicação de matrículas ou entrega de mercadorias duplicadas em notificações concorrentes.
3. **Isolamento de Falhas (Circuit Breaker):** Caso uma API externa de gateway enfrente lentidão ou instabilidade de rede, as falhas são contidas sem paralisar o catálogo de páginas ou o painel administrativo.
4. **Zero Armazenamento de Dados de Cartão:** O servidor **nunca** armazena números de cartão, CVV ou data de expiração, delegando a captura aos componentes SDK autorizados do gateway.

---

## 🚀 6. Cronograma de Execução

- [ ] **Fase 1: Fundação de Dados e Entidades:**
  - Migrações das tabelas `ds_products`, `ds_orders` e `ds_order_items`.
  - Repositórios com ANSI SQL e injeção de dependência via PDO.
- [ ] **Fase 2: Motor de Checkout e Contratos:**
  - Interfaces de gateway e DTOs de pagamento.
  - Implementação do `CheckoutService` e cálculo de frete/cupons.
- [ ] **Fase 3: Primeiro Provedor (Mercado Pago / Pix):**
  - Integração com API de Pix (geração de QR Code em SVG/Base64).
  - Receptor de Webhook seguro e tela de pagamento em tempo real.
- [ ] **Fase 4: Integração de Eventos com o Academy LMS:**
  - Listener para liberação instantânea de módulos de cursos.
- [ ] **Fase 5: Blocos Flex Builder FSE & Painel Cockpit:**
  - Dashboard de vendas, faturamento e clientes no painel administrativo.
  - Bloco `[product_card id="..."]` e carrinho de compras.
