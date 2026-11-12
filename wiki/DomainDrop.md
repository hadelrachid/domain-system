> 🇧🇷 [Ler em Português](#-domaindrop--dropshipping-com-sdk-do-aliexpress) | 🇺🇸 [Read in English](#-domaindrop--aliexpress-sdk-dropshipping-engine)

---

# 🇧🇷 DomainDrop — Dropshipping com SDK do AliExpress (v2.7.0)

O **DomainDrop** (`src/Plugins/domain_drop`) é o motor autônomo de comércio eletrônico e dropshipping do **Domain System**. Ele conecta diretamente o protocolo **AliExpress Open Platform SDK** (`TOP` com assinatura `HMAC-SHA256` e OAuth 2.0) a uma vitrine de alta conversão, sem depender de plataformas terceiras pesadas.

---

## 🚀 Arquitetura e Funcionalidades

1. **SDK Oficial AliExpress Open Platform (`AliExpressApiClientInterface`):**
   - Consulta de detalhes de produtos (`aliexpress.ds.product.get`) e feeds recomendados (`aliexpress.ds.recommend.feed.get`).
   - Cálculo de frete (`aliexpress.logistics.buyer.freight.get`) e criação automatizada de ordens (`aliexpress.trade.buy.placeorder`).
   - Rastreamento logístico em tempo real (`queryTrackingInfo`) integrado aos Correios e à Cainiao Global.
2. **Mineração Autônoma & Ciclo Flash de 24h (`ProductMiningServiceInterface`):**
   - Curadoria que prioriza estoque local no Brasil (Entreposto Nacional com entrega rápida) com fallback global.
   - Conversão cambial dinâmica (`USD → BRL`) via `CurrencyConverterInterface` com margem configurável.
   - Enriquecimento de copywriting com Inteligência Artificial (`AiProductEnricherInterface`).
   - Sincronização do cronômetro regressivo de 24h da Home com o ciclo diário do servidor e cabeçalhos HTTP anti-cache (`CachePolicy`).
3. **Espaço do Cliente Completo (`/minha-conta`, `/entrar`, `/cadastro`):**
   - Autenticação isolada em `domain_drop_customers` (separada da tabela `users` administrativa).
   - **🛰️ Acompanhamento de Entrega Integrado:** Linha do tempo de 5 etapas dentro da conta do cliente com links diretos para **Correios**, **Cainiao** e eventos do SDK.
   - **💬 Reclamações, Observações & Dúvidas:** Abertura de solicitações com upload seguro de fotos/PDF (até 4 arquivos com validação de *magic bytes*) e **vinculação opcional de ordem de pedido ou produto da loja**.
4. **Gestão Administrativa de Clientes, Pedidos & Mediação Bilingue (`/admin/dropship`):**
   - **👥 Clientes & Compradores (`/admin/dropship/customers`):** Listagem estilo CRM com busca por nome/e-mail/CPF, métricas de compras/chamados, ativação/suspensão de conta e histórico de chamados recentes.
   - **📦 Pedidos, SLA & Disputas (`/admin/dropship/orders`):** Barra de progresso de SLA e **Mediação 1-Clique com Fornecedor** (`SellerMediationService`), traduzindo automaticamente chamados para **Chinês Simplificado (`zh-CN`)** para vendedores internacionais (`CN`) ou mantendo em **Português (`pt-BR`)** para fornecedores nacionais (`BR`).
5. **Isolamento de Temas Nativos (`"flex_builder": false`):**
   - Temas dedicados de e-commerce (`rachidd_store`) declaram `"flex_builder": false` no `theme.json`, isolando seu cabeçalho e rodapé nativos da edição de partes do Flex Builder.

---

<br>

# 🇺🇸 DomainDrop — AliExpress SDK Dropshipping Engine (v2.7.0)

**DomainDrop** (`src/Plugins/domain_drop`) is the autonomous dropshipping and physical e-commerce engine for **Domain System**, integrating the **Official AliExpress Open Platform SDK** (`TOP` protocol with `HMAC-SHA256` signatures and OAuth 2.0) into our ~30 MB core.

---

## 🚀 Architecture & Features

1. **Official AliExpress Open Platform SDK (`AliExpressApiClientInterface`):**
   - Product details (`aliexpress.ds.product.get`), recommendation feeds (`aliexpress.ds.recommend.feed.get`), freight calculation (`aliexpress.logistics.buyer.freight.get`), order placement (`aliexpress.trade.buy.placeorder`), and live logistics tracking (`queryTrackingInfo`).
2. **Autonomous Mining & 24h Flash Cycle (`ProductMiningServiceInterface`):**
   - Prioritizes Brazil domestic warehouse stock with global fallback, dynamic `USD → BRL` conversion (`CurrencyConverterInterface`), AI copywriting enrichment (`AiProductEnricherInterface`), and HTTP anti-cache policies (`CachePolicy`).
3. **Customer Portal (`/minha-conta`, `/entrar`, `/cadastro`):**
   - Isolated customer authentication (`domain_drop_customers`), built-in 5-stage shipment tracking (Correios + Cainiao + AliExpress SDK), and ticket/complaint submission with photo/PDF attachments and **optional order or store product linking**.
4. **Admin CRM, SLA & Bilingual Supplier Mediation (`/admin/dropship`):**
   - **👥 Customers & Buyers (`/admin/dropship/customers`):** CRM directory with search, metrics, status controls, and recent tickets.
   - **📦 Orders, SLA & Disputes (`/admin/dropship/orders`):** Delivery SLA progress bars and 1-click **Supplier Mediation** (`SellerMediationService`) with automatic translation to **Simplified Chinese (`zh-CN`)** for Chinese suppliers (`CN`) or **Portuguese (`pt-BR`)** for Brazilian suppliers (`BR`).
