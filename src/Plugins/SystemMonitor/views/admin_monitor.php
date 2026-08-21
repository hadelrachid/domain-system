
<div class="wrap">
    <h1 style="display:flex; justify-content:space-between; align-items:center;">
        <span>🚨 Painel de Supervisão e Rastreamento de Erros</span>
        <form method="POST" action="<?= BASE_URL ?>/admin/monitor/clear" style="margin:0;">
            <button type="submit" class="page-title-action" style="color:#d63638; border-color:#d63638;" onclick="return confirm('Tem certeza que deseja limpar todo o histórico de erros?');">Limpar Logs</button>
        </form>
    </h1>
    
    <p>Este painel intercepta e exibe todos os erros críticos (Páginas em branco, exceções e falhas fatais em plugins) protegendo o núcleo do sistema.</p>

    <?php if (isset($_GET['cleared'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            Histórico de erros apagado com sucesso.
        </div>
    <?php endif; ?>

    <?php if (empty($logs)): ?>
        <div style="background: #fff; padding: 30px; text-align: center; border: 1px solid #c3c4c7; border-radius: 4px;">
            <h2 style="color: #28a745;">Tudo verde por aqui! ✅</h2>
            <p>Nenhum erro crítico foi registrado recentemente pelo motor do sistema.</p>
        </div>
    <?php else: ?>
        <?php foreach ($logs as $index => $log): ?>
            <div style="background: #fff; border-left: 5px solid #d63638; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="padding: 15px; border-bottom: 1px solid #f0f0f1; display:flex; justify-content:space-between; background:#fafafa;">
                    <strong><?= htmlspecialchars($log['type']) ?></strong>
                    <span style="color: #646970;"><?= htmlspecialchars($log['timestamp']) ?> - <?= htmlspecialchars($log['method']) ?> <?= htmlspecialchars($log['url']) ?></span>
                </div>
                <div style="padding: 15px;">
                    <p style="font-size: 16px; color: #d63638; margin-top: 0;"><strong><?= htmlspecialchars($log['message']) ?></strong></p>
                    
                    <p style="margin:0; color:#50575e;">
                        <strong>Arquivo:</strong> <?= htmlspecialchars($log['file']) ?><br>
                        <strong>Linha:</strong> <?= htmlspecialchars($log['line']) ?>
                    </p>

                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #2271b1; font-weight: 600;">Ver Rastreamento Completo (Stack Trace)</summary>
                        <pre style="background: #1d2327; color: #f1f1f1; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; margin-top: 10px;"><?= htmlspecialchars($log['trace']) ?></pre>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

