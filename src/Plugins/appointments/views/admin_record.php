<?php ob_start(); 
$dateObj = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
$birthObj = new DateTime($appointment['patient_birthdate']);
$age = $birthObj->diff(new DateTime('today'))->y;
?>

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1 style="margin: 0;">Prontuário Médico da Consulta</h1>
        <a href="<?= BASE_URL ?>/admin/appointments" class="page-title-action">&larr; Voltar para a Agenda</a>
    </div>

    <div style="background: #fff; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; display: flex; gap: 20px;">
        <div style="flex: 1;">
            <p style="margin:0 0 5px 0;"><strong>Paciente:</strong> <?= htmlspecialchars($appointment['patient_name']) ?></p>
            <p style="margin:0 0 5px 0;"><strong>CPF:</strong> <?= htmlspecialchars($appointment['patient_cpf']) ?></p>
            <p style="margin:0;"><strong>Idade:</strong> <?= $age ?> anos (<?= $birthObj->format('d/m/Y') ?>)</p>
        </div>
        <div style="flex: 1; border-left: 1px solid #eee; padding-left: 20px;">
            <p style="margin:0 0 5px 0;"><strong>Médico:</strong> <?= htmlspecialchars($appointment['doctor_name']) ?></p>
            <p style="margin:0 0 5px 0;"><strong>Data e Hora:</strong> <?= $dateObj->format('d/m/Y \à\s H:i') ?></p>
            <p style="margin:0;"><strong>Status Atual:</strong> <?= htmlspecialchars($appointment['status']) ?></p>
        </div>
    </div>

    <div style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">
        
        <!-- Formulário do Prontuário -->
        <div class="upload-box" style="flex: 2;">
            <h2 style="margin-top: 0;">Evolução Clínica</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/appointments/record">
                <input type="hidden" name="id" value="<?= $appointment['id'] ?>">

                <div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border: 1px solid #c3c4c7; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; font-size: 14px;">Atualização Cadastral do Paciente (Opcional)</h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 150px;">
                            <label style="display:block; font-size: 12px; margin-bottom: 2px;">CPF</label>
                            <input type="text" name="patient_cpf" value="<?= htmlspecialchars($appointment['patient_cpf']) ?>" style="width: 100%; padding: 6px; box-sizing: border-box; font-size: 13px;">
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label style="display:block; font-size: 12px; margin-bottom: 2px;">Data Nascimento</label>
                            <input type="date" name="patient_birthdate" value="<?= htmlspecialchars($appointment['patient_birthdate'] ?? '') ?>" style="width: 100%; padding: 6px; box-sizing: border-box; font-size: 13px;">
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label style="display:block; font-size: 12px; margin-bottom: 2px;">Nº do Plano (Carteirinha)</label>
                            <input type="text" name="insurance_number" value="<?= htmlspecialchars($patient_data['insurance_number'] ?? '') ?>" style="width: 100%; padding: 6px; box-sizing: border-box; font-size: 13px;" placeholder="Ex: 123456789">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                        <div style="flex: 1; min-width: 100px;">
                            <label style="display:block; font-size: 12px; margin-bottom: 2px;">CEP</label>
                            <input type="text" name="zip_code" value="<?= htmlspecialchars($patient_data['zip_code'] ?? '') ?>" style="width: 100%; padding: 6px; box-sizing: border-box; font-size: 13px;">
                        </div>
                        <div style="flex: 2; min-width: 200px;">
                            <label style="display:block; font-size: 12px; margin-bottom: 2px;">Endereço Completo</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($patient_data['address'] ?? '') ?>" style="width: 100%; padding: 6px; box-sizing: border-box; font-size: 13px;" placeholder="Rua, Número, Bairro, Cidade - Estado">
                        </div>
                    </div>
                </div>

                <h3 style="margin-top: 0;">Evolução Clínica</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                    Preencha os sintomas, exames físicos, diagnósticos e prescrições do paciente. Ao salvar, a consulta será marcada automaticamente como <strong>Atendida</strong>.
                </p>

                <textarea name="medical_record" rows="15" style="width: 100%; padding: 12px; box-sizing: border-box; font-family: monospace; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Digite aqui o prontuário do paciente..."><?= htmlspecialchars($appointment['medical_record'] ?? '') ?></textarea>

                <div style="text-align: right; margin-top: 15px;">
                    <button type="submit" class="btn btn-activate" style="font-size: 16px; padding: 8px 20px;">Salvar Prontuário e Finalizar Atendimento</button>
                </div>
            </form>
        </div>

        <!-- Histórico Lateral -->
        <div class="upload-box" style="flex: 1; background: #f9f9f9;">
            <h2 style="margin-top: 0;">Histórico do Paciente</h2>
            
            <?php if (empty($history)): ?>
                <p style="color: #777; font-size: 13px;">Nenhuma consulta anterior registrada para este paciente.</p>
            <?php else: ?>
                <div style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($history as $h): ?>
                        <?php 
                            $hDate = new DateTime($h['appointment_date'] . ' ' . $h['appointment_time']);
                        ?>
                        <div style="border-bottom: 1px solid #e2e4e7; padding-bottom: 10px; margin-bottom: 10px;">
                            <strong style="color: #2271b1;"><?= $hDate->format('d/m/Y \à\s H:i') ?></strong><br>
                            <small>Médico: <?= htmlspecialchars($h['doctor_name']) ?></small><br>
                            <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 3px;"><?= htmlspecialchars($h['status']) ?></span>
                            
                            <?php if (!empty($h['medical_record'])): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #444; background: #fff; padding: 8px; border: 1px solid #ddd; border-left: 3px solid #2271b1;">
                                    <?= nl2br(htmlspecialchars($h['medical_record'])) ?>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 12px; color: #999; font-style: italic;">Sem anotações no prontuário.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
