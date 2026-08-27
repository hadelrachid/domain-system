<!-- views/partials/summary_cards.php -->
<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
    <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #22c55e;">
        <div style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Receitas (Pagas)</div>
        <div style="font-size: 24px; font-weight: bold; color: #1e293b;">R$ <?= number_format($totals['income'] ?? 0, 2, ',', '.') ?></div>
    </div>
    
    <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #ef4444;">
        <div style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Despesas (Pagas)</div>
        <div style="font-size: 24px; font-weight: bold; color: #1e293b;">R$ <?= number_format($totals['expense'] ?? 0, 2, ',', '.') ?></div>
    </div>
    
    <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6;">
        <div style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Saldo Atual</div>
        <div style="font-size: 24px; font-weight: bold; color: <?= ($totals['balance'] ?? 0) >= 0 ? '#1e293b' : '#ef4444' ?>;">
            R$ <?= number_format($totals['balance'] ?? 0, 2, ',', '.') ?>
        </div>
    </div>
</div>
