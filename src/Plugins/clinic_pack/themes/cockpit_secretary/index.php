<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CockPIT Secretária</title>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; background: #f0f2f5; margin: 0; padding: 0; color: #1d2327; }
        
        /* Header Sidebar / Navbar simulado */
        .header { background: #fff; padding: 15px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .header h1 { margin: 0; color: #2271b1; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .user-profile { display: flex; align-items: center; gap: 15px; cursor: pointer; }
        .user-profile img { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #2271b1; }
        .user-profile .name { font-weight: 600; font-size: 15px; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Abas */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab { padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; border-bottom: 3px solid transparent; }
        .tab.active { color: #2271b1; border-bottom-color: #2271b1; }
        .tab:hover { color: #2271b1; }
        
        /* Conteúdo das Abas */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Cards de Agendamento */
        .appointment-card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; border-left: 4px solid #f59e0b; }
        .appointment-card.status-confirmado { border-left-color: #10b981; }
        
        .info-group { display: flex; flex-direction: column; gap: 5px; }
        .info-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .info-value { font-size: 15px; font-weight: 500; }
        
        /* Botões de Ação */
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        .btn-wa { background: #25D366; color: white; }
        .btn-wa:hover { background: #128C7E; }
        .btn-tg { background: #0088cc; color: white; }
        .btn-tg:hover { background: #0077b5; }
        .btn-email { background: #64748b; color: white; }
        .btn-email:hover { background: #475569; }
        .btn-confirm { background: #10b981; color: white; border: 1px solid #059669; }
        .btn-confirm:hover { background: #059669; }
        
        /* Status Select */
        .status-select { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid transparent; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; padding-right: 25px; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto; }
        .status-select.Pendente { background-color: #fef3c7; color: #d97706; border-color: #fcd34d; }
        .status-select.Confirmado { background-color: #d1fae5; color: #059669; border-color: #6ee7b7; }
        .status-select:focus { box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2); }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; }
        .modal-close { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        
        /* Formulários */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #dcdcdc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .btn-save { background: #2271b1; color: white; padding: 10px 20px; width: 100%; font-size: 16px; margin-top: 10px; cursor: pointer; border: none; border-radius: 4px; font-weight: bold; }
        .btn-save:hover { background: #135e96; }
    </style>
</head>
<body>
    
    <div class="header">
        <h1><i class="fas fa-headset"></i> CockPIT Secretária</h1>
        <div class="user-profile" onclick="toggleSettingsModal()" title="Configurações do Perfil">
            <div class="name"><?= htmlspecialchars($user_name) ?></div>
            <img src="<?= !empty($profile_image) ? BASE_URL . htmlspecialchars($profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=f0f6fc&color=2271b1&size=150' ?>" alt="Perfil">
            <i class="fas fa-cog" style="color: #64748b;"></i>
        </div>
    </div>

    <div class="container">
        
        <?php if(isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #059669; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #059669;">
                Operação realizada com sucesso!
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('agenda')" id="tab-btn-agenda"><i class="fas fa-inbox"></i> Fila da Internet</div>
            <div class="tab" onclick="switchTab('manual')" id="tab-btn-manual"><i class="fas fa-plus-circle"></i> Agendamento Manual (WhatsApp)</div>
        </div>

        <!-- ABA DE AGENDAMENTOS ONLINE -->
        <div id="tab-agenda" class="tab-content active">
            <?php if(empty($appointments)): ?>
                <div style="text-align: center; padding: 50px; color: #64748b;">
                    <i class="fas fa-calendar-check" style="font-size: 40px; margin-bottom: 15px; color: #cbd5e1;"></i>
                    <h2>Nenhum agendamento na fila!</h2>
                </div>
            <?php else: ?>
                <?php foreach($appointments as $app): 
                    $cleanPhone = preg_replace('/[^0-9]/', '', $app['patient_phone']);
                    if (strlen($cleanPhone) == 11 && substr($cleanPhone, 0, 2) != '55') {
                        $cleanPhone = '55' . $cleanPhone;
                    }
                    $isConfirmed = (strtolower($app['status']) === 'confirmado');
                    $cardClass = $isConfirmed ? 'status-confirmado' : '';
                ?>
                <div class="appointment-card <?= $cardClass ?>" id="card-<?= $app['id'] ?>">
                    
                    <div class="info-group" style="min-width: 150px;">
                        <span class="info-label">Paciente</span>
                        <span class="info-value"><i class="fas fa-user" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($app['patient_name'] ?: 'Não informado') ?></span>
                    </div>
                    
                    <div class="info-group" style="min-width: 120px;">
                        <span class="info-label">Data & Hora</span>
                        <span class="info-value"><i class="fas fa-clock" style="color:#94a3b8; margin-right:5px;"></i> <?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= $app['appointment_time'] ?></span>
                    </div>

                    <div class="info-group" style="min-width: 150px;">
                        <span class="info-label">Profissional</span>
                        <span class="info-value"><i class="fas fa-user-md" style="color:#2271b1; margin-right:5px;"></i> <?= htmlspecialchars($app['doctor_name'] ?: 'Não definido') ?></span>
                    </div>

                    <div class="info-group" style="min-width: 120px;">
                        <span class="info-label">Tipo</span>
                        <span class="info-value" style="text-transform: capitalize;"><?= htmlspecialchars($app['attendance_type'] ?? 'Particular') ?></span>
                        <?php if(!empty($app['health_insurance'])): ?>
                            <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($app['health_insurance']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="info-group" style="min-width: 130px;">
                        <span class="info-label">Status</span>
                        <select class="status-select <?= $isConfirmed ? 'Confirmado' : 'Pendente' ?>" 
                                id="status-<?= $app['id'] ?>" 
                                onchange="changeStatus(<?= $app['id'] ?>, this)"
                                <?= $isConfirmed ? 'disabled title="Apenas o médico pode alterar um agendamento já confirmado."' : '' ?>>
                            <option value="Pendente" <?= !$isConfirmed ? 'selected' : '' ?>>Pendente</option>
                            <option value="Confirmado" <?= $isConfirmed ? 'selected' : '' ?>>Confirmado</option>
                            <option value="Cancelado">Cancelado</option>
                            <option value="Concluído">Concluído</option>
                        </select>
                    </div>

                    <div class="actions">
                        <?php if(!empty($cleanPhone)): ?>
                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-wa"><i class="fab fa-whatsapp"></i></a>
                            <a href="https://t.me/+<?= $cleanPhone ?>" target="_blank" class="btn btn-tg"><i class="fab fa-telegram"></i></a>
                        <?php endif; ?>
                        
                        <?php if(!empty($app['patient_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($app['patient_email']) ?>" class="btn btn-email"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ABA DE AGENDAMENTO MANUAL -->
        <div id="tab-manual" class="tab-content">
            <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; border-top: 4px solid #2271b1;">
                <h2 style="margin-top: 0; color: #1d2327; margin-bottom: 20px;"><i class="fas fa-user-plus" style="color: #2271b1;"></i> Novo Agendamento (Balcão/WhatsApp)</h2>
                <form id="manual-booking-form">
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Nome do Paciente</label>
                            <input type="text" name="name" id="patient_name" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Telefone (WhatsApp)</label>
                            <input type="text" name="phone" id="patient_phone" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Data</label>
                            <input type="date" name="date" id="date" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Horário</label>
                            <select name="time" id="time" required>
                                <option value="">Selecione o médico e a data...</option>
                            </select>
                            <div id="slots-msg" style="color: #64748b; font-size: 11px; margin-top: 5px;"></div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div class="form-group" style="flex: 2; margin-bottom: 0;">
                            <label>Médico / Profissional</label>
                            <select name="doctor_id" id="doctor_id" required>
                                <option value="">Selecione o profissional...</option>
                                <?php foreach($doctors as $doc): ?>
                                    <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialty']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Tipo de Atendimento</label>
                            <select name="attendance_type" id="attendance_type">
                                <option value="particular">Particular</option>
                                <option value="conveniado">Conveniado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observações da Recepção</label>
                        <textarea name="notes" id="reception_notes" rows="3" placeholder="Informações extras vindas do WhatsApp..."></textarea>
                    </div>
                    
                    <input type="hidden" name="health_insurance" id="health_insurance" value="">
                    
                    <button type="submit" class="btn-save" id="btn-manual-submit"><i class="fas fa-calendar-plus"></i> Agendar Paciente Manualmente</button>
                </form>
            </div>
        </div>

    </div>

    <!-- MODAL DE CONFIGURAÇÕES DE PERFIL -->
    <div class="modal-overlay" id="settingsModal">
        <div class="modal-content">
            <button class="modal-close" onclick="toggleSettingsModal()"><i class="fas fa-times"></i></button>
            <h2 style="margin-top: 0; color: #2271b1; margin-bottom: 25px;"><i class="fas fa-user-cog"></i> Configurações do Perfil</h2>
            
            <form action="<?= BASE_URL ?>/cockpit/profile" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                    <img src="<?= !empty($profile_image) ? BASE_URL . htmlspecialchars($profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=f0f6fc&color=2271b1&size=200' ?>" alt="Perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; margin-bottom: 15px;">
                    <br>
                    <input type="file" name="photo" accept="image/*" style="border:none; background: transparent; padding: 0;">
                </div>
                
                <div class="form-group">
                    <label>E-mail (Usado no 2FA)</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user_email) ?>" required>
                </div>

                <div class="form-group">
                    <label>Nova Senha</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="sec_password" placeholder="Deixe em branco para manter a atual" style="padding-right: 40px;">
                        <i class="fas fa-eye" id="togglePasswordSec" style="position: absolute; right: 15px; top: 12px; cursor: pointer; color: #64748b;" onclick="
                            const pwd = document.getElementById('sec_password');
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

                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Salvar Alterações</button>
            </form>
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

        // Fechar modal ao clicar fora
        document.getElementById('settingsModal').addEventListener('click', function(e) {
            if(e.target === this) toggleSettingsModal();
        });

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabId).classList.add('active');
            document.getElementById('tab-btn-' + tabId).classList.add('active');
        }

        function changeStatus(id, selectElement) {
            let newStatus = selectElement.value;
            let card = document.getElementById('card-' + id);
            
            if(!confirm('Deseja alterar o status para "' + newStatus + '"?')) {
                // Reverte seleção caso cancele a confirmação
                if(card.classList.contains('status-confirmado')) selectElement.value = 'Confirmado';
                else selectElement.value = 'Pendente';
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
                        // Fade out e remove da tela (pois não pertencem mais à secretária)
                        card.style.transition = 'opacity 0.5s, transform 0.5s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 500);
                    } else if (newStatus === 'Confirmado') {
                        card.classList.add('status-confirmado');
                        selectElement.className = 'status-select Confirmado';
                        // Trava o dropdown, pois a secretária não tem mais permissão após confirmar
                        selectElement.title = "Apenas o médico pode alterar um agendamento já confirmado.";
                    } else if (newStatus === 'Pendente') {
                        card.classList.remove('status-confirmado');
                        selectElement.className = 'status-select Pendente';
                        selectElement.disabled = false;
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

        // Máscara simples de telefone (WhatsApp)
        const phoneInput = document.getElementById('patient_phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                let v = this.value.replace(/\D/g, '');
                if (v.length > 11) v = v.substring(0, 11);
                if (v.length > 6) {
                    this.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 7) + '-' + v.substring(7);
                } else if (v.length > 2) {
                    this.value = '(' + v.substring(0, 2) + ') ' + v.substring(2);
                } else if (v.length > 0) {
                    this.value = '(' + v;
                }
            });
        }

        function loadSlots() {
            let docId = document.getElementById('doctor_id').value;
            let date = document.getElementById('date').value;
            let timeSelect = document.getElementById('time');
            let msg = document.getElementById('slots-msg');

            if(!docId || !date) {
                timeSelect.innerHTML = '<option value="">Selecione o médico e a data...</option>';
                msg.innerText = '';
                return;
            }

            timeSelect.innerHTML = '<option value="">Carregando...</option>';
            msg.innerText = '';

            fetch('<?= BASE_URL ?>/api/agendamento/slots?doctor_id=' + docId + '&date=' + date)
            .then(r => r.json())
            .then(data => {
                if(data.slots && data.slots.length > 0) {
                    let html = '<option value="">Selecione o horário...</option>';
                    data.slots.forEach(slot => {
                        html += '<option value="'+slot+'">'+slot+'</option>';
                    });
                    timeSelect.innerHTML = html;
                    msg.innerText = data.message;
                } else {
                    timeSelect.innerHTML = '<option value="">Indisponível</option>';
                    msg.innerText = data.message || 'Sem horários';
                }
            })
            .catch(e => {
                timeSelect.innerHTML = '<option value="">Erro</option>';
            });
        }

        document.getElementById('date').addEventListener('change', loadSlots);
        document.getElementById('doctor_id').addEventListener('change', loadSlots);

        document.getElementById('manual-booking-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            let btn = document.getElementById('btn-manual-submit');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';
            btn.disabled = true;

            let formData = new FormData(this);
            
            fetch('<?= BASE_URL ?>/api/agendamento/submit', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Paciente agendado com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Desconhecido'));
                    btn.innerHTML = '<i class="fas fa-calendar-plus"></i> Agendar Paciente Manualmente';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Erro de rede ao agendar.');
                btn.innerHTML = '<i class="fas fa-calendar-plus"></i> Agendar Paciente Manualmente';
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>


