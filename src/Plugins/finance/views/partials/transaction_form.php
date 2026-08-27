<!-- views/partials/transaction_form.php -->
<div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
    <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-size: 16px;">Novo Lançamento</h3>
    <form action="<?= BASE_URL ?>/admin/finance/store" method="POST">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Tipo</label>
                <select name="type" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" required>
                    <option value="INCOME">Receita (Entrada)</option>
                    <option value="EXPENSE">Despesa (Saída)</option>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Valor (R$)</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" required>
            </div>
            
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Vencimento</label>
                <input type="date" name="due_date" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" required>
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Descrição</label>
            <input type="text" name="description" placeholder="Ex: Conta de Luz, Consulta Maria..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" required>
        </div>
        
        <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
            <label style="font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                <input type="checkbox" name="status" value="PAID">
                Já está pago?
            </label>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">Registrar</button>
        </div>
    </form>
</div>
