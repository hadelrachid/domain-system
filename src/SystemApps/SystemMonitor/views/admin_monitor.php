
<div class="wrap">
    <h1 style="display:flex; justify-content:space-between; align-items:center;">
        <span>🚨 Painel de Supervisão e Rastreamento de Erros</span>
        <form method="POST" action="<?= BASE_URL ?>/admin/monitor/clear" style="margin:0;" id="clearLogsForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="button" class="page-title-action" style="color:#d63638; border-color:#d63638;" onclick="OS.confirm('Tem certeza que deseja limpar todo o histórico de erros?', () => document.getElementById('clearLogsForm').submit());">Limpar Logs</button>
        </form></div>
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
            <?php 
                $rawText = $log['type'] . "\n";
                $rawText .= $log['timestamp'] . " - " . $log['method'] . " " . $log['url'] . "\n\n";
                $rawText .= $log['message'] . "\n\n";
                $rawText .= "Arquivo: " . $log['file'] . "\n";
                $rawText .= "Linha: " . $log['line'] . "\n\n";
                $rawText .= "Stack Trace:\n" . $log['trace'];
            ?>
            <div style="background: #1e293b; border-left: 5px solid #ef4444; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); position: relative; color: #f8fafc; border-radius: 6px;">
                <div style="padding: 15px; border-bottom: 1px solid #334155; display:flex; justify-content:space-between; background:#0f172a; border-radius: 6px 6px 0 0;">
                    <strong style="font-size: 15px; color: #f8fafc;"><?= htmlspecialchars($log['type']) ?></strong>
                    <span style="color: #94a3b8; font-weight: 500;"><?= htmlspecialchars($log['timestamp']) ?> - <?= htmlspecialchars($log['method']) ?> <?= htmlspecialchars($log['url']) ?></span>
                </div>
                <div style="padding: 15px;">
                    <p style="font-size: 16px; color: #fca5a5; margin-top: 0; padding-right: 100px;"><strong><?= htmlspecialchars($log['message']) ?></strong></p>
                    
                    <button type="button" class="btn" style="position: absolute; right: 15px; top: 60px; font-size: 12px; display: flex; align-items: center; gap: 5px; background: #334155; color: #fff; border: none; padding: 5px 10px; border-radius: 4px;" onclick="copyLog(<?= $index ?>)" id="btn_copy_<?= $index ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        Copiar Log
                    </button>
                    <textarea id="raw_log_<?= $index ?>" style="display:none;"><?= htmlspecialchars($rawText) ?></textarea>

                    <p style="margin:0; color:#cbd5e1;">
                        <strong>Arquivo:</strong> <?= htmlspecialchars($log['file']) ?><br>
                        <strong>Linha:</strong> <?= htmlspecialchars($log['line']) ?>
                    </p>

                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #38bdf8; font-weight: 600;">Ver Rastreamento Completo (Stack Trace)</summary>
                        <pre style="background: #0b1120; color: #10b981; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; margin-top: 10px; border: 1px solid #334155;"><?= htmlspecialchars($log['trace']) ?></pre>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>
        
        <script>
            function copyLog(index) {
                var textarea = document.getElementById('raw_log_' + index);
                var btn = document.getElementById('btn_copy_' + index);
                
                navigator.clipboard.writeText(textarea.value).then(function() {
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copiado!';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                    }, 2000);
                }).catch(function(err) {
                    OS.notify("Erro ao copiar: " + err, 'error');
                });
            }
        </script>
    <?php endif; ?>
</div>

