<div class="upload-box form-panel ds-shortcode-booking" style="width: 100%; max-width: 800px; margin-bottom: 20px;">
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
                <option value="<?= $d['id'] ?>" <?= (isset($preSelectedDoctorId) && $preSelectedDoctorId == $d['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?>
                </option>
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
                <select name="attendance_type" class="attendance_type" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    <option value="particular">Particular</option>
                    <option value="conveniado">Conveniado</option>
                </select>
            </div>
            <div style="flex:1; display:none;" class="health_insurance_group">
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

        <button type="submit" class="btn btn-activate" style="width: 100%; text-align: center; background: #3b82f6; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;">Confirmar Agendamento</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ds-shortcode-booking').forEach(function(container) {
            var attType = container.querySelector('.attendance_type');
            var healthInsGroup = container.querySelector('.health_insurance_group');
            
            if (attType && healthInsGroup) {
                function toggleInsurance() {
                    if (attType.value === 'conveniado') {
                        healthInsGroup.style.display = 'block';
                    } else {
                        healthInsGroup.style.display = 'none';
                    }
                }
                
                attType.addEventListener('change', toggleInsurance);
                toggleInsurance();
            }
        });
    });
</script>
