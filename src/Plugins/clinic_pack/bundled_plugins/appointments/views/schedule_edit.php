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
?>
<div style="max-width: 900px;">
    <h1>📅 Agenda dos Médicos</h1>
    <p style="color: #666; margin-bottom: 20px;">Configure os dias e horários de atendimento de cada médico. Estes dados serão usados para exibir os slots disponíveis no formulário público de agendamento.</p>

    <?php if ($saved): ?>
        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
            ✅ Grade de horários salva com sucesso!
        </div>
    <?php endif; ?>

    <!-- Selecionar Médico -->
    <form method="GET" action="<?= BASE_URL ?>/admin/doctors/schedule" style="margin-bottom: 24px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <label for="doctor_id" style="font-weight: 600;">Selecionar Médico:</label>
        <select name="doctor_id" id="doctor_id" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; min-width: 220px;">
            <option value="">-- Selecione --</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == $selectedDoctorId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doc['name']) ?> <?= $doc['specialty'] ? "({$doc['specialty']})" : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="padding: 8px 18px; background: #1A365D; color: white; border: none; border-radius: 6px; cursor: pointer;">Ver Agenda</button>
    </form>

    <?php if ($selectedDoctorId): ?>
    <!-- Formulário de Horários -->
    <form method="POST" action="<?= BASE_URL ?>/admin/doctors/schedule/save" id="scheduleForm">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($selectedDoctorId) ?>">

        <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
            <?php foreach ($days as $dayNum => $dayName):
                $daySchedules = $scheduleByDay[$dayNum] ?? [];
                $isActive = !empty($daySchedules);
                $isWeekend = ($dayNum === 0 || $dayNum === 6);
            ?>
            <div class="day-row" style="border-bottom: 1px solid #eee; padding: 16px;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <!-- Toggle do dia -->
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; min-width: 170px;">
                        <input type="checkbox" name="day_<?= $dayNum ?>_active" value="1" 
                               <?= $isActive ? 'checked' : '' ?>
                               onchange="toggleDay(this, <?= $dayNum ?>)"
                               style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 600; color: <?= $isWeekend ? '#888' : '#1A365D' ?>;">
                            <?= $dayName ?>
                        </span>
                        <?php if ($isWeekend): ?>
                            <span style="font-size: 0.75rem; color: #aaa;">(fim de semana)</span>
                        <?php endif; ?>
                    </label>

                    <!-- Períodos do dia -->
                    <div id="periods-<?= $dayNum ?>" style="display: <?= $isActive ? 'flex' : 'none' ?>; flex-wrap: wrap; gap: 10px; flex: 1;">
                        <?php if (!empty($daySchedules)): ?>
                            <?php foreach ($daySchedules as $idx => $s): ?>
                                <div class="period-block" style="display: flex; gap: 8px; align-items: center; background: #f9f9f9; padding: 8px 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                    <input type="time" name="day_<?= $dayNum ?>_periods[<?= $idx ?>][start]" 
                                           value="<?= htmlspecialchars($s['start_time']) ?>"
                                           style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                    <span>até</span>
                                    <input type="time" name="day_<?= $dayNum ?>_periods[<?= $idx ?>][end]" 
                                           value="<?= htmlspecialchars($s['end_time']) ?>"
                                           style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                    <select name="day_<?= $dayNum ?>_periods[<?= $idx ?>][slot]" style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                        <option value="15" <?= $s['slot_duration']==15?'selected':'' ?>>15 min</option>
                                        <option value="20" <?= $s['slot_duration']==20?'selected':'' ?>>20 min</option>
                                        <option value="30" <?= $s['slot_duration']==30?'selected':'' ?>>30 min</option>
                                        <option value="45" <?= $s['slot_duration']==45?'selected':'' ?>>45 min</option>
                                        <option value="60" <?= $s['slot_duration']==60?'selected':'' ?>>60 min</option>
                                    </select>
                                    <button type="button" onclick="this.closest('.period-block').remove()" 
                                            style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer;">✕</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="period-block" style="display: flex; gap: 8px; align-items: center; background: #f9f9f9; padding: 8px 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                <input type="time" name="day_<?= $dayNum ?>_periods[0][start]" value="08:00" style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                <span>até</span>
                                <input type="time" name="day_<?= $dayNum ?>_periods[0][end]" value="12:00" style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                <select name="day_<?= $dayNum ?>_periods[0][slot]" style="padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="15">15 min</option>
                                    <option value="20">20 min</option>
                                    <option value="30" selected>30 min</option>
                                    <option value="45">45 min</option>
                                    <option value="60">60 min</option>
                                </select>
                                <button type="button" onclick="this.closest('.period-block').remove()" 
                                        style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer;">✕</button>
                            </div>
                        <?php endif; ?>

                        <button type="button" onclick="addPeriod(<?= $dayNum ?>)"
                                style="padding: 6px 12px; background: #C5A880; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                            + Período
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 12px;">
            <button type="submit" style="padding: 12px 28px; background: #1A365D; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                💾 Salvar Grade de Horários
            </button>
            <a href="/admin/doctors" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-size: 1rem;">
                Voltar
            </a>
        </div>
    </form>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #888; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ddd;">
            <p>👆 Selecione um médico acima para gerenciar sua agenda.</p>
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
    // Contar períodos existentes
    const count = container.querySelectorAll('.period-block').length;
    
    const block = document.createElement('div');
    block.className = 'period-block';
    block.style.cssText = 'display:flex; gap:8px; align-items:center; background:#f9f9f9; padding:8px 10px; border-radius:6px; border:1px solid #e0e0e0;';
    block.innerHTML = `
        <input type="time" name="day_${dayNum}_periods[${count}][start]" value="14:00" style="padding:4px; border:1px solid #ccc; border-radius:4px;">
        <span>até</span>
        <input type="time" name="day_${dayNum}_periods[${count}][end]" value="18:00" style="padding:4px; border:1px solid #ccc; border-radius:4px;">
        <select name="day_${dayNum}_periods[${count}][slot]" style="padding:4px; border:1px solid #ccc; border-radius:4px;">
            <option value="15">15 min</option>
            <option value="20">20 min</option>
            <option value="30" selected>30 min</option>
            <option value="45">45 min</option>
            <option value="60">60 min</option>
        </select>
        <button type="button" onclick="this.closest('.period-block').remove()"
                style="background:#dc3545; color:white; border:none; border-radius:4px; padding:4px 8px; cursor:pointer;">✕</button>
    `;
    
    // Inserir antes do botão "+ Período"
    const addBtn = container.querySelector('button[onclick^="addPeriod"]');
    container.insertBefore(block, addBtn);
}
</script>

