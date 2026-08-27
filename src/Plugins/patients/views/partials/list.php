<div class="table-responsive ds-shortcode-list">
    <table class="wp-list-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #ccc;">
                <th style="text-align: left; padding: 10px;">Nome</th>
                <th style="text-align: left; padding: 10px;">CPF</th>
                <th style="text-align: left; padding: 10px;">Nascimento</th>
                <th style="text-align: left; padding: 10px;">Contato</th>
                <?php if ($showActions ?? true): ?>
                <th style="width: 150px; padding: 10px;">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patients)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum paciente encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($patients as $p): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($p['cpf']) ?></td>
                        <td style="padding: 10px;"><?= !empty($p['birthdate']) ? date('d/m/Y', strtotime($p['birthdate'])) : '-' ?></td>
                        <td style="padding: 10px;">
                            <?= htmlspecialchars($p['email'] ?? '') ?><br>
                            <small style="color: #666;"><?= htmlspecialchars($p['phone'] ?? '') ?></small>
                        </td>
                        <?php if ($showActions ?? true): ?>
                        <td style="padding: 10px;">
                            <div style="display:flex; gap:5px;">
                                <a href="<?= BASE_URL ?>/admin/patients/edit?id=<?= $p['id'] ?>" class="btn btn-activate" style="text-decoration:none; background: #e0e7ff; color: #3730a3; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">Editar</a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
