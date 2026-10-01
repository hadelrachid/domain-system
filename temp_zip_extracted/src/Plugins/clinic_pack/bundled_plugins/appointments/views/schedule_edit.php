<?php
/**
 * View: Gerenciar Grade de Horários do Médico
 * Acessível em: /admin/doctors/schedule?doctor_id=X
 * 
 * Variáveis disponíveis:
 *   $doctors         - array de médicos
 *   $selectedDoctorId - ID do médico selecionado
 *   $schedules       - array de horários configurados
 */

$days = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
];

// Agrupar os horários existentes por dia
$scheduleByDay = [];
foreach ($schedules as $s) {
    $scheduleByDay[$s['day_of_week']][] = $s;
}

$saved = $_GET['saved'] ?? false;
$embedded = $_GET['embedded'] ?? false;
?>
<div style="max-width: 900px; <?= $embedded ? 'margin: 0; padding: 0;' : '' ?>">
    
    <?php if (!$embedded): ?>
    <h1 style="color: var(--text-main);">📅 Agenda dos Médicos</h1>
    <p style="color: var(--text-muted); margin-bottom: 25px;">Configure os dias e horários de atendimento de cada médico. Estes dados serão usados para exibir os slots disponíveis no formulário público de agendamento.</p>
    <?php endif; ?>

    <?php if ($saved): ?>
        <div style="background: rgba(0,210,132,0.1); border: 1px solid var(--accent-green); color: var(--accent-green); padding: 12px 16px; border-radius: 6px; margin-bottom: 25px;">
            <i class="fas fa-check-circle"></i> Configuração de horários salva com sucesso!
        </div>
    <?php endif; ?>

    <?php if ($selectedDoctorId && !$embedded): ?>
        <div class="sys-tabs">
            <a href="<?= BASE_URL ?>/admin/doctors/edit?id=<?= $selectedDoctorId ?>" class="sys-tab"><i class="fas fa-user-md"></i> Informações Gerais</a>
            <a href="<?= BASE_URL ?>/admin/doctors/schedule?doctor_id=<?= $selectedDoctorId ?>" class="sys-tab active"><i class="fas fa-calendar-alt"></i> Agenda de Horários</a>
        </div>
    <?php endif; ?>

    <?php if (!$embedded): ?>
    <!-- Selecionar Médico -->
    <form method="GET" action="<?= BASE_URL ?>/admin/doctors/schedule" style="margin-bottom: 25px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <label for="doctor_id" style="font-weight: 600; color: var(--text-main);">Selecionar Médico:</label>
        <select name="doctor_id" id="doctor_id" style="min-width: 250px;">
            <option value="">-- Selecione --</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == $selectedDoctorId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doc['name']) ?> <?= $doc['specialty'] ? "({$doc['specialty']})" : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-activate">Ver Agenda</button>
    </form>
    <?php endif; ?>

    <?php if ($selectedDoctorId): ?>
    <!-- Formulário de Horários -->
    <form id="scheduleForm" method="POST" action="<?= BASE_URL ?>/admin/doctors/schedule/save" class="card" style="padding: 25px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">
        
        <?php if ($embedded): ?>
            <input type="hidden" name="redirect" value="/admin/doctors/schedule?doctor_id=<?= $selectedDoctorId ?>&embedded=1&raw=1">
        <?php endif; ?>

        <div style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: var(--bg-deep);">
            <?php foreach ($days as $dayNum => $dayName):
                $daySchedules = $scheduleByDay[$dayNum] ?? [];
                $isActive = !empty($daySchedules);
                $isWeekend = ($dayNum === 0 || $dayNum === 6);
            ?>
            <div class="day-row" style="border-bottom: 1px solid var(--border); padding: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <!-- Toggle do dia -->
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; min-width: 170px;">
                        <input type="checkbox" name="day_<?= $dayNum ?>_active" value="1" 
                               <?= $isActive ? 'checked' : '' ?>
                               onchange="toggleDay(this, <?= $dayNum ?>)"
                               style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent-blue);">
                        <span style="font-weight: 600; color: <?= $isWeekend ? 'var(--text-muted)' : 'var(--accent-blue)' ?>;">
                            <?= $dayName ?>
                        </span>
                        <?php if ($isWeekend): ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">(Fim de semana)</span>
                        <?php endif; ?>
                    </label>

                    <!-- Períodos do dia -->
                    <div id="periods-<?= $dayNum ?>" style="display: <?= $isActive ? 'flex' : 'none' ?>; flex-wrap: wrap; gap: 10px; flex: 1; align-items: center;">
                        <?php if (!empty($daySchedules)): ?>
                            <?php foreach ($daySchedules as $idx => $s): ?>
                                <div class="period-block" style="display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.02); padding: 10px; border-radius: 6px; border: 1px dashed var(--border);">
                                    <input type="time" name="day_<?= $dayNum ?>_periods[<?= $idx ?>][start]" value="<?= htmlspecialchars($s['start_time']) ?>">
                                    <span style="color: var(--text-muted);">até</span>
                                    <input type="time" name="day_<?= $dayNum ?>_periods[<?= $idx ?>][end]" value="<?= htmlspecialchars($s['end_time']) ?>">
                                    <select name="day_<?= $dayNum ?>_periods[<?= $idx ?>][slot]">
                                        <option value="15" <?= $s['slot_duration']==15?'selected':'' ?>>15 min</option>
                                        <option value="20" <?= $s['slot_duration']==20?'selected':'' ?>>20 min</option>
                                        <option value="30" <?= $s['slot_duration']==30?'selected':'' ?>>30 min</option>
                                        <option value="45" <?= $s['slot_duration']==45?'selected':'' ?>>45 min</option>
                                        <option value="60" <?= $s['slot_duration']==60?'selected':'' ?>>60 min</option>
                                    </select>
                                    <button type="button" class="btn btn-deactivate" onclick="this.closest('.period-block').remove()" style="padding: 6px 12px !important; font-size:11px !important;">✕</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="period-block" style="display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.02); padding: 10px; border-radius: 6px; border: 1px dashed var(--border);">
                                <input type="time" name="day_<?= $dayNum ?>_periods[0][start]" value="08:00">
                                <span style="color: var(--text-muted);">até</span>
                                <input type="time" name="day_<?= $dayNum ?>_periods[0][end]" value="12:00">
                                <select name="day_<?= $dayNum ?>_periods[0][slot]">
                                    <option value="15">15 min</option>
                                    <option value="20">20 min</option>
                                    <option value="30" selected>30 min</option>
                                    <option value="45">45 min</option>
                                    <option value="60">60 min</option>
                                </select>
                                <button type="button" class="btn btn-deactivate" onclick="this.closest('.period-block').remove()" style="padding: 6px 12px !important; font-size:11px !important;">✕</button>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="btn" onclick="addPeriod(<?= $dayNum ?>)" style="padding: 8px 12px !important;">+ Período</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 15px;">
            <button type="submit" class="btn button-primary">💾 Salvar Grade de Horários</button>
            <a href="<?= BASE_URL ?>/admin/doctors" class="btn">Voltar</a>
        </div>
    </form>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 40px; border: 1px dashed var(--accent-blue); background: rgba(88,166,255,0.05);">
            <p style="color: var(--accent-blue); font-size: 16px; font-weight: 600;">👆 Selecione um médico acima para gerenciar sua agenda.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleDay(checkbox, dayNum) {
    const periodsDiv = document.getElementById('periods-' + dayNum);
    periodsDiv.style.display = checkbox.checked ? 'flex' : 'none';
}

function addPeriod(dayNum) {
    const container = document.getElementById('periods-' + dayNum);
    const count = container.querySelectorAll('.period-block').length;
    
    const block = document.createElement('div');
    block.className = 'period-block';
    block.style.cssText = 'display:flex; gap:10px; align-items:center; background:rgba(255,255,255,0.02); padding:10px; border-radius:6px; border:1px dashed var(--border);';
    block.innerHTML = `
        <input type="time" name="day_${dayNum}_periods[${count}][start]" value="14:00">
        <span style="color: var(--text-muted);">até</span>
        <input type="time" name="day_${dayNum}_periods[${count}][end]" value="18:00">
        <select name="day_${dayNum}_periods[${count}][slot]">
            <option value="15">15 min</option>
            <option value="20">20 min</option>
            <option value="30" selected>30 min</option>
            <option value="45">45 min</option>
            <option value="60">60 min</option>
        </select>
        <button type="button" class="btn btn-deactivate" onclick="this.closest('.period-block').remove()" style="padding: 6px 12px !important; font-size:11px !important;">✕</button>
    `;
    
    const addBtn = container.querySelector('button[onclick^="addPeriod"]');
    container.insertBefore(block, addBtn);
}
</script>

