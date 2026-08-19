<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Usuários e Permissões</h1>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div style="padding: 10px; margin-bottom: 20px; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'success' ? '#4caf50' : '#f44336' ?>; background: #fff;">
        <?= htmlspecialchars($_SESSION['flash_message']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h2 style="margin-top: 0; font-size: 16px;">Adicionar Novo Usuário</h2>
        <form method="POST" action="<?= BASE_URL ?>/admin/users">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nome Completo</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">E-mail</label>
                <input type="email" name="email" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Senha</label>
                <input type="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Perfil de Acesso</label>
                <select name="role" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" onchange="document.getElementById('doctor_select').style.display = (this.value === 'doctor') ? 'block' : 'none';">
                    <option value="receptionist">Recepcionista</option>
                    <option value="doctor">Médico</option>
                    <option value="admin">Administrador Geral</option>
                </select>
            </div>
            <div id="doctor_select" style="margin-bottom: 15px; display: none; background: #f0f6fc; padding: 10px; border: 1px solid #b6d4fe; border-radius: 4px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Vincular a qual Médico?</label>
                <select name="linked_doctor_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">-- Selecione o Médico --</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (CRM: <?= htmlspecialchars($d['crm']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;">Se este usuário é um médico, vincule-o ao cadastro dele para que ele possa ver sua própria agenda.</small>
            </div>
            
            <button type="submit" class="btn btn-activate">Salvar Usuário</button>
        </form>
    </div>

    <div style="flex: 2; min-width: 400px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h2 style="margin-top: 0; font-size: 16px;">Usuários Cadastrados</h2>
        <table class="wp-list-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 10px; border-bottom: 1px solid #ccc; text-align: left;">Nome / E-mail</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ccc; text-align: left;">Perfil</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ccc; text-align: left;">Segurança (2FA)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                            <small><?= htmlspecialchars($u['email']) ?></small>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <?php 
                            if ($u['role'] === 'admin') echo '👑 Administrador';
                            elseif ($u['role'] === 'doctor') echo '🩺 Médico';
                            else echo '📞 Recepcionista';
                            ?>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <form method="POST" action="<?= BASE_URL ?>/admin/users/2fa-type" style="margin-bottom: 5px;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="two_factor_type" onchange="this.form.submit()" style="font-size: 11px; padding: 2px;">
                                    <option value="none" <?= ($u['two_factor_type'] ?? 'none') === 'none' ? 'selected' : '' ?>>Desativado</option>
                                    <option value="app" <?= ($u['two_factor_type'] ?? 'none') === 'app' ? 'selected' : '' ?>>App (Google Auth)</option>
                                    <option value="email" <?= ($u['two_factor_type'] ?? 'none') === 'email' ? 'selected' : '' ?>>E-mail (Código)</option>
                                </select>
                            </form>
                            
                            <?php if (($u['two_factor_type'] ?? 'none') === 'app'): ?>
                                <?php if (!empty($u['two_factor_secret'])): ?>
                                    <span style="color: green; font-size: 11px;">✅ App Sincronizado</span><br>
                                    <a href="<?= BASE_URL ?>/admin/users/2fa-disable?id=<?= $u['id'] ?>" onclick="return confirm('Remover sincronização?')" style="color: red; font-size: 11px; text-decoration: none;">Refazer QR Code</a>
                                <?php else: ?>
                                    <span style="color: #d63638; font-size: 11px;">⚠️ Pendente</span><br>
                                    <a href="<?= BASE_URL ?>/admin/users/2fa?id=<?= $u['id'] ?>" class="btn" style="font-size: 10px; padding: 2px 6px;">Configurar QR Code</a>
                                <?php endif; ?>
                            <?php elseif (($u['two_factor_type'] ?? 'none') === 'email'): ?>
                                <span style="color: green; font-size: 11px;">✅ Envio por E-mail</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
