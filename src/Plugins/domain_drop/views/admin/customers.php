<?php
/**
 * View Administrativa: Gestão de Clientes & Compradores (Cockpit DomainDrop)
 *
 * @var array<int, array<string, mixed>> $customers
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var int $totalPages
 * @var string $currentSearch
 * @var string $currentStatus
 * @var array<string, int> $metrics
 * @var string $csrfToken
 * @var string $baseUrl
 * @var array<string, mixed>|null $flash
 */
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="<?= $e($baseUrl) ?>/admin/dropship" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                📦 Hub DomainDrop
            </a>
            <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
            <span style="color: #F0D060; font-size: 13px; font-weight: 600;">Clientes &amp; Compradores</span>
        </div>
        <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
            👥 Clientes &amp; Compradores Cadastrados
        </h1>
        <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
            Controle de contas da loja, consulta de CPF/WhatsApp, histórico de compras, reclamações e status de acesso.
        </p>
    </div>

    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= $e($baseUrl) ?>/admin/dropship/orders" class="btn" style="background: rgba(212,175,55,0.14); border: 1px solid rgba(212,175,55,0.4); color: #F0D060; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; text-decoration: none;">
            🛍️ Pedidos &amp; Mediação
        </a>
        <a href="<?= $e($baseUrl) ?>/admin/dropship" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">
            ← Catálogo
        </a>
    </div>
</div>

<?php if (!empty($flash['msg'])): ?>
    <?php $isErr = ($flash['type'] ?? '') === 'error'; ?>
    <div style="background: <?= $isErr ? 'rgba(220,50,50,0.15)' : 'rgba(40,167,69,0.15)' ?>; border: 1px solid <?= $isErr ? '#dc3232' : '#28a745' ?>; color: <?= $isErr ? '#ff8080' : '#5cd67d' ?>; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        <?= $e($flash['msg']) ?>
    </div>
<?php endif; ?>

<!-- Cards de Métricas em Tempo Real -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Total de Clientes
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #fff;">
            <?= (int)($metrics['total_customers'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: #00d284; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Contas Ativas
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #00d284;">
            <?= (int)($metrics['active_customers'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: #f87171; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Suspensos / Bloqueados
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #f87171;">
            <?= (int)($metrics['suspended_customers'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: #F0D060; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Pedidos Vinculados
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #F0D060;">
            <?= (int)($metrics['linked_orders'] ?? 0) ?>
        </div>
    </div>
</div>

<!-- Painel Principal de Clientes -->
<div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow: hidden;">
    <!-- Barra de Filtros e Busca -->
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border, rgba(255,255,255,0.08)); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="<?= $e($baseUrl) ?>/admin/dropship/customers" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1; max-width: 680px;">
            <input type="text" name="q" value="<?= $e($currentSearch) ?>" placeholder="Buscar por nome, e-mail, CPF ou WhatsApp..."
                   style="flex: 1; min-width: 220px; background: rgba(0,0,0,0.28); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 12px; border-radius: 6px; font-size: 13px;">

            <select name="status" style="background: rgba(0,0,0,0.28); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 12px; border-radius: 6px; font-size: 13px;">
                <option value="all" <?= $currentStatus === 'all' ? 'selected' : '' ?>>Todos os Status</option>
                <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Ativos</option>
                <option value="suspended" <?= $currentStatus === 'suspended' ? 'selected' : '' ?>>Suspensos</option>
            </select>

            <button type="submit" style="background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; border: none; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 800; cursor: pointer;">
                🔍 Filtrar
            </button>

            <?php if ($currentSearch !== '' || $currentStatus !== 'all'): ?>
                <a href="<?= $e($baseUrl) ?>/admin/dropship/customers" style="color: #94a3b8; font-size: 12px; text-decoration: underline;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="font-size: 12px; color: #94a3b8;">
            Exibindo <strong><?= count($customers) ?></strong> de <strong><?= (int)$total ?></strong> cliente(s)
        </div>
    </div>

    <?php if (empty($customers)): ?>
        <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
            <p style="margin: 0 0 8px 0; font-size: 15px; color: #e2e8f0; font-weight: 600;">Nenhum cliente encontrado.</p>
            <p style="margin: 0; font-size: 13px;">Os clientes cadastrados em <code>/cadastro</code> aparecerão automaticamente nesta lista.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #e2e8f0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.25); text-align: left; color: #94a3b8; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px;">
                        <th style="padding: 12px 16px;">ID / Cliente</th>
                        <th style="padding: 12px 16px;">Contato (E-mail &amp; WhatsApp)</th>
                        <th style="padding: 12px 16px;">CPF</th>
                        <th style="padding: 12px 16px;">Pedidos &amp; Compras</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <?php
                        $cid = (int)($c['id'] ?? 0);
                        $st = (string)($c['status'] ?? 'active');
                        $isActive = ($st === 'active');
                        $ordersCount = (int)($c['orders_count'] ?? 0);
                        $disputesCount = (int)($c['disputes_count'] ?? 0);
                        $totalSpent = (float)($c['total_spent_brl'] ?? 0);
                        $phoneDigits = preg_replace('/\D+/', '', (string)($c['phone'] ?? '')) ?? '';
                        ?>
                        <tr style="border-top: 1px solid rgba(255,255,255,0.06);">
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 700; color: #fff; font-size: 14px;">#<?= $cid ?> — <?= $e($c['name'] ?? 'Cliente') ?></div>
                                <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">Cadastro: <?= $e($c['created_at'] ?? '-') ?></div>
                            </td>
                            <td style="padding: 14px 16px;">
                                <div style="color: #cbd5e1;"><?= $e($c['email'] ?? '') ?></div>
                                <?php if (!empty($c['phone'])): ?>
                                    <div style="margin-top: 4px; font-size: 12px;">
                                        <span style="color: #6ee7b7;">📱 <?= $e($c['phone']) ?></span>
                                        <?php if (strlen($phoneDigits) >= 10): ?>
                                            <a href="https://wa.me/55<?= $e($phoneDigits) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 6px; color: #00d284; font-size: 11px; text-decoration: underline;">WhatsApp ↗</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px; font-family: monospace; color: #cbd5e1;">
                                <?= !empty($c['cpf']) ? $e($c['cpf']) : '<span style="color:#64748b;">Não informado</span>' ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <div>
                                    <strong style="color: #F0D060;"><?= $ordersCount ?> pedido(s)</strong>
                                    <span style="color: #94a3b8;">•</span>
                                    <strong style="color: #5cd67d;">R$ <?= number_format($totalSpent, 2, ',', '.') ?></strong>
                                </div>
                                <?php if ($disputesCount > 0): ?>
                                    <div style="font-size: 11.5px; color: #fca5a5; margin-top: 3px;">
                                        🚨 <?= $disputesCount ?> chamado(s)/reclamação(ões)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; background: <?= $isActive ? 'rgba(0,210,132,0.14)' : 'rgba(239,68,68,0.15)' ?>; color: <?= $isActive ? '#00d284' : '#f87171' ?>; border: 1px solid <?= $isActive ? 'rgba(0,210,132,0.35)' : 'rgba(239,68,68,0.35)' ?>;">
                                    <?= $isActive ? '● Ativo' : '● Suspenso' ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                                    <a href="<?= $e($baseUrl) ?>/admin/dropship/orders" style="padding: 6px 10px; border-radius: 6px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.14); color: #cbd5e1; text-decoration: none; font-size: 12px;">
                                        📦 Ver Pedidos
                                    </a>
                                    <form method="POST" action="<?= $e($baseUrl) ?>/admin/dropship/customers/<?= $cid ?>/status" style="margin: 0; display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                        <input type="hidden" name="status" value="<?= $isActive ? 'suspended' : 'active' ?>">
                                        <button type="submit" style="padding: 6px 10px; border-radius: 6px; border: 1px solid <?= $isActive ? 'rgba(239,68,68,0.4)' : 'rgba(0,210,132,0.4)' ?>; background: <?= $isActive ? 'rgba(239,68,68,0.12)' : 'rgba(0,210,132,0.12)' ?>; color: <?= $isActive ? '#fca5a5' : '#6ee7b7' ?>; font-size: 12px; font-weight: 700; cursor: pointer;">
                                            <?= $isActive ? 'Suspender' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (($totalPages ?? 1) > 1): ?>
        <div style="padding: 14px 20px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: flex-end; gap: 6px;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="<?= $e($baseUrl) ?>/admin/dropship/customers?page=<?= $p ?>&amp;status=<?= $e(rawurlencode($currentStatus)) ?>&amp;q=<?= $e(rawurlencode($currentSearch)) ?>"
                   style="padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700; background: <?= $p === $page ? '#F0D060' : 'rgba(255,255,255,0.06)' ?>; color: <?= $p === $page ? '#0b0f17' : '#cbd5e1' ?>;">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($recentDisputes)): ?>
    <div style="margin-top: 24px; background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 20px;">
        <h2 style="margin: 0 0 14px 0; font-size: 18px; font-weight: 700; color: #fff;">
            💬 Chamados, Reclamações, Observações &amp; Dúvidas Recebidas (<?= count($recentDisputes) ?>)
        </h2>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($recentDisputes as $disp): ?>
                <div style="padding: 14px 16px; background: rgba(0,0,0,0.28); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: #F0D060; font-family: monospace;">Ref/Pedido: <?= $e($disp['order_ref'] ?? 'GERAL') ?></strong>
                            <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">• Cliente: <?= $e($disp['customer_email'] ?? '') ?></span>
                            <span style="color: #45f3ff; font-size: 12px; margin-left: 8px;">• Motivo: <?= $e($disp['reason'] ?? '') ?></span>
                            <?php if (!empty($disp['seller_name'])): ?>
                                <span style="color: #6ee7b7; font-size: 12px; margin-left: 8px;">• Vendedor: <?= $e($disp['seller_name']) ?> (<?= $e($disp['seller_origin'] ?? 'BR') ?>)</span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 11px; color: #94a3b8;"><?= $e($disp['created_at'] ?? '') ?></span>
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #e2e8f0;"><?= nl2br($e($disp['description'] ?? '')) ?></p>
                    <?php if (!empty($disp['attachments'])): ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                            <?php foreach ($disp['attachments'] as $att): ?>
                                <a href="<?= $e($baseUrl) ?>/dropship/disputes/attachment/<?= (int)($disp['id'] ?? 0) ?>/<?= $e(rawurlencode((string)($att['filename'] ?? ''))) ?>" target="_blank"
                                   style="font-size: 11.5px; color: #93c5fd; background: rgba(59,130,246,0.14); border: 1px solid rgba(59,130,246,0.35); padding: 4px 10px; border-radius: 6px; text-decoration: none;">
                                    📎 <?= $e($att['original_name'] ?? $att['filename'] ?? 'Anexo') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

