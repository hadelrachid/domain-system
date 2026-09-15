<div class="form-panel" style="background: var(--bg-card, #fff); padding: 20px; border: 1px solid var(--primary-border, #c3c4c7); border-radius: 4px; color: var(--text-main, inherit);">
    <h2 style="margin-top: 0; font-size: 16px;">Novo Agendamento</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/appointments">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <!-- Paciente -->
        <div style="margin-bottom: 10px;">
            <label style="display:block; margin-bottom: 5px;">Selecione o Paciente *</label>
            <select name="patient_id" required style="width: 100%; padding: 6px; background: var(--bg-body); color: var(--text-main); border: 1px solid var(--primary-border);">
                <option value="">Selecione...</option>
                <?php foreach($patients as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['phone'] ?? 'Sem telefone') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Médico -->
        <div style="margin-bottom: 10px;">
            <label style="display:block; margin-bottom: 5px;">Médico *</label>
            <select name="doctor_id" id="admin_doctor_id" required style="width: 100%; padding: 6px; background: var(--bg-body); color: var(--text-main); border: 1px solid var(--primary-border);">
                <option value="">Selecione...</option>
                <?php foreach($doctors as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="admin-doctor-schedule-hint" style="font-size:12px; color:var(--primary, #2271b1); margin-top:5px; font-weight:600;"></div>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <!-- Data -->
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px;">Data *</label>
                <!-- Flatpickr via CDN local para o form -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
                <input type="text" name="appointment_date" id="admin_date" placeholder="Selecione no calendário" required style="width: 100%; padding: 6px; box-sizing: border-box; background: var(--bg-body); color: var(--text-main); border: 1px solid var(--primary-border); color-scheme: dark light;">
            </div>
        </div>

        <!-- Horário (Slots) -->
        <div style="margin-bottom: 10px;">
            <label style="display:block; margin-bottom: 5px;">Horário *</label>
            <div id="adminSlotsContainer" style="min-height: 50px; background: var(--bg-body, #f8fafc); border: 1px solid var(--primary-border, #e2e8f0); border-radius: 4px; padding: 15px;">
                <div style="color: var(--text-muted, #64748b); font-size: 13px;"><i class="fas fa-info-circle"></i> Selecione um médico e uma data para ver os horários.</div>
            </div>
            <input type="hidden" name="appointment_time" id="admin_time" required>
        </div>
        
        <!-- Tipo de Atendimento -->
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px;">Tipo de Atendimento</label>
                <select name="attendance_type" id="attendance_type" style="width: 100%; padding: 6px;" onchange="document.getElementById('insurance_box').style.display = (this.value === 'convenio') ? 'block' : 'none';">
                    <option value="particular">Particular</option>
                    <option value="convenio">Convênio</option>
                </select>
            </div>
            <div style="flex: 1; display: none;" id="insurance_box">
                <label style="display:block; margin-bottom: 5px;">Qual Convênio?</label>
                <select name="health_insurance" style="width: 100%; padding: 6px;">
                    <option value="">Nenhum / Não Listado</option>
                    <?php if(isset($insurances) && is_array($insurances)): ?>
                        <?php foreach($insurances as $ins): ?>
                            <option value="<?= htmlspecialchars($ins['name']) ?>"><?= htmlspecialchars($ins['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Observações -->
        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px;">Observações da Recepção</label>
            <textarea name="reception_notes" style="width: 100%; padding: 6px; box-sizing: border-box;" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-activate">Marcar Consulta</button>
    </form>
</div>

    <!-- Injeção da Classe Abstrata do Calendário -->
    <script src="<?= BASE_URL ?>/assets/js/doctor-calendar.js"></script>
    <script>
    function loadAdminSlots() {
        let docId = document.getElementById('admin_doctor_id').value;
        let date = document.getElementById('admin_date').value;
        let slotsContainer = document.getElementById('adminSlotsContainer');
        let timeInput = document.getElementById('admin_time');

        timeInput.value = '';

        if(!docId || !date) {
            slotsContainer.innerHTML = '<div style="color: #64748b; font-size: 13px;"><i class="fas fa-info-circle"></i> Selecione um médico e uma data para ver os horários.</div>';
            return;
        }

        slotsContainer.innerHTML = '<div style="color: #64748b; font-size: 13px;"><i class="fas fa-spinner fa-spin"></i> Carregando horários...</div>';

        fetch('<?= BASE_URL ?>/api/agendamento/slots?doctor_id=' + docId + '&date=' + date, { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            if(data.slots && data.slots.length > 0) {
                let html = '<div class="slots-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;">';
                data.slots.forEach(slot => {
                    html += '<button type="button" class="slot-btn" data-time="'+slot+'" style="padding: 10px; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer; transition: 0.2s;">'+slot+'</button>';
                });
                html += '</div>';
                slotsContainer.innerHTML = html;

                document.querySelectorAll('.slot-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.slot-btn').forEach(b => {
                            b.style.background = '#fff';
                            b.style.color = '#333';
                            b.style.borderColor = '#cbd5e1';
                        });
                        this.style.background = '#2271b1';
                        this.style.color = '#fff';
                        this.style.borderColor = '#135e96';
                        timeInput.value = this.dataset.time;
                    });
                });
            } else {
                slotsContainer.innerHTML = '<div style="color: #e3342f; font-size: 13px;"><i class="fas fa-calendar-times"></i> ' + (data.message || 'Sem horários') + '</div>';
            }
        })
        .catch(e => {
            slotsContainer.innerHTML = '<div style="color: #e3342f; font-size: 13px;"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar</div>';
        });
    }

    function loadAdminDoctorSchedule() {
        let docId = document.getElementById('admin_doctor_id').value;
        let hint = document.getElementById('admin-doctor-schedule-hint');
        
        if(!docId) {
            hint.innerText = '';
            return;
        }

        hint.innerHTML = 'Buscando agenda...';

        fetch('<?= BASE_URL ?>/api/doctors/schedules?doctor_id=' + docId, { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            if (data.schedules && data.schedules.length > 0) {
                let diasMap = {0:'Dom',1:'Seg',2:'Ter',3:'Qua',4:'Qui',5:'Sex',6:'Sáb'};
                let diasTrabalho = [...new Set(data.schedules.map(s => diasMap[s.day_of_week]))];
                hint.innerText = 'Dias de atendimento: ' + diasTrabalho.join(', ');
                
                let dateInput = document.getElementById('admin_date');
                if(!dateInput.value) {
                    let today = new Date();
                    for(let i=0; i<=7; i++) {
                        let checkDate = new Date(today);
                        checkDate.setDate(today.getDate() + i);
                        let dow = checkDate.getDay();
                        if (data.schedules.find(s => s.day_of_week == dow)) {
                            // O calendário cuidará de popular o input formatado, ou podemos deixar como está
                            // Apenas chamamos loadAdminSlots no change callback do calendário
                            break;
                        }
                    }
                }
            } else {
                hint.innerText = '⚠️ O médico não possui horários cadastrados.';
            }
        })
        .catch(e => {
            hint.innerText = '';
        });
    }

    const adminDoctorDays = <?= json_encode($doctorDays ?? []) ?>;

    // Instancia a classe abstrata!
    new DoctorCalendarPicker('#admin_date', '#admin_doctor_id', adminDoctorDays, function(selectedDates, dateStr, instance, isReset) {
        if (isReset) {
            document.getElementById('adminSlotsContainer').innerHTML = '<div style="color: #64748b; font-size: 13px;"><i class="fas fa-info-circle"></i> Selecione um médico e uma data para ver os horários.</div>';
            loadAdminDoctorSchedule();
        } else {
            loadAdminSlots();
        }
    });
</script>
