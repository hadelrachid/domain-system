<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Histórico de Atendimentos</h1>
    <a href="<?= BASE_URL ?>/admin/appointments" class="page-title-action">&larr; Voltar para a Fila</a>
</div>

<div class="upload-box" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/appointments/history" style="display: flex; gap: 10px;">
        <input type="text" name="s" placeholder="Buscar por Nome, WhatsApp ou Data (YYYY-MM-DD)..." value="<?= htmlspecialchars($_GET['s'] ?? '') ?>" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <button type="submit" class="btn btn-activate">Buscar</button>
        <?php if (!empty($_GET['s'])): ?>
            <a href="<?= BASE_URL ?>/admin/appointments/history" class="btn" style="padding: 10px;">Limpar</a>
        <?php endif; ?>
    </form>
    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Mostrando os últimos 20 registros encontrados.</p>
</div>

<div class="table-responsive">
    <table class="wp-list-table" style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #c3c4c7;">
        <thead>
            <tr>
                <th style="padding: 10px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">Data/Hora</th>
                <th style="padding: 10px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">Paciente</th>
                <th style="padding: 10px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">Médico</th>
                <th style="padding: 10px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">Status</th>
                <th style="padding: 10px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum histórico encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $a): ?>
                    <?php 
                        $statusClass = 'status-' . strtolower($a['status']);
                        $dateObj = new DateTime($a['appointment_date'] . ' ' . $a['appointment_time']);
                        $attendance = $a['attendance_type'] === 'conveniado' ? 'Conveniado (' . ($a['health_insurance'] ?: 'Não Informado') . ')' : 'Particular';
                    ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong><?= $dateObj->format('d/m/Y') ?></strong><br>
                            <small><?= $dateObj->format('H:i') ?></small>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong><?= htmlspecialchars($a['patient_name']) ?></strong><br>
                            <small style="color:#666;">? <?= htmlspecialchars($a['patient_phone']) ?></small><br>
                            <small style="color:#0073aa;"><?= htmlspecialchars($attendance) ?></small>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($a['status']) ?></span>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <a href="<?= BASE_URL ?>/admin/appointments/record?id=<?= $a['id'] ?>" class="btn" style="text-decoration:none; font-size: 11px;">Ver Prontuário</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
