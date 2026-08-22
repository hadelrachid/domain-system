<?php ob_start(); ?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="margin-bottom: 5px;">Dashboard</h1>
        <p style="color: #64748b; margin: 0;">Visão geral da clínica e atendimentos de hoje.</p>
    </div>
    <div>
        <a href="admin/appointments" class="btn" style="background: #2563eb; color: #fff; border-color: #2563eb; padding: 8px 16px; border-radius: 6px; font-weight: bold;">+ Novo Agendamento</a>
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
    .status-confirmado { background: #dcfce3; color: #166534; }
    .status-cancelado { background: #fee2e2; color: #991b1b; }
    .status-concluido { background: #e0e7ff; color: #3730a3; }
</style>

<!-- KPI Cards -->
<div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
    <div class="kpi-card">
        <div class="kpi-icon" style="background: #eff6ff; color: #3b82f6;">👥</div>
        <div>
            <div class="kpi-title">Pacientes Registrados</div>
            <div class="kpi-value"><?= number_format($totalPatients ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon" style="background: #f0fdf4; color: #22c55e;">🩺</div>
        <div>
            <div class="kpi-title">Médicos Ativos</div>
            <div class="kpi-value"><?= number_format($totalDoctors ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background: #fef2f2; color: #ef4444;">📅</div>
        <div>
            <div class="kpi-title">Consultas Hoje</div>
            <div class="kpi-value"><?= number_format($appointmentsToday ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>
</div>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <!-- Fila de Atendimento -->
    <div class="panel-card" style="flex: 2; min-width: 400px;">
        <div class="panel-header">Próximos Atendimentos (Hoje)</div>
        <div style="padding: 0;">
            <table class="wp-list-table" style="border: none; box-shadow: none;">
                <thead>
                    <tr>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b;">Horário</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b;">Paciente</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b;">Médico</th>
                        <th style="background: #fff; padding: 15px 25px; color: #64748b;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">Nenhum atendimento agendado para hoje.</td></tr>
                    <?php else: ?>
                        <?php foreach ($queue as $app): 
                            $statusClass = 'status-pendente';
                            $st = strtolower($app['status']);
                            if (str_contains($st, 'confirmado')) $statusClass = 'status-confirmado';
                            if (str_contains($st, 'cancelado')) $statusClass = 'status-cancelado';
                            if (str_contains($st, 'conclu')) $statusClass = 'status-concluido';
                        ?>
                        <tr>
                            <td style="padding: 15px 25px; font-weight: bold; color: #3b82f6;"><?= htmlspecialchars(substr($app['appointment_time'], 0, 5)) ?></td>
                            <td style="padding: 15px 25px; font-weight: 500; color: #1e293b;"><?= htmlspecialchars($app['patient_name']) ?></td>
                            <td style="padding: 15px 25px; color: #64748b;"><?= htmlspecialchars($app['doctor_name']) ?></td>
                            <td style="padding: 15px 25px;"><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="panel-card" style="flex: 1; min-width: 250px;">
        <div class="panel-header">Ações Rápidas</div>
        <div style="padding: 25px;">
            <a href="admin/patients" class="btn" style="display: block; width: 100%; box-sizing: border-box; text-align: center; padding: 12px; margin-bottom: 15px; background: #fff; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; transition: all 0.2s;">
                👥 Gerenciar Pacientes
            </a>
            <a href="admin/users" class="btn" style="display: block; width: 100%; box-sizing: border-box; text-align: center; padding: 12px; margin-bottom: 15px; background: #fff; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; transition: all 0.2s;">
                🔐 Controle de Usuários
            </a>
            <a href="admin/plugins" class="btn" style="display: block; width: 100%; box-sizing: border-box; text-align: center; padding: 12px; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: bold; transition: all 0.2s;">
                ⚙️ Configurar Sistema
            </a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
