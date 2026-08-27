<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Agenda de Consultas</h1>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
    <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
        <?= htmlspecialchars($msg['msg']) ?>
    </div>
<?php endif; ?>

<style>
    .flex-container { display: flex; flex-direction: column; gap: 20px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .form-panel { width: 100%; max-width: 800px; margin-bottom: 20px; }
    @media (max-width: 768px) {
        .flex-container { flex-direction: column; }
        .table-responsive, .form-panel { width: 100%; flex: none; }
    }
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .status-pendente { background: #fff3cd; color: #856404; }
    .status-confirmado { background: #d1ecf1; color: #0c5460; }
    .status-atendido { background: #d4edda; color: #155724; }
    
    .status-aguardando-triagem { background: #ffeeba; color: #856404; } .status-aguardando-médico { background: #cce5ff; color: #004085; } .status-aguardando { background: #ffeeba; color: #856404; } /* Aguardando Triagem */
    .status-medico { background: #cce5ff; color: #004085; } /* Aguardando Médico */
    .status-cancelado { background: #f8d7da; color: #721c24; text-decoration: line-through; }
</style>

<div class="flex-container">
    
    <!-- Formulário de Novo Agendamento (Injetado via partial) -->
    <?php include __DIR__ . '/partials/booking_form.php'; ?>

    <!-- Tabela de Agendamentos -->
    <div class="table-responsive">
        <table class="wp-list-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente / Atendimento</th>
                    <th>Médico</th>
                    <th>Status</th>
                    <th style="width: 200px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum agendamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($appointments as $a): ?>
                        <?php 
                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $a['status']));
                            $dateObj = new DateTime($a['appointment_date'] . ' ' . $a['appointment_time']);
                            $attendance = $a['attendance_type'] === 'conveniado' ? 'Conveniado (' . ($a['health_insurance'] ?: 'Não Informado') . ')' : 'Particular';
                        ?>
                        <tr>
                            <td>
                                <strong><?= $dateObj->format('d/m/Y') ?></strong><br>
                                <small><?= $dateObj->format('H:i') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($a['patient_name']) ?></strong><br>
                                <small style="color:#0073aa; font-weight:bold;"><?= htmlspecialchars($attendance) ?></small><br>
                                <small style="color:#666;"><?= htmlspecialchars($a['reception_notes']) ?></small>
                                <?php if (!empty($a['patient_phone'])): ?>
                                    <div style="margin-top: 5px;">
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $a['patient_phone']) ?>" target="_blank" class="btn" style="background: #25D366; color: white; border: none; font-size: 11px; padding: 2px 6px;">💬 WhatsApp</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($a['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($a['status'] !== 'Cancelado'): ?>
                                    <div style="display:flex; flex-direction:column; gap:5px;">
                                        <div style="display:flex; gap:5px;">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/appointments/status" style="margin:0;">
                                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                <select name="status" onchange="this.form.submit()" style="font-size: 11px; padding: 2px;">
                                                    <option value="">Alterar...</option>
                                                    <option value="Confirmado">Confirmar</option>
                                                    <option value="Aguardando Triagem">Chegou (Aguardando Triagem)</option>
                                                    <option value="Pendente">Marcar Pendente</option>
                                                    <option value="Cancelado">Cancelar Consulta</option>
                                                </select>
                                            </form>
                                        </div>
                                        <a href="<?= BASE_URL ?>/admin/appointments/record/<?= $a['id'] ?>" class="btn btn-activate" style="text-decoration:none; text-align:center; font-size: 11px; padding: 3px 8px;">Prontuário Médico</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
