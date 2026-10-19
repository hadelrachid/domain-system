
<div class="wrap">
    <h1 style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <span>🚨 Painel de Supervisão e Rastreamento</span>
        <div style="display:inline-flex; gap:10px; align-items:center;">
            <form method="POST" action="<?= BASE_URL ?>/admin/monitor/clear" style="margin:0;" id="clearLogsForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="button" class="page-title-action" style="color:#d63638; border-color:#d63638;" onclick="OS.confirm('Tem certeza que deseja limpar o histórico de logs e métricas recentes?', () => document.getElementById('clearLogsForm').submit());">Limpar Histórico/Logs</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>/admin/monitor/restart-kernel" style="margin:0;" id="restartKernelForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="button" class="page-title-action" style="color:#38bdf8; border-color:#38bdf8;" onclick="OS.confirm('Deseja reiniciar o ciclo do Kernel Singleton (alocando novos PIDs no próximo boot)?', () => document.getElementById('restartKernelForm').submit());">Reiniciar Ciclo do Kernel</button>
            </form>
        </div>
    </h1>
    
    <p>Este painel intercepta falhas protegendo o núcleo do sistema, monitora o ciclo de vida do Kernel Singleton e exibe o consumo de memória do worker e picos de requisições.</p>

    <div class="sys-tabs">
        <button class="sys-tab" onclick="openSysTab(event, 'tab-erros')">🪲 Histórico de Crashes</button>
        <button class="sys-tab active" onclick="openSysTab(event, 'tab-processos')">🖥️ Pilha de Serviços & Memória ao Vivo</button>
    </div>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- ABA 1: HISTÓRICO DE CRASHES                    -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="tab-erros" class="sys-content">
        <?php if (isset($_GET['cleared'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                Histórico de erros e métricas recentes apagados com sucesso (ciclo do Kernel preservado).
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['restarted'])): ?>
            <div style="background: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px; border-left: 4px solid #17a2b8;">
                Ciclo do Kernel Singleton reiniciado com sucesso. Novos PIDs alocados.
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
                        
                        <div style="position: absolute; right: 15px; top: 60px;">
                            <button type="button" class="button" style="background: #334155; color: #fff; border: 1px solid #475569;" onclick="copyRawLog(<?= $index ?>)" id="btn-copy-<?= $index ?>">Copiar Log</button>
                        </div>

                        <p style="color: #cbd5e1; font-size: 13px;">
                            <strong>Arquivo:</strong> <?= htmlspecialchars($log['file']) ?> na linha <strong><?= htmlspecialchars($log['line']) ?></strong>
                        </p>
                        <h4 style="margin-bottom: 5px; color: #94a3b8;">Rastreamento (Stack Trace):</h4>
                        <pre style="background: #090d16; color: #38bdf8; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; border: 1px solid #1e293b; max-height: 250px; line-height: 1.5;"><?= htmlspecialchars($log['trace']) ?></pre>
                    </div>
                </div>
                <textarea id="raw-log-<?= $index ?>" style="display:none;"><?= htmlspecialchars($rawText) ?></textarea>
            <?php endforeach; ?>

            <script>
                function copyRawLog(index) {
                    var textarea = document.getElementById('raw-log-' + index);
                    var btn = document.getElementById('btn-copy-' + index);
                    
                    navigator.clipboard.writeText(textarea.value).then(function() {
                        var originalText = btn.textContent;
                        btn.textContent = 'Copiado! ✓';
                        btn.style.background = '#10b981';
                        btn.style.borderColor = '#10b981';
                        
                        setTimeout(function() {
                            btn.textContent = originalText;
                            btn.style.background = '#334155';
                            btn.style.borderColor = '#475569';
                        }, 2000);
                    }).catch(function(err) {
                        OS.notify("Erro ao copiar: " + err, 'error');
                    });
                }
            </script>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- ABA 2: PILHA DE SERVIÇOS & MEMÓRIA AO VIVO     -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="tab-processos" class="sys-content active">
        <!-- Cabeçalho da Aba com Indicador Live e Badge do Singleton -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; color:#fff; display: flex; align-items: center; gap: 10px;">
                    Gerenciador de Tarefas do Kernel
                    <span id="os-singleton-badge" style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); font-family: monospace;">
                        <?= htmlspecialchars($singleton_id ?? 'KRN-LIVE') ?>
                    </span>
                </h2>
                <p style="color:#94a3b8; font-size:13px; margin: 5px 0 0;">Monitoramento contínuo em tempo real. Os PIDs são estáveis durante a vida do Singleton; o consumo e liberação de memória oscilam dinamicamente.</p>
            </div>
            <div id="os-live-indicator" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; background: #0f172a; padding: 6px 14px; border-radius: 20px; border: 1px solid #1e293b;">
                <span id="os-live-dot" style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 8px #10b981;"></span>
                <span id="os-live-text" style="color:#e2e8f0; font-weight: 500;">Monitor Ativo</span>
            </div>
        </div>

        <!-- KPI Cards: Indicativos de Memória Total e Saúde do Sistema -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <!-- Card 1: Memória Total Alocada -->
            <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 16px; position: relative; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Memória Total Alocada</span>
                        <div style="font-size: 24px; font-weight: 700; color: #38bdf8; margin: 4px 0;">
                            <span id="kpi-mem-allocated"><?= number_format($system_memory['allocated_mb'] ?? 0, 2) ?></span> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">MB</span>
                        </div>
                    </div>
                    <div style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-radius: 6px; padding: 8px; font-size: 16px;">🧠</div>
                </div>
                <div style="margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 4px;">
                        <span>Limite PHP: <?= htmlspecialchars($system_memory['limit'] ?? '128M') ?></span>
                        <span id="kpi-mem-percent"><?= $system_memory['percentage'] ?? 0 ?>%</span>
                    </div>
                    <div style="background: #1e293b; height: 6px; border-radius: 3px; overflow: hidden;">
                        <div id="kpi-mem-bar" style="background: linear-gradient(90deg, #06b6d4, #38bdf8); height: 100%; width: <?= min(100, $system_memory['percentage'] ?? 0) ?>%; transition: width 0.4s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Memória em Uso Efetivo -->
            <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Uso Ativo Zend</span>
                        <div style="font-size: 24px; font-weight: 700; color: #10b981; margin: 4px 0;">
                            <span id="kpi-mem-current"><?= number_format($system_memory['current_mb'] ?? 0, 2) ?></span> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">MB</span>
                        </div>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px; padding: 8px; font-size: 16px;">⚡</div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 12px;">
                    Consumo real em estruturas e variáveis ativas
                </div>
            </div>

            <!-- Card 3: Pico Máximo -->
            <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Pico de Memória</span>
                        <div style="font-size: 24px; font-weight: 700; color: #f59e0b; margin: 4px 0;">
                            <span id="kpi-mem-peak"><?= number_format($system_memory['peak_mb'] ?? 0, 2) ?></span> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">MB</span>
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 6px; padding: 8px; font-size: 16px;">📈</div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 12px;">
                    Pico mais alto alcançado durante a execução
                </div>
            </div>

            <!-- Card 4: Ciclo Singleton Ativo -->
            <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Kernel Singleton</span>
                        <div style="font-size: 20px; font-weight: 700; color: #a855f7; margin: 6px 0; font-family: monospace;">
                            <span id="kpi-singleton-id"><?= htmlspecialchars($singleton_id ?? 'KRN-LIVE') ?></span>
                        </div>
                    </div>
                    <div style="background: rgba(168, 85, 247, 0.1); color: #a855f7; border-radius: 6px; padding: 8px; font-size: 16px;">🐝</div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 12px;">
                    <span style="color:#10b981;">●</span> PIDs estáveis até a morte do Singleton
                </div>
            </div>
        </div>

        <!-- Painel do Gráfico ao Vivo -->
        <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 18px 20px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 14px; font-weight: 600; color: #f8fafc;">📊 Gráfico de Consumo e Liberação de Memória Total (Ao Vivo)</span>
                    <span style="font-size: 10px; color: #10b981; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 4px; padding: 2px 6px; font-weight: 600; letter-spacing: 0.5px;">STREAMING ATIVO</span>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; font-size: 11px; color: #94a3b8;">
                    <span style="display:flex; align-items:center; gap:6px;"><span style="display:inline-block; width:12px; height:3px; background:#06b6d4; border-radius:2px; box-shadow: 0 0 6px #06b6d4;"></span> Memória Alocada</span>
                    <span style="display:flex; align-items:center; gap:6px;"><span style="display:inline-block; width:12px; height:2px; background:#f59e0b; border-radius:2px;"></span> Pico Registrado</span>
                    <span id="chart-live-val" style="font-family: monospace; color: #38bdf8; font-weight: 700;"><?= number_format($system_memory['allocated_mb'] ?? 0, 2) ?> MB</span>
                </div>
            </div>
            <div style="position: relative; width: 100%; height: 160px;">
                <canvas id="os-memory-chart" style="width: 100%; height: 160px; display: block;"></canvas>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-top: 6px;">
                <span>← Histórico (últimos 30 ciclos)</span>
                <span id="chart-latest-label">Tempo real: <?= htmlspecialchars($system_memory['time_str'] ?? date('H:i:s')) ?></span>
            </div>
        </div>
        
        <?php if (empty($processes)): ?>
            <div id="os-empty-stack" style="background: var(--bg-panel); padding: 30px; text-align: center; border: 1px solid var(--border); border-radius: 4px;">
                <p>Nenhuma pilha de processos foi registrada nesta inicialização.</p>
            </div>
        <?php endif; ?>

        <!-- Tabela de Pilha de Serviços com Indicadores Dinâmicos de Consumo e Liberação -->
        <table class="wp-list-table" id="os-process-table" style="<?= empty($processes) ? 'display:none;' : '' ?>">
            <thead>
                <tr>
                    <th style="width: 130px;">PID (Específico)</th>
                    <th>Módulo / Plugin</th>
                    <th>Camada (Ring)</th>
                    <th>Memória Atual (Δ Oscilação)</th>
                    <th>Pico Histórico</th>
                    <th>Tempo de Boot</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="process-table-body">
                <?php foreach ($processes as $pid => $proc): ?>
                <?php
                    $memUsed = (float)($proc['memory_used_kb'] ?? 0);
                    $peakMem = (float)($proc['peak_memory_kb'] ?? $memUsed);
                    $delta   = (float)($proc['memory_delta_kb'] ?? 0);
                ?>
                <tr id="proc-row-<?= htmlspecialchars($pid) ?>">
                    <td style="font-family: monospace; font-weight: bold; color: var(--accent-blue);"><?= htmlspecialchars($proc['pid']) ?></td>
                    <td><strong><?= htmlspecialchars($proc['name']) ?></strong></td>
                    <td>
                        <?php if($proc['type'] === 'SystemApp'): ?>
                            <span class="badge" style="background: rgba(88,166,255,0.1); color: var(--accent-blue); border: 1px solid var(--accent-blue);">Ring 0 (Core)</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(245,110,40,0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange);">Ring 3 (User)</span>
                        <?php endif; ?>
                    </td>
                    <td class="proc-mem-cell">
                        <span class="proc-mem-val" style="font-weight: 600; color: <?= $memUsed > 5000 ? '#ef4444' : 'var(--text-main)' ?>;">
                            <?= number_format($memUsed, 2) ?> KB
                        </span>
                        <?php if ($delta > 0): ?>
                            <span class="proc-delta-badge" style="font-size: 11px; margin-left: 6px; padding: 1px 5px; border-radius: 3px; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">▲ +<?= number_format($delta, 2) ?> KB</span>
                        <?php elseif ($delta < 0): ?>
                            <span class="proc-delta-badge" style="font-size: 11px; margin-left: 6px; padding: 1px 5px; border-radius: 3px; background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">▼ <?= number_format($delta, 2) ?> KB</span>
                        <?php endif; ?>
                    </td>
                    <td class="proc-peak-cell" style="color: #94a3b8; font-size: 12px;">
                        <?= number_format($peakMem, 2) ?> KB
                    </td>
                    <td class="proc-time-cell" style="color: <?= $proc['duration_ms'] > 100 ? '#ef4444' : 'var(--accent-green)' ?>; font-family: monospace; font-weight: 500;">
                        <?= number_format($proc['duration_ms'], 2) ?> ms
                    </td>
                    <td class="proc-status-cell">
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
    if (tabId === 'tab-processos' && window.DS_TRIGGER_CHART_RESIZE) {
        setTimeout(window.DS_TRIGGER_CHART_RESIZE, 50);
    }
}

// ═══════════════════════════════════════════════
// Gráfico ao Vivo de Memória & Heartbeat da Pilha
// ═══════════════════════════════════════════════
(function() {
    let pollInterval = null;
    const maxHistoryPoints = 30;
    
    // Inicializa o buffer histórico com dados iniciais do PHP
    const initialAllocated = <?= (float)($system_memory['allocated_mb'] ?? 0) ?>;
    const initialPeak = <?= (float)($system_memory['peak_mb'] ?? 0) ?>;
    const initialTime = "<?= htmlspecialchars($system_memory['time_str'] ?? date('H:i:s')) ?>";

    const memoryHistory = [];
    for (let i = 0; i < 15; i++) {
        memoryHistory.push({
            time: initialTime,
            allocated: initialAllocated,
            peak: initialPeak
        });
    }

    // Mapa de memória anterior por PID para detectar oscilações dinâmicas
    const previousMemoryMap = {};
    <?php foreach ($processes as $pid => $proc): ?>
        previousMemoryMap['<?= htmlspecialchars($pid) ?>'] = <?= (float)($proc['memory_used_kb'] ?? 0) ?>;
    <?php endforeach; ?>

    const canvas = document.getElementById('os-memory-chart');
    const ctx = canvas ? canvas.getContext('2d') : null;

    function resizeCanvas() {
        if (!canvas || !ctx) return;
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        drawLiveChart();
    }
    window.DS_TRIGGER_CHART_RESIZE = resizeCanvas;
    window.addEventListener('resize', resizeCanvas);

    function drawLiveChart() {
        if (!canvas || !ctx || memoryHistory.length === 0) return;

        const dpr = window.devicePixelRatio || 1;
        const w = canvas.width / dpr;
        const h = canvas.height / dpr;

        ctx.save();
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, w, h);

        // Calcula escala mínima e máxima dinâmica com margem
        let minVal = Infinity;
        let maxVal = -Infinity;
        memoryHistory.forEach(pt => {
            if (pt.allocated < minVal) minVal = pt.allocated;
            if (pt.allocated > maxVal) maxVal = pt.allocated;
            if (pt.peak > maxVal) maxVal = pt.peak;
        });

        if (minVal === Infinity) minVal = 0;
        if (maxVal <= minVal) maxVal = minVal + 5;

        const paddingBottom = 22;
        const paddingTop = 15;
        const paddingLeft = 45;
        const paddingRight = 15;
        const plotW = w - paddingLeft - paddingRight;
        const plotH = h - paddingTop - paddingBottom;

        const valSpan = (maxVal - minVal) * 1.15 || 1;
        const baseMin = Math.max(0, minVal - valSpan * 0.05);
        const baseMax = baseMin + valSpan;

        function getY(val) {
            const norm = (val - baseMin) / (baseMax - baseMin);
            return paddingTop + plotH - norm * plotH;
        }

        function getX(idx) {
            if (memoryHistory.length <= 1) return paddingLeft;
            return paddingLeft + (idx / (memoryHistory.length - 1)) * plotW;
        }

        // 1. Grid Lines e Rótulos Y
        const gridSteps = 4;
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#64748b';
        ctx.font = '10px monospace';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';

        for (let i = 0; i <= gridSteps; i++) {
            const stepVal = baseMin + (i / gridSteps) * (baseMax - baseMin);
            const y = getY(stepVal);
            ctx.beginPath();
            ctx.moveTo(paddingLeft, y);
            ctx.lineTo(w - paddingRight, y);
            ctx.stroke();

            ctx.fillText(stepVal.toFixed(1) + 'M', paddingLeft - 6, y);
        }

        // 2. Linha do Pico Máximo (Tracejada Dourada)
        const latestPeak = memoryHistory[memoryHistory.length - 1].peak;
        if (latestPeak > 0) {
            const peakY = getY(latestPeak);
            ctx.save();
            ctx.setLineDash([4, 4]);
            ctx.strokeStyle = '#f59e0b';
            ctx.lineWidth = 1.2;
            ctx.beginPath();
            ctx.moveTo(paddingLeft, peakY);
            ctx.lineTo(w - paddingRight, peakY);
            ctx.stroke();

            ctx.fillStyle = '#f59e0b';
            ctx.font = '10px monospace';
            ctx.textAlign = 'right';
            ctx.fillText('Pico ' + latestPeak.toFixed(1) + 'M', w - paddingRight, Math.max(peakY - 5, paddingTop));
            ctx.restore();
        }

        // 3. Área Preenchida com Gradiente Neon
        const grad = ctx.createLinearGradient(0, paddingTop, 0, paddingTop + plotH);
        grad.addColorStop(0, 'rgba(6, 182, 212, 0.35)');
        grad.addColorStop(1, 'rgba(6, 182, 212, 0.0)');

        ctx.beginPath();
        ctx.moveTo(getX(0), getY(memoryHistory[0].allocated));
        for (let i = 1; i < memoryHistory.length; i++) {
            const prevX = getX(i - 1);
            const prevY = getY(memoryHistory[i - 1].allocated);
            const curX = getX(i);
            const curY = getY(memoryHistory[i].allocated);
            const midX = (prevX + curX) / 2;
            ctx.bezierCurveTo(midX, prevY, midX, curY, curX, curY);
        }
        ctx.lineTo(getX(memoryHistory.length - 1), paddingTop + plotH);
        ctx.lineTo(getX(0), paddingTop + plotH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // 4. Linha Principal Neon Cyan da Memória Alocada
        ctx.beginPath();
        ctx.moveTo(getX(0), getY(memoryHistory[0].allocated));
        for (let i = 1; i < memoryHistory.length; i++) {
            const prevX = getX(i - 1);
            const prevY = getY(memoryHistory[i - 1].allocated);
            const curX = getX(i);
            const curY = getY(memoryHistory[i].allocated);
            const midX = (prevX + curX) / 2;
            ctx.bezierCurveTo(midX, prevY, midX, curY, curX, curY);
        }
        ctx.shadowColor = 'rgba(6, 182, 212, 0.8)';
        ctx.shadowBlur = 8;
        ctx.strokeStyle = '#06b6d4';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        ctx.shadowBlur = 0;

        // 5. Ponto Pulsante da Leitura Mais Recente
        const lastIdx = memoryHistory.length - 1;
        const lastX = getX(lastIdx);
        const lastY = getY(memoryHistory[lastIdx].allocated);

        ctx.beginPath();
        ctx.arc(lastX, lastY, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#38bdf8';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(lastX, lastY, 7, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(56, 189, 248, 0.5)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        ctx.restore();
    }

    function setLiveStatus(status, color) {
        const dot = document.getElementById('os-live-dot');
        const txt = document.getElementById('os-live-text');
        if (dot) {
            dot.style.background = color;
            dot.style.boxShadow = '0 0 8px ' + color;
        }
        if (txt) txt.textContent = status;
    }

    function updateKpis(mem, singletonId) {
        if (!mem) return;
        const elAllocated = document.getElementById('kpi-mem-allocated');
        const elCurrent   = document.getElementById('kpi-mem-current');
        const elPeak      = document.getElementById('kpi-mem-peak');
        const elPercent   = document.getElementById('kpi-mem-percent');
        const elBar       = document.getElementById('kpi-mem-bar');
        const elLiveVal   = document.getElementById('chart-live-val');
        const elLatestLbl = document.getElementById('chart-latest-label');
        const elSingleton = document.getElementById('kpi-singleton-id');
        const elBadge     = document.getElementById('os-singleton-badge');

        if (elAllocated) elAllocated.textContent = parseFloat(mem.allocated_mb).toFixed(2);
        if (elCurrent) elCurrent.textContent = parseFloat(mem.current_mb).toFixed(2);
        if (elPeak) elPeak.textContent = parseFloat(mem.peak_mb).toFixed(2);
        if (elPercent) elPercent.textContent = mem.percentage + '%';
        if (elLiveVal) elLiveVal.textContent = parseFloat(mem.allocated_mb).toFixed(2) + ' MB';
        if (elLatestLbl) elLatestLbl.textContent = 'Tempo real: ' + mem.time_str;
        if (elBar) {
            const p = Math.min(100, Math.max(0, mem.percentage));
            elBar.style.width = p + '%';
            if (p > 80) {
                elBar.style.background = 'linear-gradient(90deg, #f97316, #ef4444)';
            } else if (p > 60) {
                elBar.style.background = 'linear-gradient(90deg, #eab308, #f59e0b)';
            } else {
                elBar.style.background = 'linear-gradient(90deg, #06b6d4, #38bdf8)';
            }
        }
        if (singletonId) {
            if (elSingleton) elSingleton.textContent = singletonId;
            if (elBadge) elBadge.textContent = singletonId;
        }
    }

    const previousDurationMap = {};

    function renderProcessTable(processes) {
        const tbody = document.getElementById('process-table-body');
        const table = document.getElementById('os-process-table');
        const empty = document.getElementById('os-empty-stack');
        if (!tbody) return;

        let hasProcesses = false;
        for (let pid in processes) {
            hasProcesses = true;
            let p = processes[pid];
            let row = document.getElementById('proc-row-' + p.pid);

            let curMem = parseFloat(p.memory_used_kb || 0);
            let prevMem = previousMemoryMap[pid] !== undefined ? previousMemoryMap[pid] : curMem;
            let delta = Math.round((curMem - prevMem) * 100) / 100;
            previousMemoryMap[pid] = curMem;

            // Se o servidor já enviou delta explícito, usamos
            if (p.memory_delta_kb !== undefined && Math.abs(parseFloat(p.memory_delta_kb)) > 0) {
                delta = parseFloat(p.memory_delta_kb);
            }

            let curDuration = parseFloat(p.duration_ms || 0);
            let prevDuration = previousDurationMap[pid] !== undefined ? previousDurationMap[pid] : curDuration;
            previousDurationMap[pid] = curDuration;

            let deltaBadge = '';
            if (delta > 0) {
                deltaBadge = `<span class="proc-delta-badge" style="font-size:11px; margin-left:6px; padding:1px 5px; border-radius:3px; background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);">▲ +${delta.toFixed(2)} KB</span>`;
            } else if (delta < 0) {
                deltaBadge = `<span class="proc-delta-badge" style="font-size:11px; margin-left:6px; padding:1px 5px; border-radius:3px; background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);">▼ ${delta.toFixed(2)} KB</span>`;
            }

            let memC = curMem > 5000 ? '#ef4444' : 'var(--text-main)';
            let peakMem = parseFloat(p.peak_memory_kb || curMem).toFixed(2);
            let timeC = curDuration > 100 ? '#ef4444' : 'var(--accent-green)';
            let st = p.status === 'Running'
                ? '<span style="color:var(--accent-green);">● Estável</span>'
                : '<span style="color:#ef4444;font-weight:bold;">✖ Crashed</span>';

            let ring = p.type === 'SystemApp'
                ? '<span class="badge" style="background:rgba(88,166,255,0.1);color:var(--accent-blue);border:1px solid var(--accent-blue);">Ring 0 (Core)</span>'
                : '<span class="badge" style="background:rgba(245,110,40,0.1);color:var(--accent-orange);border:1px solid var(--accent-orange);">Ring 3 (User)</span>';

            if (row) {
                const memCell = row.querySelector('.proc-mem-cell');
                if (memCell) {
                    memCell.innerHTML = `<span class="proc-mem-val" style="font-weight:600; color:${memC};">${curMem.toFixed(2)} KB</span> ${deltaBadge}`;
                }
                const peakCell = row.querySelector('.proc-peak-cell');
                if (peakCell) {
                    peakCell.textContent = peakMem + ' KB';
                }
                const timeCell = row.querySelector('.proc-time-cell');
                if (timeCell) {
                    timeCell.style.color = timeC;
                    timeCell.textContent = curDuration.toFixed(2) + ' ms';
                    if (Math.abs(curDuration - prevDuration) > 0.01) {
                        timeCell.style.transition = 'text-shadow 0.3s ease';
                        timeCell.style.textShadow = '0 0 10px rgba(16, 185, 129, 0.9)';
                        setTimeout(() => { timeCell.style.textShadow = 'none'; }, 600);
                    }
                }
                const statusCell = row.querySelector('.proc-status-cell');
                if (statusCell) {
                    statusCell.innerHTML = st;
                }
            } else {
                const tr = document.createElement('tr');
                tr.id = 'proc-row-' + p.pid;
                tr.innerHTML = `
                    <td style="font-family:monospace;font-weight:bold;color:var(--accent-blue);">${p.pid}</td>
                    <td><strong>${p.name}</strong></td>
                    <td>${ring}</td>
                    <td class="proc-mem-cell">
                        <span class="proc-mem-val" style="font-weight:600; color:${memC};">${curMem.toFixed(2)} KB</span>
                        ${deltaBadge}
                    </td>
                    <td class="proc-peak-cell" style="color:#94a3b8; font-size:12px;">${peakMem} KB</td>
                    <td class="proc-time-cell" style="color:${timeC}; font-family: monospace; font-weight: 500;">${curDuration.toFixed(2)} ms</td>
                    <td class="proc-status-cell">${st}</td>
                `;
                tbody.appendChild(tr);
            }
        }

        if (hasProcesses) {
            if (table) table.style.display = '';
            if (empty) empty.style.display = 'none';
        }
    }

    function poll() {
        const tab = document.getElementById('tab-processos');
        if (!tab || !tab.classList.contains('active')) {
            setLiveStatus('Pausado (aba inativa)', '#334155');
            return;
        }

        setLiveStatus('Consultando...', '#f59e0b');

        fetch(window.DS_BASE_URL + 'api/os/monitor/stack')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.system_memory) {
                        updateKpis(data.system_memory, data.singleton_id);
                        memoryHistory.push({
                            time: data.system_memory.time_str,
                            allocated: parseFloat(data.system_memory.allocated_mb),
                            peak: parseFloat(data.system_memory.peak_mb)
                        });
                        if (memoryHistory.length > maxHistoryPoints) {
                            memoryHistory.shift();
                        }
                        drawLiveChart();
                    }

                    if (data.processes) {
                        renderProcessTable(data.processes);
                    }

                    setLiveStatus('Monitor Ativo', '#10b981');
                }
            })
            .catch(() => setLiveStatus('Erro de conexão', '#ef4444'));
    }

    // Inicializa o Canvas ao carregar a página
    setTimeout(resizeCanvas, 100);

    // Heartbeat a cada 3 segundos
    pollInterval = setInterval(poll, 3000);

    // Executa imediatamente ao abrir a aba
    document.querySelectorAll('.sys-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            setTimeout(() => {
                resizeCanvas();
                poll();
            }, 100);
        });
    });
})();
</script>