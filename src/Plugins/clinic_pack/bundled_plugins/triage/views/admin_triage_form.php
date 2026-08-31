<div class="wrap" style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 20px;">🩺 Realizar Triagem</h1>
    
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?= htmlspecialchars($appointment['patient_name']) ?></h3>
        <p style="margin: 0; font-size: 13px; color: #555;">
            <strong>Médico:</strong> <?= htmlspecialchars($appointment['doctor_name']) ?><br>
            <strong>Nascimento:</strong> <?= date('d/m/Y', strtotime($appointment['birthdate'])) ?> (<?= date_diff(date_create($appointment['birthdate']), date_create('today'))->y ?> anos)<br>
            <strong>Motivo:</strong> <?= htmlspecialchars($appointment['reception_notes'] ?: 'Não informado') ?>
        </p>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/triage/save/<?= $appointment['id'] ?>" class="upload-box" style="background: #fff;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Peso (kg)</label>
                <input type="number" step="0.1" name="weight" value="<?= htmlspecialchars($triage['weight'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 75.5">
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Altura (cm)</label>
                <input type="number" step="0.1" name="height" value="<?= htmlspecialchars($triage['height'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 175">
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Pressão Arterial (mmHg)</label>
                <input type="text" name="blood_pressure" value="<?= htmlspecialchars($triage['blood_pressure'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 120/80">
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Temperatura (°C)</label>
                <input type="number" step="0.1" name="temperature" value="<?= htmlspecialchars($triage['temperature'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 36.5">
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Freq. Cardíaca (bpm)</label>
                <input type="number" name="heart_rate" value="<?= htmlspecialchars($triage['heart_rate'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 80">
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Saturação (SpO2 %)</label>
                <input type="number" name="sp02" value="<?= htmlspecialchars($triage['sp02'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Ex: 98">
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display:block; font-weight:bold; margin-bottom:5px;">Anotações da Enfermagem</label>
            <textarea name="notes" rows="4" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Alergias, queixa principal detalhada..."><?= htmlspecialchars($triage['notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap: 10px;">
            <a href="<?= BASE_URL ?>/admin/triage" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-activate">Salvar Triagem e Enviar ao Médico</button>
        </div>
    </form>
</div>


