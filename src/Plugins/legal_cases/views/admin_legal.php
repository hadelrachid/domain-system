<div style="padding: 20px;">
    <h1 style="color: #fff; font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #d4af37; padding-bottom: 10px;">⚖️ Meus Processos Ativos</h1>
    <table style="width: 100%; border-collapse: collapse; background: rgba(0,0,0,0.5); color: #fff;">
        <thead>
            <tr style="background: rgba(212, 175, 55, 0.2); text-align: left;">
                <th style="padding: 15px; border-bottom: 1px solid #d4af37;">Nº Processo</th>
                <th style="padding: 15px; border-bottom: 1px solid #d4af37;">Cliente</th>
                <th style="padding: 15px; border-bottom: 1px solid #d4af37;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cases as $c): ?>
            <tr>
                <td style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($c['case_number']) ?></td>
                <td style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($c['client_name']) ?></td>
                <td style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);"><span style="background: #d4af37; color: #000; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;"><?= htmlspecialchars($c['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
