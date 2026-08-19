<?php ob_start(); ?>

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
        .flex-container { display: flex; gap: 20px; align-items: flex-start; }
        .table-responsive { overflow-x: auto; flex: 2; }
        .form-panel { flex: 1; min-width: 300px; }
        @media (max-width: 768px) {
            .flex-container { flex-direction: column; }
            .table-responsive, .form-panel { width: 100%; flex: none; }
        }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-confirmado { background: #d1ecf1; color: #0c5460; }
        .status-atendido { background: #d4edda; color: #155724; }
        .status-cancelado { background: #f8d7da; color: #721c24; text-decoration: line-through; }
    </style>

    <div class="flex-container">
        
        <!-- Formulário de Novo Agendamento -->
        <div class="upload-box form-panel">
            <h2 style="margin-top: 0;">Novo Agendamento</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/appointments">
                
                <label style="display:block; margin-bottom: 5px;">Paciente</label>
                <select name="patient_id" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">
                    <option value="">-- Selecione o Paciente --</option>
                    <?php foreach($patients as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (CPF: <?= htmlspecialchars($p['cpf']) ?>)</option>
                    <?php endforeach; ?>
                </select>

                <label style="display:block; margin-bottom: 5px;">Médico</label>
                <select name="doctor_id" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">
                    <option value="">-- Selecione o Médico --</option>
                    <?php foreach($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="display:flex; gap:10px; margin-bottom: 15px;">
                    <div style="flex:2;">
                        <label style="display:block; margin-bottom: 5px;">Data</label>
                        <input type="date" name="appointment_date" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">Horário</label>
                        <input type="time" name="appointment_time" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-bottom: 15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">Tipo de Atendimento</label>
                        <select name="attendance_type" id="attendance_type" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                            <option value="particular">Particular</option>
                            <option value="conveniado">Conveniado</option>
                        </select>
                    </div>
                    <div style="flex:1;" id="health_insurance_group" style="display:none;">
                        <label style="display:block; margin-bottom: 5px;">Qual Convênio?</label>
                        <select name="health_insurance" style="width: 100%; padding: 8px; box-sizing: border-box;">
                            <option value="">-- Opcional --</option>
                            <option value="Unimed">Unimed</option>
                            <option value="Porto Seguro">Porto Seguro</option>
                            <option value="Cassi">Cassi</option>
                            <option value="Petrobras">Petrobrás</option>
                            <option value="Amil Fácil">Amil Fácil</option>
                            <option value="Amil Saúde">Amil Saúde</option>
                            <option value="Amil One">Amil One</option>
                        </select>
                    </div>
                </div>

                <label style="display:block; margin-bottom: 5px;">Motivo da Consulta / Sintomas (Observações)</label>
                <textarea name="reception_notes" rows="3" style="width: 100%; padding: 8px; margin-bottom: 20px; box-sizing: border-box;" placeholder="Sintomas, retorno, primeira vez..."></textarea>

                <button type="submit" class="btn btn-activate" style="width: 100%; text-align: center;">Confirmar Agendamento</button>
            </form>
        </div>

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
                                $statusClass = 'status-' . strtolower($a['status']);
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
                                                        <option value="Pendente">Marcar Pendente</option>
                                                        <option value="Cancelado">Cancelar Consulta</option>
                                                    </select>
                                                </form>
                                            </div>
                                            <a href="<?= BASE_URL ?>/admin/appointments/record?id=<?= $a['id'] ?>" class="btn btn-activate" style="text-decoration:none; text-align:center; font-size: 11px; padding: 3px 8px;">Prontuário Médico</a>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var attType = document.getElementById('attendance_type');
            var healthInsGroup = document.getElementById('health_insurance_group');
            
            function toggleInsurance() {
                if (attType.value === 'conveniado') {
                    healthInsGroup.style.display = 'block';
                } else {
                    healthInsGroup.style.display = 'none';
                }
            }
            
            attType.addEventListener('change', toggleInsurance);
            toggleInsurance();
        });
    </script>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
