<?php
/**
 * View Administrativa: Gestão de Pedidos e Fulfillment de Dropshipping
 *
 * @var array<int, array<string, mixed>> $orders
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 */
?>
<div class="domain-drop-orders" style="max-width: 1400px; margin: 0 auto;">

    <!-- Top Header & Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <a href="<?= $baseUrl ?>/admin/dropship" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fas fa-satellite-dish"></i> DomainDrop
                </a>
                <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
                <span style="color: #ff4747; font-size: 13px; font-weight: 600;">Pedidos & Fulfillment</span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shipping-fast" style="color: #ff4747;"></i>
                Pedidos Dropshipping & Rastreamento
            </h1>
            <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Gerenciamento de pedidos pagos no checkout, vínculo de ordens do AliExpress e emissão de códigos de rastreio aos clientes.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="<?= $baseUrl ?>/admin/dropship" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-boxes"></i> Ver Catálogo
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($flashMsg) && $flashType === 'error'): ?>
        <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
        <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form action="<?= $baseUrl ?>/admin/dropship/orders" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
            <div style="position: relative; min-width: 260px; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted, #94a3b8); font-size: 13px;"></i>
                <input type="text" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="Buscar por referência, cliente, e-mail ou rastreio..." style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px 8px 34px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
            </div>

            <select name="status" onchange="this.form.submit()" style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                <option value="">Todos os Status</option>
                <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : '' ?>>Pendente</option>
                <option value="paid" <?= (isset($_GET['status']) && $_GET['status'] === 'paid') ? 'selected' : '' ?>>Pago (Aguardando Despacho)</option>
                <option value="shipped" <?= (isset($_GET['status']) && $_GET['status'] === 'shipped') ? 'selected' : '' ?>>Enviado / Com Rastreio</option>
                <option value="delivered" <?= (isset($_GET['status']) && $_GET['status'] === 'delivered') ? 'selected' : '' ?>>Entregue</option>
            </select>

            <button type="submit" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>
            <?php if (!empty($_GET['q']) || !empty($_GET['status'])): ?>
                <a href="<?= $baseUrl ?>/admin/dropship/orders" style="color: var(--text-muted, #94a3b8); font-size: 12px; text-decoration: none;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="font-size: 13px; color: var(--text-muted, #94a3b8);">
            Total de Pedidos: <strong style="color: #fff;"><?= count($orders) ?></strong>
        </div>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div style="background: var(--bg-panel, #1d2327); border: 1px dashed var(--border, rgba(255,255,255,0.15)); border-radius: 8px; padding: 60px 20px; text-align: center;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-muted, #94a3b8); margin-bottom: 16px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #fff; font-size: 18px;">Nenhum pedido de dropshipping recebido</h3>
            <p style="margin: 0 0 20px 0; color: var(--text-muted, #94a3b8); font-size: 13px; max-width: 480px; margin-left: auto; margin-right: auto;">
                Assim que um cliente comprar um produto na sua vitrine via DomainCommerce, o pedido aparecerá aqui com status <strong>Pago</strong> pronto para despacho.
            </p>
        </div>
    <?php else: ?>
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.2);">
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Referência</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Cliente</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Total Pago</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Status</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff;">ID AliExpress / Rastreio</th>
                        <th style="padding: 14px 16px; font-weight: 600; color: #fff; text-align: right;">Ações de Envio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                            <td style="padding: 14px 16px; font-family: monospace; font-weight: 600; color: #fff;">
                                <?= htmlspecialchars((string)$o['order_ref']) ?>
                                <div style="font-size: 11px; color: var(--text-muted, #94a3b8); font-family: sans-serif; margin-top: 2px;">
                                    <?= htmlspecialchars((string)($o['created_at'] ?? '')) ?>
                                </div>
                            </td>
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 600; color: #fff;"><?= htmlspecialchars((string)($o['customer_name'] ?? 'Cliente')) ?></div>
                                <div style="font-size: 12px; color: var(--text-muted, #94a3b8);"><?= htmlspecialchars((string)($o['customer_email'] ?? '')) ?></div>
                            </td>
                            <td style="padding: 14px 16px; font-weight: 600; color: #5cd67d; font-family: monospace;">
                                R$ <?= number_format((float)($o['total_paid_brl'] ?? 0.0), 2, ',', '.') ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <?php
                                $status = (string)($o['status'] ?? 'pending');
                                $badges = [
                                    'pending'   => ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#fbbf24', 'label' => 'Pendente'],
                                    'paid'      => ['bg' => 'rgba(40, 167, 69, 0.15)', 'color' => '#5cd67d', 'label' => 'Pago'],
                                    'shipped'   => ['bg' => 'rgba(69, 243, 255, 0.15)', 'color' => '#45f3ff', 'label' => 'Enviado'],
                                    'delivered' => ['bg' => 'rgba(168, 85, 247, 0.15)', 'color' => '#c084fc', 'label' => 'Entregue'],
                                ];
                                $b = $badges[$status] ?? ['bg' => 'rgba(255,255,255,0.1)', 'color' => '#fff', 'label' => $status];
                                ?>
                                <span style="background: <?= $b['bg'] ?>; color: <?= $b['color'] ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <?= htmlspecialchars($b['label']) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px;">
                                <?php if (!empty($o['aliexpress_order_id'])): ?>
                                    <div style="font-size: 12px; color: #fff;">
                                        AliExpress: <code style="color: #ff6b6b;"><?= htmlspecialchars((string)$o['aliexpress_order_id']) ?></code>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($o['tracking_code'])): ?>
                                    <div style="font-size: 12px; color: #45f3ff; margin-top: 3px; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-barcode"></i> <?= htmlspecialchars((string)$o['tracking_code']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--text-muted, #94a3b8); italic;">Aguardando envio</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <form action="<?= $baseUrl ?>/admin/dropship/orders/<?= (int)$o['id'] ?>/fulfillment" method="POST" style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="order_ref" value="<?= htmlspecialchars((string)$o['order_ref']) ?>">
                                    
                                    <input type="text" name="tracking_code" placeholder="Cód. Rastreio" value="<?= htmlspecialchars((string)($o['tracking_code'] ?? '')) ?>" style="width: 120px; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 6px 8px; border-radius: 4px; font-size: 12px;">

                                    <select name="status" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 6px 8px; border-radius: 4px; font-size: 12px;">
                                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Pago</option>
                                        <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                                        <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Entregue</option>
                                    </select>

                                    <button type="submit" style="background: rgba(69, 243, 255, 0.15); border: 1px solid rgba(69, 243, 255, 0.4); color: #45f3ff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;" title="Atualizar Rastreio e Status">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
