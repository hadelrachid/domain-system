<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Usuários e Permissões</h1>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div style="padding: 10px; margin-bottom: 20px; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'success' ? '#4caf50' : '#f44336' ?>; background: #fff;">
        <?= htmlspecialchars($_SESSION['flash_message']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h2 style="margin-top: 0; font-size: 16px;">Adicionar Novo Usuário</h2>
        <form method="POST" action="<?= BASE_URL ?>/admin/users">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nome Completo</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">E-mail</label>
                <input type="email" name="email" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;" class="settings-form-group">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Senha</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="new_user_pwd" required style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 6px; padding-right: 35px; box-sizing: border-box; outline: none;" oninput="analyzePasswordStrength(this.value, 'admin_new')">
                    <button type="button" onclick="togglePasswordVisibility('new_user_pwd', this)" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: transparent; border: none; color: #666; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                
                <div style="margin-top: 8px;">
                    <button type="button" onclick="generatePasswordAndAnalyze('new_user_pwd', 'admin_new')" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:5px 12px; border-radius:6px; font-size:11px; cursor:pointer; color:#3b82f6; font-weight:700; display:inline-flex; align-items:center; gap:6px; transition:0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'">
                        <i class="fas fa-magic"></i> Gerar Senha Segura
                    </button>
                </div>
                
                <div id="pwd-meter-admin_new" style="display:none; margin-top:8px;">
                     <div style="height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                          <div id="pwd-bar-admin_new" style="height:100%; width:0%; background:#ef4444; transition: width 0.3s, background 0.3s;"></div>
                     </div>
                     <div style="display:flex; justify-content:space-between; margin-top:4px;">
                         <div id="pwd-hint-admin_new" style="font-size:11px; color:#64748b;">Inclua letras, números e símbolos</div>
                         <div id="pwd-text-admin_new" style="font-size:11px; font-weight:600; text-align:right;">Péssimo</div>
                     </div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Perfil de Acesso</label>
                <select name="role" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" onchange="document.getElementById('doctor_select').style.display = (this.value === 'doctor') ? 'block' : 'none';">
                    <option value="patient">Paciente / Comum</option>
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
                            if ($u['role'] === 'admin') echo '🛡️ Administrador';
                            elseif ($u['role'] === 'doctor') echo '⚕️ Médico';
                            elseif ($u['role'] === 'receptionist') echo '👩‍💼 Recepcionista';
                            else echo '👤 Paciente / Comum';
                            ?>
                            
                            <!-- Redefinir Senha e Excluir -->
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display: flex; flex-direction: column; gap: 8px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="font-size:12px; color:#475569;">Mudar Senha</strong>
                                </div>
                                <form method="POST" action="<?= BASE_URL ?>/admin/users/reset-password" onsubmit="return confirm('Tem certeza que deseja mudar a senha deste usuário?')" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;" class="settings-form-group">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <div style="position: relative; flex: 1; min-width: 120px; width:100%;">
                                        <div style="position:relative;">
                                            <input type="password" name="new_password" id="reset_pwd_<?= $u['id'] ?>" placeholder="Nova senha" required style="width: 100%; font-size: 13px; padding: 6px 8px; padding-right: 32px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" oninput="analyzePasswordStrength(this.value, '<?= $u['id'] ?>')">
                                            <button type="button" onclick="togglePasswordVisibility('reset_pwd_<?= $u['id'] ?>', this)" style="position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: transparent; border: none; color: #64748b; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </button>
                                        </div>
                                        
                                        <div style="margin-top: 8px;">
                                            <button type="button" onclick="generatePasswordAndAnalyze('reset_pwd_<?= $u['id'] ?>', '<?= $u['id'] ?>')" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:5px 12px; border-radius:6px; font-size:11px; cursor:pointer; color:#3b82f6; font-weight:700; display:inline-flex; align-items:center; gap:6px; transition:0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'">
                                                <i class="fas fa-magic"></i> Gerar Senha Segura
                                            </button>
                                        </div>
                                        
                                        <div id="pwd-meter-<?= $u['id'] ?>" style="display:none; margin-top:8px; width:100%;">
                                             <div style="height:4px; background:#e2e8f0; border-radius:2px; overflow:hidden;">
                                                  <div id="pwd-bar-<?= $u['id'] ?>" style="height:100%; width:0%; background:#ef4444; transition: width 0.3s, background 0.3s;"></div>
                                             </div>
                                             <div style="display:flex; justify-content:space-between; margin-top:2px;">
                                                 <div id="pwd-hint-<?= $u['id'] ?>" style="font-size:9px; color:#64748b;">Inclua letras, números e símbolos</div>
                                                 <div id="pwd-text-<?= $u['id'] ?>" style="font-size:9px; font-weight:600; text-align:right;">Péssimo</div>
                                             </div>
                                        </div>
                                    </div>
                                    <button type="submit" style="background: #0f172a; color: white; border: none; padding: 6px 12px; font-size: 12px; font-weight: 500; border-radius: 6px; cursor: pointer; transition: background 0.2s;">Mudar</button>
                                </form>
                                
                                <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin/users/delete" onsubmit="return confirm('ATENÇÃO: Deseja realmente EXCLUIR o usuário <?= htmlspecialchars($u['name']) ?>? Isso não pode ser desfeito.')" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" style="background: transparent; color: #dc2626; border: 1px solid #dc2626; padding: 4px 10px; font-size: 11px; font-weight: 600; border-radius: 4px; cursor: pointer; width: auto;"><i class="fas fa-trash-alt"></i> Excluir Conta</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
    <form method="POST" action="<?= BASE_URL ?>/admin/users/2fa-type" style="margin-bottom: 8px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <select name="two_factor_type" onchange="this.form.submit()" style="font-size: 13px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; outline: none; background: #f8fafc;">
            <option value="none" <?= ($u['two_factor_type'] ?? 'none') === 'none' ? 'selected' : '' ?>>Desativado</option>
            <option value="app" <?= ($u['two_factor_type'] ?? 'none') === 'app' ? 'selected' : '' ?>>App (Google Auth)</option>
            <option value="email" <?= ($u['two_factor_type'] ?? 'none') === 'email' ? 'selected' : '' ?>>E-mail (Código)</option>
        </select>
    </form>
    
    <?php if (($u['two_factor_type'] ?? 'none') === 'app'): ?>
        <?php if (!empty($u['two_factor_secret'])): ?>
            <span style="color: #16a34a; font-size: 12px; font-weight: 500; display: block; margin-top: 5px;">✅ Sincronizado</span>
            <a href="<?= BASE_URL ?>/admin/users/2fa-disable?id=<?= $u['id'] ?>" onclick="return confirm('Remover sincronização?')" style="color: #dc2626; font-size: 11px; text-decoration: none; display: block; margin-top: 4px;">Refazer QR Code</a>
        <?php else: ?>
            <span style="color: #dc2626; font-size: 12px; font-weight: 500; display: block; margin-top: 5px;">⚠️ Pendente</span>
            <a href="<?= BASE_URL ?>/admin/users/2fa?id=<?= $u['id'] ?>" class="btn" style="background: #dc2626; color: white; display: inline-block; text-align: center; margin-top: 6px; font-size: 11px; padding: 5px 8px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer;">Configurar QR</a>
        <?php endif; ?>
    <?php elseif (($u['two_factor_type'] ?? 'none') === 'email'): ?>
        <span style="color: #16a34a; font-size: 12px; font-weight: 500; display: block; margin-top: 5px;">✅ E-mail Ativado</span>
    <?php endif; ?>
</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    var svg = btn.querySelector('svg');
    if (input.type === 'password') {
        input.type = 'text';
        svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        input.type = 'password';
        svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>


