<div class="wrap">
    <h1 style="display:flex; justify-content:space-between; align-items:center;">
        <span>🩺 Fila de Triagem (Enfermagem)</span>
    </h1>

    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="wp-list-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th style="width: 150px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px;">Nenhum paciente aguardando triagem no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($a['appointment_date'])) ?></strong><br>
                                <small><?= htmlspecialchars($a['appointment_time']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($a['patient_name']) ?></strong><br>
                                <small style="color:#666;">📝 <?= htmlspecialchars($a['reception_notes'] ?: 'Sem notas da recepção') ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/triage/form/<?= $a['id'] ?>" class="btn btn-activate" style="text-decoration:none; text-align:center;">Realizar Triagem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

