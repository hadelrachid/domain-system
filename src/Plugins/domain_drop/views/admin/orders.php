<?php
/**
 * View Administrativa: Gestão de Pedidos, Barra Regressiva de SLA e Intermediação com Fornecedores
 *
 * @var array<int, array<string, mixed>> $orders
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 * @var string $status
 * @var string $search
 */
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$totalOrders = count($orders);
$okDeliveredCount = 0;
$delayedCount = 0;
$disputeCount = 0;
$registeredCount = 0;

foreach ($orders as $ordItem) {
    $st = $ordItem['sla']['state'] ?? '';
    if ($st === 'ok_delivered') {
        $okDeliveredCount++;
    } elseif ($st === 'delayed_awaiting_customer') {
        $delayedCount++;
    } elseif ($st === 'complaint_open') {
        $disputeCount++;
    }
    if (!empty($ordItem['is_registered_customer'])) {
        $registeredCount++;
    }
}
?>
<div class="domain-drop-orders" style="max-width: 1460px; margin: 0 auto;">

    <!-- Top Header & Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <a href="<?= $e($baseUrl) ?>/admin/dropship" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fas fa-satellite-dish"></i> DomainDrop
                </a>
                <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
                <span style="color: #ff4747; font-size: 13px; font-weight: 600;">Pedidos, SLA &amp; Mediação</span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shipping-fast" style="color: #ff4747;"></i>
                Pedidos Dropshipping, Prazo Regressivo &amp; Intermediação
            </h1>
            <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Barra regressiva de entrega por cliente cadastrado (7 dias Brasil / 25 dias China), checagem de entrega via SDK e botão único de intermediação multilíngue (PT-BR / zh-CN).
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="<?= $e($baseUrl) ?>/admin/dropship" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-boxes"></i> Ver Catálogo
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 18px;">
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px 16px;">
            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Total / Cadastrados</div>
            <div style="font-size: 22px; font-weight: 800; color: #fff; margin-top: 4px;"><?= $totalOrders ?> <span style="font-size: 13px; color: #F0D060;">(<?= $registeredCount ?> cad.)</span></div>
        </div>
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(16,185,129,0.28); border-radius: 8px; padding: 14px 16px;">
            <div style="font-size: 11px; color: #6ee7b7; text-transform: uppercase; font-weight: 700;">Tudo Ok! Entregues</div>
            <div style="font-size: 22px; font-weight: 800; color: #10b981; margin-top: 4px;"><?= $okDeliveredCount ?></div>
        </div>
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(245,158,11,0.28); border-radius: 8px; padding: 14px 16px;">
            <div style="font-size: 11px; color: #fcd34d; text-transform: uppercase; font-weight: 700;">Atraso (Aguardando Cliente)</div>
            <div style="font-size: 22px; font-weight: 800; color: #f59e0b; margin-top: 4px;"><?= $delayedCount ?></div>
        </div>
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(239,68,68,0.35); border-radius: 8px; padding: 14px 16px;">
            <div style="font-size: 11px; color: #fca5a5; text-transform: uppercase; font-weight: 700;">Com Reclamação Aberta</div>
            <div style="font-size: 22px; font-weight: 800; color: #ef4444; margin-top: 4px;"><?= $disputeCount ?></div>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($flashMsg) && $flashType === 'error'): ?>
        <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?= $e($flashMsg) ?>
        </div>
    <?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
        <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?= $e($flashMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form action="<?= $e($baseUrl) ?>/admin/dropship/orders" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
            <div style="position: relative; min-width: 260px; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted, #94a3b8); font-size: 13px;"></i>
                <input type="text" name="q" value="<?= $e($search ?? '') ?>" placeholder="Buscar por referência, cliente, e-mail ou rastreio..." style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px 8px 34px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
            </div>

            <select name="status" onchange="this.form.submit()" style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                <option value="">Todos os Status</option>
                <option value="pending" <?= (($status ?? '') === 'pending') ? 'selected' : '' ?>>Pendente</option>
                <option value="paid" <?= (($status ?? '') === 'paid') ? 'selected' : '' ?>>Pago (Aguardando Despacho)</option>
                <option value="shipped" <?= (($status ?? '') === 'shipped') ? 'selected' : '' ?>>Enviado / Com Rastreio</option>
                <option value="delivered" <?= (($status ?? '') === 'delivered') ? 'selected' : '' ?>>Entregue</option>
            </select>

            <button type="submit" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="<?= $e($baseUrl) ?>/admin/dropship/orders" style="color: var(--text-muted, #94a3b8); font-size: 12px; text-decoration: none;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="font-size: 13px; color: var(--text-muted, #94a3b8);">
            Total de Pedidos: <strong style="color: #fff;"><?= $totalOrders ?></strong>
        </div>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div style="background: var(--bg-panel, #1d2327); border: 1px dashed var(--border, rgba(255,255,255,0.15)); border-radius: 8px; padding: 60px 20px; text-align: center;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-muted, #94a3b8); margin-bottom: 16px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #fff; font-size: 18px;">Nenhum pedido de dropshipping recebido</h3>
            <p style="margin: 0 0 20px 0; color: var(--text-muted, #94a3b8); font-size: 13px; max-width: 480px; margin-left: auto; margin-right: auto;">
                Assim que um cliente comprar um produto na sua vitrine via DomainCommerce, o pedido aparecerá aqui com barra regressiva de SLA e acompanhamento pós-venda.
            </p>
        </div>
    <?php else: ?>
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.2);">
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Referência</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Cliente</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff; min-width: 310px;">Prazo Regressivo &amp; Pós-Venda</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Status / Rastreio</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff; text-align: right;">Ações de Envio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $ordStatus = (string)($o['status'] ?? 'pending');
                        $badges = [
                            'pending'   => ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#fbbf24', 'label' => 'Pendente'],
                            'paid'      => ['bg' => 'rgba(40, 167, 69, 0.15)', 'color' => '#5cd67d', 'label' => 'Pago'],
                            'shipped'   => ['bg' => 'rgba(69, 243, 255, 0.15)', 'color' => '#45f3ff', 'label' => 'Enviado'],
                            'delivered' => ['bg' => 'rgba(16, 185, 129, 0.18)', 'color' => '#34d399', 'label' => 'Entregue'],
                        ];
                        $b = $badges[$ordStatus] ?? ['bg' => 'rgba(255,255,255,0.1)', 'color' => '#fff', 'label' => $ordStatus];

                        $sla = is_array($o['sla'] ?? null) ? $o['sla'] : [];
                        $slaState = (string)($sla['state'] ?? 'in_transit_countdown');
                        $slaOrigin = (string)($sla['origin'] ?? 'BR');
                        $barColor = match ($sla['tone'] ?? 'info') {
                            'success' => '#10b981',
                            'danger'  => '#ef4444',
                            'warning' => '#f59e0b',
                            'amber'   => '#f59e0b',
                            default   => '#3b82f6',
                        };
                        $ordDisputes = is_array($o['disputes'] ?? null) ? $o['disputes'] : [];
                        $latestDispute = $ordDisputes[0] ?? null;
                        ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); vertical-align: top;">
                            <td style="padding: 14px 16px; font-family: monospace; font-weight: 600; color: #fff;">
                                <?= $e($o['order_ref'] ?? '') ?>
                                <div style="font-size: 11px; color: var(--text-muted, #94a3b8); font-family: sans-serif; margin-top: 2px;">
                                    <?= $e($o['created_at'] ?? '') ?>
                                </div>
                                <div style="font-weight: 700; color: #5cd67d; font-size: 12.5px; margin-top: 6px;">
                                    R$ <?= number_format((float)($o['total_paid_brl'] ?? 0.0), 2, ',', '.') ?>
                                </div>
                            </td>

                            <td style="padding: 14px 16px;">
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-weight: 600; color: #fff;"><?= $e($o['customer_name'] ?? 'Cliente') ?></span>
                                    <?php if (!empty($o['is_registered_customer'])): ?>
                                        <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 99px; background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.35);">Cadastrado</span>
                                    <?php else: ?>
                                        <span style="font-size: 10px; padding: 2px 6px; border-radius: 99px; background: rgba(255,255,255,0.06); color: #94a3b8;">Avulso</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted, #94a3b8); margin-top: 2px;"><?= $e($o['customer_email'] ?? '') ?></div>
                                <div style="font-size: 11px; margin-top: 6px; color: #cbd5e1;">
                                    Fornecedor: <strong><?= $slaOrigin === 'BR' ? '🇧🇷 Brasil (7d)' : '🇨🇳 China (' . (int)($sla['expected_days'] ?? 25) . 'd)' ?></strong>
                                </div>
                            </td>

                            <td style="padding: 14px 16px;">
                                <!-- Barra Regressiva de Entrega -->
                                <div style="background: rgba(0,0,0,0.28); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 11.5px; margin-bottom: 6px;">
                                        <strong style="color: <?= $barColor ?>;">
                                            <?php if ($slaState === 'ok_delivered'): ?>
                                                ✅ <?= $e($sla['label'] ?? '') ?>
                                            <?php elseif ($slaState === 'delayed_awaiting_customer'): ?>
                                                ⏳ <?= $e($sla['label'] ?? '') ?>
                                            <?php elseif ($slaState === 'complaint_open'): ?>
                                                🚨 <?= $e($sla['label'] ?? '') ?>
                                            <?php else: ?>
                                                🚚 <?= $e($sla['label'] ?? '') ?>
                                            <?php endif; ?>
                                        </strong>
                                        <span style="color: #94a3b8; font-family: monospace;"><?= (int)($sla['percent_remaining'] ?? 0) ?>%</span>
                                    </div>

                                    <div style="width: 100%; height: 9px; background: rgba(255,255,255,0.09); border-radius: 99px; overflow: hidden;">
                                        <div style="width: <?= (int)($sla['bar_percent'] ?? 0) ?>%; height: 100%; background: <?= $barColor ?>; transition: width 0.3s ease;"></div>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; font-size: 11px; color: #94a3b8;">
                                        <span>Decorridos: <?= (int)($sla['days_elapsed'] ?? 0) ?>d / <?= (int)($sla['expected_days'] ?? 7) ?>d</span>
                                        <?php if (!empty($o['tracking_code'])): ?>
                                            <form action="<?= $e($baseUrl) ?>/admin/dropship/orders/<?= (int)($o['id'] ?? 0) ?>/check-sdk" method="POST" style="margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                <input type="hidden" name="order_ref" value="<?= $e($o['order_ref'] ?? '') ?>">
                                                <button type="submit" style="background: rgba(59,130,246,0.14); border: 1px solid rgba(59,130,246,0.38); color: #93c5fd; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 700; cursor: pointer;">
                                                    🔄 Sincronizar SDK
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Bloco de Reclamação do Cliente + Botão Único de Intermediação -->
                                <?php if (is_array($latestDispute)): ?>
                                    <?php
                                    $dispSellerOrigin = strtoupper((string)($latestDispute['seller_origin'] ?: $slaOrigin));
                                    $isChineseSeller = $dispSellerOrigin !== 'BR';
                                    $isSent = ($latestDispute['mediation_status'] ?? 'open') === 'sent';
                                    ?>
                                    <div style="margin-top: 10px; padding: 10px 12px; background: rgba(239,68,68,0.09); border: 1px solid rgba(239,68,68,0.35); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 6px; font-size: 11px; margin-bottom: 4px;">
                                            <strong style="color: #fca5a5;">Motivo: <?= $e($latestDispute['reason'] ?? '') ?></strong>
                                            <span style="color: <?= $isSent ? '#6ee7b7' : '#fcd34d' ?>; font-weight: 700;">
                                                <?= $isSent ? '✅ Mediação Enviada' : '⚠️ Pendente' ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 12px; color: #e2e8f0; line-height: 1.45; margin-bottom: 8px;">
                                            <?= nl2br($e($latestDispute['description'] ?? '')) ?>
                                        </div>

                                        <?php if (!empty($latestDispute['attachments'])): ?>
                                            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
                                                <?php foreach ($latestDispute['attachments'] as $att): ?>
                                                    <a href="<?= $e($baseUrl) ?>/dropship/disputes/attachment/<?= (int)($latestDispute['id'] ?? 0) ?>/<?= $e(rawurlencode((string)($att['filename'] ?? ''))) ?>" target="_blank" style="font-size: 11px; color: #93c5fd; background: rgba(59,130,246,0.16); border: 1px solid rgba(59,130,246,0.35); padding: 3px 8px; border-radius: 5px; text-decoration: none;">
                                                        📎 <?= $e($att['original_name'] ?? $att['filename'] ?? 'Anexo') ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Botão Único de Intermediação -->
                                        <form action="<?= $e($baseUrl) ?>/admin/dropship/orders/<?= (int)($o['id'] ?? 0) ?>/mediate" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                            <input type="hidden" name="order_ref" value="<?= $e($o['order_ref'] ?? '') ?>">
                                            <input type="hidden" name="dispute_id" value="<?= (int)($latestDispute['id'] ?? 0) ?>">
                                            <input type="hidden" name="seller_origin" value="<?= $e($dispSellerOrigin) ?>">

                                            <button type="submit" style="width: 100%; padding: 8px 12px; border: none; border-radius: 6px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-size: 12px; font-weight: 800; cursor: pointer;">
                                                🤝 <?= $isSent ? 'Reenviar Intermediação ao Fornecedor' : 'Enviar Intermediação ao Fornecedor' ?>
                                                (<?= $isChineseSeller ? '🇨🇳 Traduzir p/ Chinês zh-CN' : '🇧🇷 Manter em Português' ?>)
                                            </button>
                                        </form>

                                        <?php if (!empty($latestDispute['translated_message'])): ?>
                                            <details style="margin-top: 8px;">
                                                <summary style="cursor: pointer; font-size: 11px; color: #F0D060; font-weight: 700;">Ver mensagem gerada (<?= $e($latestDispute['seller_language'] ?? '') ?>)</summary>
                                                <pre style="margin: 6px 0 0 0; padding: 8px; background: rgba(0,0,0,0.45); border-radius: 6px; color: #e2e8f0; font-size: 11px; white-space: pre-wrap; max-height: 180px; overflow-y: auto;"><?= $e($latestDispute['translated_message']) ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px 16px;">
                                <span style="background: <?= $b['bg'] ?>; color: <?= $b['color'] ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; margin-bottom: 8px;">
                                    <?= $e($b['label']) ?>
                                </span>
                                <?php if (!empty($o['aliexpress_order_id'])): ?>
                                    <div style="font-size: 12px; color: #fff;">
                                        AliExpress: <code style="color: #ff6b6b;"><?= $e($o['aliexpress_order_id']) ?></code>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($o['tracking_code'])): ?>
                                    <div style="font-size: 12px; color: #45f3ff; margin-top: 3px; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-barcode"></i> <?= $e($o['tracking_code']) ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-style: italic;">Aguardando rastreio</div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px 16px; text-align: right;">
                                <form action="<?= $e($baseUrl) ?>/admin/dropship/orders/<?= (int)($o['id'] ?? 0) ?>/fulfillment" method="POST" style="display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                    <input type="hidden" name="order_ref" value="<?= $e($o['order_ref'] ?? '') ?>">

                                    <div style="display: flex; gap: 6px;">
                                        <input type="text" name="tracking_code" placeholder="Cód. Rastreio" value="<?= $e($o['tracking_code'] ?? '') ?>" style="width: 115px; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 6px 8px; border-radius: 4px; font-size: 12px;">
                                        <select name="status" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 6px 8px; border-radius: 4px; font-size: 12px;">
                                            <option value="paid" <?= $ordStatus === 'paid' ? 'selected' : '' ?>>Pago</option>
                                            <option value="shipped" <?= $ordStatus === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                                            <option value="delivered" <?= $ordStatus === 'delivered' ? 'selected' : '' ?>>Entregue</option>
                                        </select>
                                    </div>

                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <select name="seller_origin" title="Origem do Fornecedor" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 5px 6px; border-radius: 4px; font-size: 11.5px;">
                                            <option value="BR" <?= $slaOrigin === 'BR' ? 'selected' : '' ?>>🇧🇷 Brasil (7d)</option>
                                            <option value="CN" <?= $slaOrigin === 'CN' ? 'selected' : '' ?>>🇨🇳 China (25d)</option>
                                        </select>
                                        <input type="email" name="seller_email" placeholder="E-mail fornecedor" value="<?= $e($o['seller_email'] ?? '') ?>" style="width: 130px; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 5px 8px; border-radius: 4px; font-size: 11.5px;">
                                        <button type="submit" style="background: rgba(69, 243, 255, 0.15); border: 1px solid rgba(69, 243, 255, 0.4); color: #45f3ff; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;" title="Salvar Rastreio, Origem e Status">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
