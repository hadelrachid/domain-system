<?php

declare(strict_types=1);

/**
 * View Pública: Confirmação de Pedido e Pagamento Aprovado (Commerce / Dropshipping).
 *
 * @var string $baseUrl
 * @var array<string, mixed> $order
 * @var array<string, mixed>|null $item
 * @var array<string, mixed>|null $dropshipOrder
 */
$baseUrl = $baseUrl ?? '';
$orderRef = htmlspecialchars((string)($order['order_ref'] ?? ''));
$total = number_format((float)($order['total'] ?? 0.0), 2, ',', '.');
$itemTitle = htmlspecialchars((string)($item['title'] ?? 'Produto Internacional'));
$itemImage = htmlspecialchars((string)($item['image'] ?? ''));
$customerName = htmlspecialchars((string)($dropshipOrder['customer_name'] ?? ($order['customer_name'] ?? 'Cliente')));
$shipping = $dropshipOrder['shipping_address'] ?? null;
if (is_string($shipping)) {
    $shipping = json_decode($shipping, true);
}
?>
<section class="ds-success-page">
    <style>
        .ds-success-page {
            --bg-body: #06090e;
            --bg-card: #0f141c;
            --accent-green: #00d284;
            --accent-gold: #F0D060;
            --border-color: rgba(212, 175, 55, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            padding: 40px 16px 64px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .ds-success-page * { box-sizing: border-box; }
        .success-box { max-width: 680px; width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 36px 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); }
        .success-badge { width: 72px; height: 72px; background: rgba(0,210,132,0.15); border: 2px solid var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; margin: 0 auto 20px auto; color: var(--accent-green); box-shadow: 0 0 24px rgba(0,210,132,0.3); }
        .order-title { text-align: center; font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .order-subtitle { text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 28px; line-height: 1.5; }
        .info-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; margin-bottom: 22px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .btn-continue { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 15px; padding: 14px 20px; border-radius: 8px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 20px rgba(212,175,55,0.35); transition: transform 0.2s; margin-top: 24px; }
        .btn-continue:hover { transform: translateY(-2px); }
    </style>

    <div class="success-box">
        <div class="success-badge">✓</div>
        <h1 class="order-title">Pagamento Aprovado!</h1>
        <p class="order-subtitle">
            Obrigado, <strong><?= $customerName ?></strong>! Seu pedido na <strong>RachidD.com</strong> foi confirmado e nossa curadoria já acionou o fornecedor para envio com rastreio e garantia de entrega.
        </p>

        <!-- Detalhes do Pedido -->
        <div class="info-card">
            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <?php if (!empty($itemImage)): ?>
                    <img src="<?= $itemImage ?>" alt="<?= $itemTitle ?>" style="width: 74px; height: 74px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                <?php endif; ?>
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 4px;"><?= $itemTitle ?></h3>
                    <span style="font-size: 13px; color: var(--accent-green); font-weight: 700;">R$ <?= $total ?></span>
                </div>
            </div>

            <div class="info-row">
                <span style="color: var(--text-muted);">Código do Pedido:</span>
                <span style="font-family: monospace; color: var(--accent-gold); font-weight: 700;"><?= $orderRef ?></span>
            </div>
            <div class="info-row">
                <span style="color: var(--text-muted);">Status do Pagamento:</span>
                <span style="color: var(--accent-green); font-weight: 700;">● Aprovado</span>
            </div>
            <div class="info-row">
                <span style="color: var(--text-muted);">Status de Envio:</span>
                <span style="color: #60a5fa; font-weight: 600;">Em Separação no Fabricante</span>
            </div>
            <div class="info-row">
                <span style="color: var(--text-muted);">Frete com Seguro:</span>
                <span style="color: #fff; font-weight: 600;">Curadoria AliExpress Brasil (Rastreado)</span>
            </div>
        </div>

        <!-- Endereço de Entrega -->
        <?php if (!empty($shipping) && is_array($shipping)): ?>
            <div class="info-card">
                <h4 style="font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <span>📍</span> Endereço para Entrega
                </h4>
                <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6;">
                    <strong><?= htmlspecialchars((string)($shipping['street'] ?? '')) ?>, <?= htmlspecialchars((string)($shipping['number'] ?? 'S/N')) ?></strong>
                    <?php if (!empty($shipping['complement'])): ?>
                        - <?= htmlspecialchars((string)$shipping['complement']) ?>
                    <?php endif; ?><br>
                    <?= htmlspecialchars((string)($shipping['neighborhood'] ?? '')) ?> - <?= htmlspecialchars((string)($shipping['city'] ?? '')) ?>/<?= htmlspecialchars((string)($shipping['state'] ?? '')) ?><br>
                    CEP: <?= htmlspecialchars((string)($shipping['zip'] ?? '')) ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="background: rgba(69, 243, 255, 0.05); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #94a3b8; line-height: 1.5;">
            ℹ️ <strong>Rastreamento:</strong> O código de rastreio dos Correios será gerado e enviado para o seu e-mail e WhatsApp assim que o produto for postado pelo fabricante (geralmente em até 24 a 48 horas úteis).
        </div>

        <a href="<?= $baseUrl ?>/loja" class="btn-continue">
            Explorar Mais Ofertas na Loja ➔
        </a>
    </div>
</section>
<script>document.title = <?= json_encode('Pedido Confirmado | ' . html_entity_decode($orderRef, ENT_QUOTES, 'UTF-8') . ' — RachidD.com', JSON_UNESCAPED_UNICODE) ?>;</script>
