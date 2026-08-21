
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
            <?php 
                $rawText = $log['type'] . "\n";
                $rawText .= $log['timestamp'] . " - " . $log['method'] . " " . $log['url'] . "\n\n";
                $rawText .= $log['message'] . "\n\n";
                $rawText .= "Arquivo: " . $log['file'] . "\n";
                $rawText .= "Linha: " . $log['line'] . "\n\n";
                $rawText .= "Stack Trace:\n" . $log['trace'];
            ?>
            <div style="background: #fff; border-left: 5px solid #d63638; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); position: relative;">
                <div style="padding: 15px; border-bottom: 1px solid #f0f0f1; display:flex; justify-content:space-between; background:#fafafa;">
                    <strong><?= htmlspecialchars($log['type']) ?></strong>
                    <span style="color: #646970;"><?= htmlspecialchars($log['timestamp']) ?> - <?= htmlspecialchars($log['method']) ?> <?= htmlspecialchars($log['url']) ?></span>
                </div>
                <div style="padding: 15px;">
                    <p style="font-size: 16px; color: #d63638; margin-top: 0; padding-right: 100px;"><strong><?= htmlspecialchars($log['message']) ?></strong></p>
                    
                    <button type="button" class="btn" style="position: absolute; right: 15px; top: 60px; font-size: 12px; display: flex; align-items: center; gap: 5px;" onclick="copyLog(<?= $index ?>)" id="btn_copy_<?= $index ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        Copiar Log
                    </button>
                    <textarea id="raw_log_<?= $index ?>" style="display:none;"><?= htmlspecialchars($rawText) ?></textarea>

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
                    alert("Erro ao copiar: " + err);
                });
            }
        </script>
    <?php endif; ?>
</div>

