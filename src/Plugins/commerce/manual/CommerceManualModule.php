<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial e Tutorial Interativo do Plugin DomainCommerce (Motor de Pagamentos & E-commerce).
 * Descoberto automaticamente pelo Cockpit via contrato ManualModuleInterface.
 */
class CommerceManualModule implements ManualModuleInterface
{
    public function getId(): string
    {
        return 'commerce';
    }

    public function getTitle(): string
    {
        return 'Plugin DomainCommerce (Pagamentos, Pix, Cartões & Webhooks)';
    }

    public function getSubtitle(): string
    {
        return 'Motor nativo e ultraleve de pagamentos com Mercado Pago, PayPal, Stripe e checkout unificado.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • DomainCommerce v2.6.0';
    }

    public function getAccentColor(): string
    {
        return '#00d284';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/commerce';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'commerce-configure-settings',
                'label' => '1. Acessar /admin/commerce/settings e configurar a moeda padrão (BRL) e as credenciais do Mercado Pago, PayPal ou Stripe.',
            ],
            [
                'id'    => 'commerce-view-dashboard',
                'label' => '2. Acessar o Dashboard Financeiro em /admin/commerce e verificar os KPIs de faturamento e status dos conectores ativos.',
            ],
            [
                'id'    => 'commerce-checkout-pix',
                'label' => '3. Acessar o checkout de um curso pago (/checkout/curso/{id}) e selecionar o meio de pagamento Pix gerando o Copia-e-Cola e QR Code.',
            ],
            [
                'id'    => 'commerce-checkout-card',
                'label' => '4. Simular o pagamento com Cartão de Crédito com parcelamento em até 12x via Mercado Pago ou Stripe.',
            ],
            [
                'id'    => 'commerce-webhook-ipn',
                'label' => '5. Simular o disparo do Webhook em /webhook/payment/mercadopago para validar a liquidação automática do pedido.',
            ],
            [
                'id'    => 'commerce-affiliate-webhooks',
                'label' => '6. Cadastrar os Webhooks nas plataformas externas (Hotmart, Eduzz, Monetizze ou ClickBank) e configurar os tokens em /admin/commerce/settings para auto-provisionamento de alunos.',
            ],
            [
                'id'    => 'commerce-manual-approval',
                'label' => '7. Na tabela de pedidos em /admin/commerce, clicar em "✓ Aprovar" em um pedido pendente e verificar a liberação instantânea do acesso.',
            ],
            [
                'id'    => 'commerce-student-fulfillment',
                'label' => '8. Fazer login como o aluno do pedido aprovado e confirmar que o curso aparece desbloqueado em /aluno/painel.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        ob_start();
        ?>
        <div style="color: var(--text-main, #c5c6c7); font-size: 14px; line-height: 1.75;">
            <p style="margin-top: 0;">
                O <strong>DomainCommerce (v2.6.0)</strong> é o motor de e-commerce e pagamentos nativo do Domain-System OS. Criado sob medida para substituir motores pesados de dezenas de megabytes (como o WooCommerce), o DomainCommerce pesa <strong>menos de 80 KB</strong> no disco e opera de forma 100% desacoplada através dos contratos de <strong>Inversão de Dependência (DIP)</strong> e eventos de Kernel.
            </p>

            <!-- SEÇÃO 1: OS CONECTORES DE PAGAMENTO -->
            <h3 style="color: #00d284; font-size: 15px; margin: 22px 0 12px;">1. Conectores e Gateways Nativos</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 24px;">
                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🇧🇷 Mercado Pago (Mercado Livre)</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Ideal para vendas no Brasil. Suporta <strong>Pix Instantâneo</strong> (QR Code dinâmico e código Copia-e-Cola), <strong>Cartão de Crédito nacional parcelado em até 12x</strong> e <strong>Boleto Bancário</strong>.
                    </div>
                </div>

                <div style="background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #45f3ff; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🌎 PayPal (Vendas Internacionais)</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Ideal para receber de alunos e clientes do exterior em <strong>Dólar (USD)</strong>, <strong>Euro (EUR)</strong> ou <strong>Libras (GBP)</strong> com checkout seguro ou saldo da conta.
                    </div>
                </div>

                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">💳 Stripe (Cartões Globais)</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Checkout otimizado com suporte a cartões de crédito de mais de 135 países e liquidação instantânea via webhook <code>checkout.session.completed</code>.
                    </div>
                </div>

                <div style="background: rgba(245, 166, 35, 0.08); border: 1px solid rgba(245, 166, 35, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #f5a623; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🔌 Extensibilidade Aberta ("ou outros")</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Conecte qualquer outro provedor (como Asaas, PagSeguro, Efí/Gerencianet) implementando o contrato <code>PaymentGatewayInterface</code> em poucas linhas.
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 2: FLUXO DE CHECKOUT E INTEGRAÇÃO -->
            <h3 style="color: #fff; font-size: 15px; margin: 22px 0 10px;">2. Como Funciona a Ponte com a Academy</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <ol style="margin: 0 0 0 18px; padding: 0; line-height: 1.9; font-size: 13.5px;">
                    <li style="margin-bottom: 8px;">
                        <strong>Desacoplamento por Contrato (Sem Dependência Direta de SQL):</strong>
                        O DomainCommerce não faz <code>SELECT</code> nas tabelas da Academy. A Academy expõe seus cursos através do contrato <code>PurchasableItemInterface</code>.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Início do Checkout (<code>/checkout/curso/{id}</code>):</strong>
                        O aluno clica no botão "Matricular-se" na aula restrita e é direcionado para a tela de checkout segura.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Geração do Pedido:</strong>
                        O sistema gera um código de pedido único (ex: <code>ORD_20261104_A1B2C3</code>) gravado na tabela <code>commerce_orders</code> com status <code>pending</code>.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Liquidação Instantânea via Eventos:</strong>
                        Assim que o pagamento é aprovado (no checkout ou via Webhook), o DomainCommerce dispara o evento <code>payment.approved</code>. O serviço <code>AcademyCourseFulfillment</code> escuta esse evento e ativa a matrícula do aluno no mesmo segundo!
                    </li>
                </ol>
            </div>

            <!-- SEÇÃO 3: ROTAS E ENDPOINTS DE WEBHOOK -->
            <h3 style="color: #45f3ff; font-size: 15px; margin: 20px 0 10px;">3. Rotas Administrativas e Endpoints de Webhook</h3>
            <div style="background: rgba(69, 243, 255, 0.06); border: 1px solid rgba(69, 243, 255, 0.25); border-radius: 8px; padding: 14px 18px; margin-bottom: 18px; font-size: 13px;">
                <div><strong>• Painel de Vendas:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/admin/commerce" target="_blank" style="color:#00d284;"><code><?= htmlspecialchars($baseUrl) ?>/admin/commerce</code></a></div>
                <div style="margin-top: 4px;"><strong>• Configurações de Gateways:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/admin/commerce/settings" target="_blank" style="color:#45f3ff;"><code><?= htmlspecialchars($baseUrl) ?>/admin/commerce/settings</code></a></div>
                <div style="margin-top: 4px;"><strong>• Webhook Mercado Pago:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/mercadopago</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook PayPal:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/paypal</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook Stripe:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/stripe</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook Hotmart:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/hotmart</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook Eduzz:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/eduzz</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook Monetizze:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/monetizze</code></div>
                <div style="margin-top: 4px;"><strong>• Webhook ClickBank:</strong> <code><?= htmlspecialchars($baseUrl) ?>/webhook/payment/clickbank</code></div>
            </div>

            <!-- DESTAQUE ARQUITETURAL SOLID -->
            <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid #00d284; padding: 12px 16px; margin: 16px 0 0; border-radius: 0 6px 6px 0; font-size: 13px;">
                <strong>Blindagem de Segurança &amp; LGPD:</strong>
                O DomainCommerce nunca armazena números de cartões de crédito ou CVVs no banco de dados local. Toda transação com cartão é tokenizada diretamente pelas APIs dos gateways. Configurações sensíveis são guardadas em JSON atômico protegido em <code>storage/commerce/settings.json</code>.
            </div>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
