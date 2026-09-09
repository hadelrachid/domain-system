<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CockPIT Médico</title>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; background: #f0f2f5; margin: 0; padding: 0; color: #1d2327; }
        
        .header { background: #fff; padding: 15px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-top: 4px solid #10b981; }
        .header h1 { margin: 0; color: #10b981; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .user-profile { display: flex; align-items: center; gap: 15px; cursor: pointer; }
        .user-profile img { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #10b981; }
        .user-profile .name { font-weight: 600; font-size: 15px; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Cards de Agendamento */
        .appointment-card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; border-left: 4px solid #10b981; }
        .appointment-card.status-concluido { border-left-color: #64748b; opacity: 0.7; }
        
        .info-group { display: flex; flex-direction: column; gap: 5px; }
        .info-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .info-value { font-size: 15px; font-weight: 500; }
        
        /* Status Select */
        .status-select { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid transparent; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; padding-right: 25px; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto; }
        .status-select.Confirmado { background-color: #d1fae5; color: #059669; border-color: #6ee7b7; }
        .status-select.Concluído { background-color: #e2e8f0; color: #475569; border-color: #cbd5e1; }
        .status-select:focus { box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
        
        .btn-medical { padding: 8px 15px; border-radius: 6px; border: 1px solid #10b981; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; color: #10b981; transition: 0.2s; background: transparent; }
        .btn-medical:hover { background: #10b981; color: white; }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: flex-start; z-index: 1000; overflow-y: auto; padding: 40px 20px; box-sizing: border-box; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 700px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; margin: auto; }
        .modal-close { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        
        /* Modal Tabs */
        .modal-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .modal-tab { padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; border-bottom: 3px solid transparent; }
        .modal-tab.active { color: #10b981; border-bottom-color: #10b981; }
        .modal-tab:hover { color: #10b981; }
        .modal-tab-content { display: none; }
        .modal-tab-content.active { display: block; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #dcdcdc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .btn-save { background: #10b981; color: white; padding: 10px 20px; width: 100%; font-size: 16px; margin-top: 10px; cursor: pointer; border: none; border-radius: 4px; font-weight: bold; transition: 0.2s; }
        .btn-save:hover { background: #059669; }

        /* Schedule Grid */
        .schedule-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; }
        @media (max-width: 768px) { .schedule-grid { grid-template-columns: 1fr; } }
        .day-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
        .day-title { font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .period-row { display: flex; gap: 5px; margin-bottom: 5px; align-items: center; flex-wrap: wrap; }
        .period-row input { padding: 4px; font-size: 12px; }
        .btn-add-period { background: #f1f5f9; border: 1px dashed #cbd5e1; color: #64748b; width: 100%; padding: 5px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .btn-add-period:hover { background: #e2e8f0; }
        .btn-rm-period { color: #ef4444; cursor: pointer; background: none; border: none; font-size: 16px; }
    </style>
</head>
<body>
    
    <div class="header">
        <h1><i class="fas fa-stethoscope"></i> CockPIT Médico</h1>
        <div class="user-profile" onclick="toggleSettingsModal()" title="Configurações do Perfil">
            <div class="name">Dr(a). <?= htmlspecialchars($user_name) ?></div>
            <img src="<?= !empty($profile_image) ? BASE_URL . htmlspecialchars($profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=d1fae5&color=059669&size=150' ?>" alt="Perfil">
            <i class="fas fa-cog" style="color: #64748b;"></i>
        </div>
    </div>

    <div class="container">
        
        <?php if(!$doctor_id): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; border-left: 4px solid #ef4444;">
                <i class="fas fa-exclamation-triangle"></i> Atenção: Sua conta de usuário ainda não foi vinculada a um perfil de Médico no sistema. Contate a administração.
            </div>
        <?php else: ?>

            <?php if(isset($_GET['success'])): ?>
                <div style="background: #d1fae5; color: #059669; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #059669;">
                    Operação realizada com sucesso!
                </div>
            <?php endif; ?>

            <h2 style="color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
                <i class="fas fa-user-injured" style="color: #10b981;"></i> Meus Pacientes de Hoje
            </h2>

            <!-- ABA DE PACIENTES CONFIRMADOS -->
            <div id="tab-pacientes">
                <?php if(empty($appointments)): ?>
                    <div style="text-align: center; padding: 50px; color: #64748b; background: #fff; border-radius: 8px;">
                        <i class="fas fa-mug-hot" style="font-size: 40px; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <h2>Nenhum paciente aguardando no momento.</h2>
                    </div>
                <?php else: ?>
                    <?php foreach($appointments as $app): ?>
                    <div class="appointment-card" id="card-<?= $app['id'] ?>">
                        
                        <div class="info-group" style="min-width: 150px;">
                            <span class="info-label">Paciente</span>
                            <span class="info-value"><i class="fas fa-user" style="color:#10b981; margin-right:5px;"></i> <?= htmlspecialchars($app['patient_name'] ?: 'Não informado') ?></span>
                        </div>
                        
                        <div class="info-group" style="min-width: 120px;">
                            <span class="info-label">Horário</span>
                            <span class="info-value"><i class="fas fa-clock" style="color:#94a3b8; margin-right:5px;"></i> <?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= $app['appointment_time'] ?></span>
                        </div>

                        <div class="info-group" style="min-width: 120px;">
                            <span class="info-label">Tipo</span>
                            <span class="info-value" style="text-transform: capitalize;"><?= htmlspecialchars($app['attendance_type'] ?? 'Particular') ?></span>
                            <?php if(!empty($app['health_insurance'])): ?>
                                <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($app['health_insurance']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="info-group" style="min-width: 130px;">
                            <span class="info-label">Status da Consulta</span>
                            <select class="status-select Confirmado" 
                                    id="status-<?= $app['id'] ?>" 
                                    onchange="changeStatus(<?= $app['id'] ?>, this)">
                                <option value="Confirmado" selected>Na Espera</option>
                                <option value="Concluído">Concluir Consulta</option>
                                <option value="Cancelado">Cancelar</option>
                            </select>
                        </div>

                        <div class="actions">
                            <button class="btn-medical" onclick="alert('Funcionalidade de Prontuário em desenvolvimento.')"><i class="fas fa-notes-medical"></i> Abrir Prontuário</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

    <!-- MODAL DE CONFIGURAÇÕES DE PERFIL E AGENDA -->
    <div class="modal-overlay" id="settingsModal">
        <div class="modal-content">
            <button class="modal-close" onclick="toggleSettingsModal()"><i class="fas fa-times"></i></button>
            <h2 style="margin-top: 0; color: #10b981; margin-bottom: 20px;"><i class="fas fa-user-cog"></i> Configurações do Perfil</h2>
            
            <div class="modal-tabs">
                <div class="modal-tab active" onclick="switchModalTab('dados')" id="mtab-btn-dados">Meus Dados</div>
                <div class="modal-tab" onclick="switchModalTab('agenda')" id="mtab-btn-agenda">Horários de Atendimento</div>
            </div>

            <!-- ABA DADOS NO MODAL -->
            <div id="mtab-dados" class="modal-tab-content active">
                <form action="<?= BASE_URL ?>/cockpit/profile" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                        <img src="<?= !empty($profile_image) ? BASE_URL . htmlspecialchars($profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=d1fae5&color=059669&size=200' ?>" alt="Perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; margin-bottom: 15px;">
                        <br>
                        <input type="file" name="photo" accept="image/*" style="border:none; background: transparent; padding: 0;">
                    </div>
                    
                    <div class="form-group">
                        <label>E-mail (Usado no Login e Notificações 2FA)</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nova Senha</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="doc_password" placeholder="Deixe em branco para manter a atual" style="padding-right: 40px;">
                            <i class="fas fa-eye" id="togglePasswordDoc" style="position: absolute; right: 15px; top: 12px; cursor: pointer; color: #64748b;" onclick="
                                const pwd = document.getElementById('doc_password');
                                if(pwd.type === 'password') { pwd.type = 'text'; this.classList.remove('fa-eye'); this.classList.add('fa-eye-slash'); }
                                else { pwd.type = 'password'; this.classList.remove('fa-eye-slash'); this.classList.add('fa-eye'); }
                            "></i>
                        </div>
                    </div>

                                        <!-- Configuração 2FA -->
                    <div class="form-group" style="margin-top: 20px;">
                        <label style="font-weight:bold; color: #1e293b;">Segurança: Autenticação em Duas Etapas (2FA)</label>
                        <select name="two_factor_type" style="width:100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; background: #fff;">
                            <option value="none" <?= ($two_factor_type === 'none') ? 'selected' : '' ?>>Desativado</option>
                            <option value="app" <?= ($two_factor_type === 'app') ? 'selected' : '' ?>>Aplicativo (Google Authenticator)</option>
                            <option value="email" <?= ($two_factor_type === 'email') ? 'selected' : '' ?>>Envio por E-mail (Código numérico)</option>
                        </select>
                        
                        <?php if ($two_factor_type === 'app'): ?>
                            <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 13px; color: #166534;">
                                <?php if (!empty($two_factor_secret)): ?>
                                    <strong style="display:flex; align-items:center; gap:5px;"><i class="fas fa-check-circle"></i> App Sincronizado</strong>
                                    <small style="display:block; margin-top:4px;">Para refazer a sincronização, altere para "Desativado", salve, e depois ative novamente.</small>
                                <?php else: ?>
                                    <strong style="color: #991b1b; display:flex; align-items:center; gap:5px;"><i class="fas fa-exclamation-triangle"></i> Pendente de Configuração</strong>
                                    <a href="<?= BASE_URL ?>/cockpit/profile/2fa" style="display: inline-block; margin-top: 8px; background: #dc2626; color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight:bold; font-size: 12px;">Configurar QR Code Agora</a>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($two_factor_type === 'email'): ?>
                            <div style="padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 13px; color: #1e40af;">
                                <strong style="display:flex; align-items:center; gap:5px;"><i class="fas fa-envelope-circle-check"></i> E-mail Ativado</strong>
                                <small style="display:block; margin-top:4px;">O código de acesso será enviado para o e-mail cadastrado acima toda vez que você entrar.</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Salvar Dados Pessoais</button>
                </form>
            </div>

            <!-- ABA AGENDA NO MODAL -->
            <div id="mtab-agenda" class="modal-tab-content">
                <p style="color: #64748b; margin-bottom: 20px; font-size: 14px;">Defina os dias e horários que você estará disponível na clínica.</p>
                
                <form action="<?= BASE_URL ?>/admin/doctors/schedule/save" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
                    <input type="hidden" name="redirect" value="/cockpit/doctor">
                    
                    <?php
                    $daysOfWeek = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                    $mappedScheds = [];
                    foreach ($schedules as $s) {
                        $mappedScheds[$s['day_of_week']][] = $s;
                    }
                    ?>

                    <div class="schedule-grid" style="grid-template-columns: 1fr;"> <!-- stacked horizontally in modal -->
                        <?php foreach($daysOfWeek as $dayIndex => $dayName): 
                            $isActive = !empty($mappedScheds[$dayIndex]);
                            $dayScheds = $isActive ? $mappedScheds[$dayIndex] : [];
                        ?>
                        <div class="day-card" style="margin-bottom: 10px;">
                            <div class="day-title">
                                <input type="checkbox" name="day_<?= $dayIndex ?>_active" value="1" <?= $isActive ? 'checked' : '' ?> onchange="toggleDay(this, <?= $dayIndex ?>)">
                                <?= $dayName ?>
                            </div>
                            <div id="periods_<?= $dayIndex ?>" style="display: <?= $isActive ? 'block' : 'none' ?>;">
                                <?php if($isActive): foreach($dayScheds as $i => $s): ?>
                                    <div class="period-row">
                                        <input type="time" name="day_<?= $dayIndex ?>_periods[<?= $i ?>][start]" value="<?= substr($s['start_time'],0,5) ?>" required>
                                        <span>às</span>
                                        <input type="time" name="day_<?= $dayIndex ?>_periods[<?= $i ?>][end]" value="<?= substr($s['end_time'],0,5) ?>" required>
                                        <input type="number" name="day_<?= $dayIndex ?>_periods[<?= $i ?>][slot]" value="<?= $s['slot_duration'] ?>" style="width:60px" title="Duração (min)">
                                        <button type="button" class="btn-rm-period" onclick="this.parentElement.remove()"><i class="fas fa-times-circle"></i></button>
                                    </div>
                                <?php endforeach; else: ?>
                                    <div class="period-row">
                                        <input type="time" name="day_<?= $dayIndex ?>_periods[0][start]" value="08:00">
                                        <span>às</span>
                                        <input type="time" name="day_<?= $dayIndex ?>_periods[0][end]" value="12:00">
                                        <input type="number" name="day_<?= $dayIndex ?>_periods[0][slot]" value="30" style="width:60px" title="Duração (min)">
                                        <button type="button" class="btn-rm-period" onclick="this.parentElement.remove()"><i class="fas fa-times-circle"></i></button>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn-add-period" onclick="addPeriod(<?= $dayIndex ?>)"><i class="fas fa-plus"></i> Turno</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn-save" style="margin-top: 20px;"><i class="fas fa-save"></i> Salvar Minha Agenda</button>
                </form>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;">
                <a href="<?= BASE_URL ?>/logout" style="display: inline-block; padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; width: 100%; box-sizing: border-box; transition: 0.2s;"><i class="fas fa-sign-out-alt"></i> Sair do Sistema</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSettingsModal() {
            const modal = document.getElementById('settingsModal');
            modal.classList.toggle('active');
        }

        document.getElementById('settingsModal').addEventListener('click', function(e) {
            if(e.target === this) toggleSettingsModal();
        });

        function switchModalTab(tabId) {
            document.querySelectorAll('.modal-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.modal-tab').forEach(el => el.classList.remove('active'));
            
            document.getElementById('mtab-' + tabId).classList.add('active');
            document.getElementById('mtab-btn-' + tabId).classList.add('active');
        }

        function changeStatus(id, selectElement) {
            let newStatus = selectElement.value;
            let card = document.getElementById('card-' + id);
            
            if(!confirm('Deseja marcar a consulta deste paciente como "' + newStatus + '"?')) {
                selectElement.value = 'Confirmado'; // Volta para espera
                return;
            }
            
            selectElement.disabled = true;

            fetch('<?= BASE_URL ?>/api/appointments/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id + '&status=' + encodeURIComponent(newStatus)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    if (newStatus === 'Cancelado' || newStatus === 'Concluído') {
                        card.style.transition = 'opacity 0.5s, transform 0.5s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 500);
                    }
                } else {
                    alert('Erro ao alterar status.');
                    selectElement.disabled = false;
                }
            })
            .catch(err => {
                alert('Erro de rede.');
                selectElement.disabled = false;
            });
        }

        // Funções para grade de horários
        function toggleDay(checkbox, dayIndex) {
            let periods = document.getElementById('periods_' + dayIndex);
            periods.style.display = checkbox.checked ? 'block' : 'none';
        }

        function addPeriod(dayIndex) {
            let container = document.getElementById('periods_' + dayIndex);
            let btn = container.querySelector('.btn-add-period');
            let idx = container.querySelectorAll('.period-row').length;
            
            let row = document.createElement('div');
            row.className = 'period-row';
            row.innerHTML = `
                <input type="time" name="day_${dayIndex}_periods[${idx}][start]" value="13:00" required>
                <span>às</span>
                <input type="time" name="day_${dayIndex}_periods[${idx}][end]" value="18:00" required>
                <input type="number" name="day_${dayIndex}_periods[${idx}][slot]" value="30" style="width:60px" title="Duração (min)">
                <button type="button" class="btn-rm-period" onclick="this.parentElement.remove()"><i class="fas fa-times-circle"></i></button>
            `;
            container.insertBefore(row, btn);
        }
    </script>
</body>
</html>


