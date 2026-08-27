<!-- views/partials/transaction_list.php -->
<div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 20px;">
    <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-size: 16px;">Extrato e Lançamentos Futuros</h3>
    
    <div style="overflow-x: auto;">
        <table class="wp-list-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 12px; font-weight: bold; color: #475569;">Data</th>
                    <th style="padding: 12px; font-weight: bold; color: #475569;">Descrição</th>
                    <th style="padding: 12px; font-weight: bold; color: #475569;">Tipo</th>
                    <th style="padding: 12px; font-weight: bold; color: #475569;">Valor</th>
                    <th style="padding: 12px; font-weight: bold; color: #475569;">Status</th>
                    <th style="padding: 12px; font-weight: bold; color: #475569; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">Nenhuma transação encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): 
                        $isIncome = $t['type'] === 'INCOME';
                        $isPaid = $t['status'] === 'PAID';
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; color: #1e293b;"><?= date('d/m/Y', strtotime($t['due_date'])) ?></td>
                        <td style="padding: 12px; color: #1e293b; font-weight: 500;"><?= htmlspecialchars($t['description']) ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; <?= $isIncome ? 'background: #dcfce3; color: #166534;' : 'background: #fee2e2; color: #991b1b;' ?>">
                                <?= $isIncome ? 'RECEITA' : 'DESPESA' ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-weight: bold; color: <?= $isIncome ? '#166534' : '#991b1b' ?>;">
                            R$ <?= number_format($t['amount'], 2, ',', '.') ?>
                        </td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; <?= $isPaid ? 'background: #f1f5f9; color: #64748b;' : 'background: #fef3c7; color: #d97706;' ?>">
                                <?= $isPaid ? 'PAGO' : 'PENDENTE' ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if (!$isPaid): ?>
                                <form action="<?= BASE_URL ?>/admin/finance/status" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="status" value="PAID">
                                    <button type="submit" style="background: #22c55e; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: bold;" title="Marcar como Pago">
                                        Baixar
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #cbd5e1;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
