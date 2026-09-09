<div class="form-panel" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
    <h2 style="margin-top: 0; font-size: 16px;">Novo Agendamento</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/appointments">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <!-- Paciente -->
        <div style="margin-bottom: 10px;">
            <label style="display:block; margin-bottom: 5px;">Selecione o Paciente *</label>
            <select name="patient_id" required style="width: 100%; padding: 6px;">
                <option value="">Selecione...</option>
                <?php foreach($patients as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['phone'] ?? 'Sem telefone') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Médico -->
        <div style="margin-bottom: 10px;">
            <label style="display:block; margin-bottom: 5px;">Médico *</label>
            <select name="doctor_id" required style="width: 100%; padding: 6px;">
                <option value="">Selecione...</option>
                <?php foreach($doctors as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <!-- Data -->
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px;">Data *</label>
                <input type="date" name="appointment_date" required style="width: 100%; padding: 6px; box-sizing: border-box;">
            </div>
            <!-- Hora -->
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px;">Horário (HH:MM)</label>
                <input type="time" name="appointment_time" style="width: 100%; padding: 6px; box-sizing: border-box;" value="00:00">
            </div>
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
