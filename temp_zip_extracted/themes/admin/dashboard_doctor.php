<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="margin-bottom: 5px;">🩺 Meu Dashboard</h1>
        <p style="color: #64748b; margin: 0;">Visão geral dos seus atendimentos de hoje.</p>
    </div>
</div>

<style>
    .kpi-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); flex: 1; border: 1px solid #e2e8f0; display: flex; align-items: center; }
    .kpi-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 24px; }
    .kpi-title { font-size: 0.85rem; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 5px; }
    .kpi-value { font-size: 2rem; color: #0f172a; font-weight: 800; margin: 0; line-height: 1; }
    
    .panel-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
    .panel-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; font-weight: bold; color: #1e293b; font-size: 1.1rem; }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
    .status-pendente { background: #fef3c7; color: #d97706; }
    .status-aguardando-triagem { background: #ffeeba; color: #856404; }
    .status-aguardando-medico { background: #cce5ff; color: #004085; }
    .status-confirmado { background: #dcfce3; color: #166534; }
    .status-cancelado { background: #fee2e2; color: #991b1b; text-decoration: line-through; }
    .status-finalizado { background: #e0e7ff; color: #3730a3; }
</style>

<!-- KPI Cards -->
<div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
    <div class="kpi-card">
        <div class="kpi-icon" style="background: #eff6ff; color: #3b82f6;">📅</div>
        <div>
            <div class="kpi-title">Consultas Hoje</div>
            <div class="kpi-value"><?= number_format($appointmentsToday ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon" style="background: #f0fdf4; color: #22c55e;">⏳</div>
        <div>
            <div class="kpi-title">Aguardando</div>
            <div class="kpi-value"><?= number_format($pendingQueue ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background: #fef2f2; color: #ef4444;">👥</div>
        <div>
            <div class="kpi-title">Meus Pacientes (Total)</div>
            <div class="kpi-value"><?= number_format($patientsServed ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>
</div>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <!-- Fila de Atendimento -->
    <div class="panel-card" style="flex: 2; min-width: 400px;">
        <div class="panel-header">Minha Agenda de Hoje</div>
        <div style="padding: 0;">
            <table class="wp-list-table" style="border: none; box-shadow: none; width: 100%;">
                <thead>
                    <tr>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b; text-align: left;">Horário</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b; text-align: left;">Paciente</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b; text-align: left;">Status</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b; text-align: right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">Nenhum atendimento agendado para hoje.</td></tr>
                    <?php else: ?>
                        <?php foreach ($queue as $app): 
                            $statusClass = 'status-' . strtolower(str_replace([' ', 'é'], ['-', 'e'], $app['status']));
                        ?>
                        <tr>
                            <td style="padding: 15px 25px; font-weight: bold; color: #3b82f6;"><?= htmlspecialchars(substr($app['appointment_time'], 0, 5)) ?></td>
                            <td style="padding: 15px 25px; font-weight: 500; color: #1e293b;"><?= htmlspecialchars($app['patient_name']) ?></td>
                            <td style="padding: 15px 25px;"><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                            <td style="padding: 15px 25px; text-align: right;">
                                <?php if ($app['status'] !== 'Cancelado' && $app['status'] !== 'Finalizado'): ?>
                                    <a href="<?= BASE_URL ?>/admin/appointments/record/<?= $app['id'] ?>" class="btn" style="background: #10b981; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold;">Chamar Paciente</a>
                                <?php elseif ($app['status'] === 'Finalizado'): ?>
                                    <a href="<?= BASE_URL ?>/admin/appointments/record/<?= $app['id'] ?>" class="btn" style="background: #64748b; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold;">Ver Prontuário</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráfico de Produtividade -->
    <div class="panel-card" style="flex: 1; min-width: 250px;">
        <div class="panel-header">Minha Produtividade (7 dias) 📈</div>
        <div style="padding: 20px;">
            <canvas id="doctorChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('doctorChart').getContext('2d');
        const chartData = <?= $chartData ?? '{"labels":[],"data":[]}' ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Pacientes Atendidos',
                    data: chartData.data,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    });
</script>

