<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #f8fafc;">Usuários e Permissões</h1>
        <p style="margin: 4px 0 0 0; color: #94a3b8; font-size: 13px;">Gerenciamento de contas, papéis de acesso, segurança e moderação do sistema.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/academy/students" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(56, 189, 248, 0.12); color: #38bdf8; text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid rgba(56, 189, 248, 0.3);">
            <span>🎓</span> Cockpit de Alunos da Academia &rarr;
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div style="padding: 12px 16px; margin-bottom: 20px; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'success' ? '#22c55e' : '#ef4444' ?>; background: var(--bg-panel, #1e293b); color: #f8fafc; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); font-size: 14px;">
        <?= htmlspecialchars($_SESSION['flash_message']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <!-- Box Adicionar Novo Usuário -->
    <div style="background: var(--bg-panel, #1e293b); padding: 20px; border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; color: #f8fafc;">
        <h2 style="margin-top: 0; font-size: 16px; font-weight: 600; color: #f8fafc;">Adicionar Novo Usuário</h2>
        <form method="POST" action="<?= BASE_URL ?>/admin/users">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #e2e8f0;">Nome Completo</label>
                    <input type="text" name="name" required style="width: 100%; padding: 8px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #e2e8f0;">E-mail</label>
                    <input type="email" name="email" required style="width: 100%; padding: 8px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #e2e8f0;">Perfil de Acesso</label>
                    <select name="role" required style="width: 100%; padding: 8px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; outline: none;">
                        <option value="user" style="background: #1e293b; color: #fff;">Usuário Comum (Aluno / Cliente)</option>
                        <option value="subscriber" style="background: #1e293b; color: #fff;">Assinante</option>
                        <option value="manager" style="background: #1e293b; color: #fff;">Gerente Operacional</option>
                        <option value="admin" style="background: #1e293b; color: #fff;">Administrador Geral</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px; max-width: 450px;" class="settings-form-group">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #e2e8f0;">Senha Inicial</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="new_user_pwd" required style="width: 100%; padding: 8px 10px; font-size: 14px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; padding-right: 35px; box-sizing: border-box; outline: none;" oninput="analyzePasswordStrength(this.value, 'admin_new')">
                    <button type="button" onclick="togglePasswordVisibility('new_user_pwd', this)" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                
                <div style="margin-top: 6px; display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="btn" onclick="generatePasswordAndAnalyze('new_user_pwd', 'admin_new')" style="padding: 4px 10px; font-size: 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                        <i class="fas fa-magic"></i> Gerar Senha Segura
                    </button>
                </div>
                
                <div id="pwd-meter-admin_new" style="display:none; margin-top:8px;">
                     <div style="height:5px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden;">
                          <div id="pwd-bar-admin_new" style="height:100%; width:0%; background:#ef4444; transition: width 0.3s, background 0.3s;"></div>
                     </div>
                     <div style="display:flex; justify-content:space-between; margin-top:4px;">
                         <div id="pwd-hint-admin_new" style="font-size:11px; color:#94a3b8;">Inclua letras, números e símbolos</div>
                         <div id="pwd-text-admin_new" style="font-size:11px; font-weight:600; text-align:right; color:#cbd5e1;">Péssimo</div>
                     </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-activate" style="padding: 8px 18px; font-size: 13px; font-weight: 600; cursor: pointer;">Salvar Novo Usuário</button>
        </form>
    </div>

    <!-- Tabela e Controles de Busca / Paginação -->
    <div style="background: var(--bg-panel, #1e293b); padding: 20px; border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; color: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <h2 style="margin: 0; font-size: 16px; font-weight: 600; color: #f8fafc;">
                Usuários Cadastrados 
                <span style="font-size: 12px; color: #94a3b8; font-weight: normal; margin-left: 6px;">(Total: <?= (int)$total ?>)</span>
            </h2>

            <!-- Formulário de Filtro & Busca -->
            <form method="GET" action="<?= BASE_URL ?>/admin/users" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <input type="text" name="q" placeholder="Buscar por nome ou e-mail..." value="<?= htmlspecialchars((string)($currentSearch ?? '')) ?>" style="padding: 6px 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 13px; color: #fff; min-width: 200px; outline: none;">
                
                <select name="role" style="padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 13px; color: #fff; outline: none;">
                    <option value="all" <?= ($currentRole ?? 'all') === 'all' ? 'selected' : '' ?> style="background: #1e293b;">Todos os Papéis</option>
                    <option value="admins_group" <?= ($currentRole ?? '') === 'admins_group' ? 'selected' : '' ?> style="background: #1e293b;">🛡️ Equipe (Admin / Gerente)</option>
                    <option value="students_group" <?= ($currentRole ?? '') === 'students_group' ? 'selected' : '' ?> style="background: #1e293b;">🎓 Alunos / Comuns</option>
                    <option value="admin" <?= ($currentRole ?? '') === 'admin' ? 'selected' : '' ?> style="background: #1e293b;">Administrador</option>
                    <option value="manager" <?= ($currentRole ?? '') === 'manager' ? 'selected' : '' ?> style="background: #1e293b;">Gerente</option>
                    <option value="user" <?= ($currentRole ?? '') === 'user' ? 'selected' : '' ?> style="background: #1e293b;">Usuário Comum</option>
                    <option value="subscriber" <?= ($currentRole ?? '') === 'subscriber' ? 'selected' : '' ?> style="background: #1e293b;">Assinante</option>
                </select>

                <select name="status" style="padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 13px; color: #fff; outline: none;">
                    <option value="all" <?= ($currentStatus ?? 'all') === 'all' ? 'selected' : '' ?> style="background: #1e293b;">Todos os Status</option>
                    <option value="active" <?= ($currentStatus ?? '') === 'active' ? 'selected' : '' ?> style="background: #1e293b;">🟢 Ativos</option>
                    <option value="suspended" <?= ($currentStatus ?? '') === 'suspended' ? 'selected' : '' ?> style="background: #1e293b;">🟡 Suspensos</option>
                    <option value="banned" <?= ($currentStatus ?? '') === 'banned' ? 'selected' : '' ?> style="background: #1e293b;">🔴 Banidos</option>
                </select>

                <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer;">Filtrar</button>
                <?php if (!empty($currentSearch) || ($currentRole ?? 'all') !== 'all' || ($currentStatus ?? 'all') !== 'all'): ?>
                    <a href="<?= BASE_URL ?>/admin/users" style="font-size: 12px; color: #94a3b8; text-decoration: underline; margin-left: 4px;">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="wp-list-table" style="width: 100%; border-collapse: collapse; color: #f8fafc;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.35);">
                        <th style="padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; font-size: 12px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;">Usuário</th>
                        <th style="padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; font-size: 12px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;">Papel de Acesso</th>
                        <th style="padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; font-size: 12px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;">Status & Moderação</th>
                        <th style="padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; font-size: 12px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;">Segurança (2FA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8; font-size: 14px;">
                                Nenhum usuário encontrado com os filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($users as $u): ?>
                        <?php 
                        $uStatus = $u['status'] ?? 'active'; 
                        $statusBadge = match($uStatus) {
                            'banned' => '<span style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700;">🔴 Banido</span>',
                            'suspended' => '<span style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fde68a; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700;">🟡 Suspenso</span>',
                            default => '<span style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700;">🟢 Ativo</span>',
                        };
                        ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <td style="padding: 14px; vertical-align: top;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #38bdf8); color: #000; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                        <?= strtoupper(mb_substr((string)$u['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #ffffff; font-size: 14px;"><?= htmlspecialchars((string)$u['name']) ?></strong><br>
                                        <small style="color: #94a3b8; font-size: 12px;"><?= htmlspecialchars((string)$u['email']) ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- Papel de Acesso com Alta Visibilidade e Contraste -->
                            <td style="padding: 14px; vertical-align: top;">
                                <div style="margin-bottom: 8px;">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #38bdf8; font-weight: 700; font-size: 13px; text-shadow: 0 0 10px rgba(56, 189, 248, 0.25);">
                                            🛡️ Administrador
                                        </span>
                                    <?php elseif ($u['role'] === 'manager'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #c084fc; font-weight: 700; font-size: 13px;">
                                            📋 Gerente
                                        </span>
                                    <?php elseif ($u['role'] === 'subscriber'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #facc15; font-weight: 700; font-size: 13px;">
                                            ⭐ Assinante
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #f8fafc; font-weight: 600; font-size: 13px;">
                                            👤 Aluno / Comum
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Redefinir Senha e Excluir -->
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <form method="POST" action="<?= BASE_URL ?>/admin/users/reset-password" onsubmit="event.preventDefault(); const f = this; OS.confirm('Deseja realmente alterar a senha deste usuário?', () => f.submit());" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="password" name="new_password" placeholder="Nova senha" required style="font-size: 11px; padding: 4px 6px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 4px; color: #fff; width: 100px; outline: none;">
                                        <button type="submit" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.15); padding: 4px 8px; font-size: 11px; border-radius: 4px; cursor: pointer;">Redefinir</button>
                                    </form>

                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/users/delete" onsubmit="event.preventDefault(); const f = this; OS.confirm('ATENÇÃO: Deseja realmente EXCLUIR o usuário <?= htmlspecialchars(addslashes((string)$u['name'])) ?>? Isso é irreversível.', () => f.submit());" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" style="background: transparent; color: #f87171; border: 1px solid rgba(248, 113, 113, 0.4); padding: 2px 6px; font-size: 10px; font-weight: 600; border-radius: 4px; cursor: pointer;"><i class="fas fa-trash-alt"></i> Excluir Definitivamente</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td style="padding: 14px; vertical-align: top;">
                                <div style="margin-bottom: 8px;">
                                    <?= $statusBadge ?>
                                </div>

                                <!-- Moderação de Status -->
                                <form method="POST" action="<?= BASE_URL ?>/admin/users/status" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" style="font-size: 11px; padding: 4px 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 4px; color: #fff; outline: none; cursor: pointer;">
                                        <option value="active" <?= $uStatus === 'active' ? 'selected' : '' ?> style="background: #1e293b;">Tornar Ativo 🟢</option>
                                        <option value="suspended" <?= $uStatus === 'suspended' ? 'selected' : '' ?> style="background: #1e293b;">Suspender Acesso 🟡</option>
                                        <option value="banned" <?= $uStatus === 'banned' ? 'selected' : '' ?> style="background: #1e293b;">Banir Usuário 🔴</option>
                                    </select>
                                </form>
                            </td>

                            <td style="padding: 14px; vertical-align: top;">
                                <form method="POST" action="<?= BASE_URL ?>/admin/users/2fa-type" style="margin-bottom: 6px;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="two_factor_type" onchange="this.form.submit()" style="font-size: 12px; padding: 4px 6px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 4px; color: #fff; width: 100%; max-width: 150px; outline: none;">
                                        <option value="none" <?= ($u['two_factor_type'] ?? 'none') === 'none' ? 'selected' : '' ?> style="background: #1e293b;">Desativado</option>
                                        <option value="app" <?= ($u['two_factor_type'] ?? 'none') === 'app' ? 'selected' : '' ?> style="background: #1e293b;">App (Google Auth)</option>
                                        <option value="email" <?= ($u['two_factor_type'] ?? 'none') === 'email' ? 'selected' : '' ?> style="background: #1e293b;">E-mail (Código)</option>
                                    </select>
                                </form>
                                
                                <?php if (($u['two_factor_type'] ?? 'none') === 'app'): ?>
                                    <?php if (!empty($u['two_factor_secret'])): ?>
                                        <span style="color: #4ade80; font-size: 11px; font-weight: 600;">✅ Sincronizado</span>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/admin/users/2fa?id=<?= $u['id'] ?>" class="btn" style="background: #ef4444; color: white; display: inline-block; font-size: 10px; padding: 3px 6px; border-radius: 4px; text-decoration: none;">Configurar QR</a>
                                    <?php endif; ?>
                                <?php elseif (($u['two_factor_type'] ?? 'none') === 'email'): ?>
                                    <span style="color: #4ade80; font-size: 11px; font-weight: 600;">✅ E-mail Ativo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <?php 
            $queryParams = [
                'q' => $currentSearch ?? '',
                'role' => $currentRole ?? 'all',
                'status' => $currentStatus ?? 'all',
            ];
            $buildPageUrl = function(int $p) use ($queryParams) {
                $params = array_filter(array_merge($queryParams, ['page' => $p]), fn($v) => $v !== '' && $v !== 'all');
                return BASE_URL . '/admin/users' . (!empty($params) ? '?' . http_build_query($params) : '');
            };
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 12px; color: #94a3b8;">
                    Página <strong style="color:#fff;"><?= (int)$page ?></strong> de <strong style="color:#fff;"><?= (int)$totalPages ?></strong>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= $buildPageUrl($page - 1) ?>" class="btn" style="padding: 4px 10px; font-size: 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; text-decoration: none;">&larr; Anterior</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p == $page): ?>
                            <span style="padding: 4px 10px; font-size: 12px; font-weight: 700; background: #38bdf8; color: #000; border-radius: 4px;"><?= $p ?></span>
                        <?php elseif ($p == 1 || $p == $totalPages || abs($p - $page) <= 2): ?>
                            <a href="<?= $buildPageUrl($p) ?>" class="btn" style="padding: 4px 10px; font-size: 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #fff; text-decoration: none;"><?= $p ?></a>
                        <?php elseif (abs($p - $page) == 3): ?>
                            <span style="padding: 4px 6px; font-size: 12px; color: #64748b;">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= $buildPageUrl($page + 1) ?>" class="btn" style="padding: 4px 10px; font-size: 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; text-decoration: none;">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
