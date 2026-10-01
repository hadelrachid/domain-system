<div style="background: white; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
    <h3 style="margin-top: 0; color: #0f172a;">Simulação de Faturamento</h3>
    <ul style="list-style: none; padding: 0;">
        <li style="margin-bottom: 10px;">🩺 <strong>Convênio:</strong> <?= htmlspecialchars($result['provider']) ?></li>
        <li style="margin-bottom: 10px;">
            ✅ <strong>Status Autorização:</strong> 
            <?= $result['authorized'] ? '<span style="color: green; font-weight: bold;">AUTORIZADO</span>' : '<span style="color: red; font-weight: bold;">NEGADO</span>' ?>
        </li>
        <li style="font-size: 18px;">
            💰 <strong>Valor a Faturar:</strong> 
            <span style="color: #2563eb; font-weight: bold;">R$ <?= number_format($result['price'], 2, ',', '.') ?></span>
        </li>
    </ul>
</div>
