
<div class="wrap">
    <h1 style="display:flex; justify-content:space-between; align-items:center;">
        <span>🚨 Painel de Supervisão e Rastreamento</span>
        <form method="POST" action="<?= BASE_URL ?>/admin/monitor/clear" style="margin:0;" id="clearLogsForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="button" class="page-title-action" style="color:#d63638; border-color:#d63638;" onclick="OS.confirm('Tem certeza que deseja limpar todo o histórico de erros?', () => document.getElementById('clearLogsForm').submit());">Limpar Logs</button>
        </form>
    </h1>
    
    <p>Este painel intercepta erros críticos protegendo o núcleo do sistema, e monitora a Pilha de Serviços (PIDs e Memória) na inicialização.</p>

    <div class="sys-tabs">
        <button class="sys-tab active" onclick="openSysTab(event, 'tab-erros')">🪲 Histórico de Crashes</button>
        <button class="sys-tab" onclick="openSysTab(event, 'tab-processos')">🖥️ Pilha de Serviços (PIDs)</button>
    </div>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- ABA 1: HISTÓRICO DE CRASHES                    -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="tab-erros" class="sys-content active">
        <?php if (isset($_GET['cleared'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                Histórico de erros apagado com sucesso.
            </div>
        <?php endif; ?>

        <?php if (empty($logs)): ?>
            <div style="background: var(--bg-panel); padding: 30px; text-align: center; border: 1px solid var(--border); border-radius: 4px;">
                <h2 style="color: var(--accent-green);">Tudo verde por aqui! ✅</h2>
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

    <!-- ═══════════════════════════════════════════════ -->
    <!-- ABA 2: PILHA DE SERVIÇOS (PIDs)                -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="tab-processos" class="sys-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h2 style="margin: 0; color:#fff;">Gerenciador de Tarefas do Kernel</h2>
                <p style="color:#94a3b8; font-size:13px; margin: 5px 0 0;">Snapshot do último boot. Atualiza automaticamente quando a aba está visível.</p>
            </div>
            <div id="os-live-indicator" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b;">
                <span id="os-live-dot" style="width: 8px; height: 8px; border-radius: 50%; background: #334155; display: inline-block;"></span>
                <span id="os-live-text">Aguardando...</span>
            </div>
        </div>
        
        <?php if (empty($processes)): ?>
            <div id="os-empty-stack" style="background: var(--bg-panel); padding: 30px; text-align: center; border: 1px solid var(--border); border-radius: 4px;">
                <p>Nenhuma pilha de processos foi registrada nesta inicialização.</p>
            </div>
        <?php endif; ?>

        <table class="wp-list-table" id="os-process-table" style="<?= empty($processes) ? 'display:none;' : '' ?>">
            <thead>
                <tr>
                    <th style="width: 130px;">PID</th>
                    <th>Módulo / Plugin</th>
                    <th>Camada (Ring)</th>
                    <th>Memória (Δ)</th>
                    <th>Tempo de Boot</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="process-table-body">
                <?php foreach ($processes as $pid => $proc): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: var(--accent-blue);"><?= htmlspecialchars($proc['pid']) ?></td>
                    <td><strong><?= htmlspecialchars($proc['name']) ?></strong></td>
                    <td>
                        <?php if($proc['type'] === 'SystemApp'): ?>
                            <span class="badge" style="background: rgba(88,166,255,0.1); color: var(--accent-blue); border: 1px solid var(--accent-blue);">Ring 0 (Core)</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(245,110,40,0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange);">Ring 3 (User)</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: <?= $proc['memory_used_kb'] > 5000 ? '#ef4444' : 'var(--text-main)' ?>;">
                        <?= number_format($proc['memory_used_kb'], 2) ?> KB
                    </td>
                    <td style="color: <?= $proc['duration_ms'] > 100 ? '#ef4444' : 'var(--accent-green)' ?>;">
                        <?= number_format($proc['duration_ms'], 2) ?> ms
                    </td>
                    <td>
                        <?php if($proc['status'] === 'Running'): ?>
                            <span style="color: var(--accent-green);">● Estável</span>
                        <?php else: ?>
                            <span style="color: #ef4444; font-weight:bold;">✖ Crashed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════
// Sistema de Abas
// ═══════════════════════════════════════════════
function openSysTab(evt, tabId) {
    document.querySelectorAll('.sys-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sys-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// ═══════════════════════════════════════════════
// Heartbeat Ajax — Pilha de Serviços (Visão Focada)
// ═══════════════════════════════════════════════
(function() {
    let lastModified = 0;
    let pollInterval = null;

    function setLiveStatus(status, color) {
        const dot = document.getElementById('os-live-dot');
        const txt = document.getElementById('os-live-text');
        if (dot) dot.style.background = color;
        if (txt) txt.textContent = status;
    }

    function renderProcessTable(processes) {
        const tbody = document.getElementById('process-table-body');
        const table = document.getElementById('os-process-table');
        const empty = document.getElementById('os-empty-stack');
        if (!tbody) return;

        let html = '';
        for (let pid in processes) {
            let p = processes[pid];
            let ring = p.type === 'SystemApp'
                ? '<span class="badge" style="background:rgba(88,166,255,0.1);color:var(--accent-blue);border:1px solid var(--accent-blue);">Ring 0 (Core)</span>'
                : '<span class="badge" style="background:rgba(245,110,40,0.1);color:var(--accent-orange);border:1px solid var(--accent-orange);">Ring 3 (User)</span>';
            let memC = parseFloat(p.memory_used_kb) > 5000 ? '#ef4444' : 'var(--text-main)';
            let timeC = parseFloat(p.duration_ms) > 100 ? '#ef4444' : 'var(--accent-green)';
            let st = p.status === 'Running'
                ? '<span style="color:var(--accent-green);">● Estável</span>'
                : '<span style="color:#ef4444;font-weight:bold;">✖ Crashed</span>';
            html += `<tr>
                <td style="font-family:monospace;font-weight:bold;color:var(--accent-blue);">${p.pid}</td>
                <td><strong>${p.name}</strong></td>
                <td>${ring}</td>
                <td style="color:${memC};">${parseFloat(p.memory_used_kb).toFixed(2)} KB</td>
                <td style="color:${timeC};">${parseFloat(p.duration_ms).toFixed(2)} ms</td>
                <td>${st}</td>
            </tr>`;
        }
        tbody.innerHTML = html;
        if (table) table.style.display = '';
        if (empty) empty.style.display = 'none';
    }

    function poll() {
        // Visão Focada: Só faz requisição se a aba estiver ativa
        const tab = document.getElementById('tab-processos');
        if (!tab || !tab.classList.contains('active')) {
            setLiveStatus('Pausado (aba inativa)', '#334155');
            return;
        }

        setLiveStatus('Consultando...', '#f59e0b');

        fetch(window.DS_BASE_URL + 'api/os/monitor/stack')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.processes) {
                    if (data.lastModified !== lastModified) {
                        lastModified = data.lastModified;
                        renderProcessTable(data.processes);
                        setLiveStatus('Atualizado agora', '#10b981');
                    } else {
                        setLiveStatus('Sem alterações', '#10b981');
                    }
                }
            })
            .catch(() => setLiveStatus('Erro de conexão', '#ef4444'));
    }

    // Inicia o heartbeat a cada 5 segundos
    pollInterval = setInterval(poll, 5000);

    // Executa imediatamente ao abrir a aba
    document.querySelectorAll('.sys-tab').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(poll, 100));
    });
})();
</script>