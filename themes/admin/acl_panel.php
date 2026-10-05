<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
?>

<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h2 style="color: #1e293b; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        <i class="fas fa-shield-alt" style="color: #6366f1;"></i> Controle de Acessos (ACL)
    </h2>

    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d1fae5; color: #059669; padding: 12px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #10b981;">
            Permissões salvas com sucesso!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ef4444;">
            Erro: <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <p style="color: #64748b;">Nesta tela você define quais Privilégios (Capabilities) pertencem a cada Cargo (Role). O Motor de Identidade usará isso para liberar ou bloquear recursos.</p>

    <form method="POST" action="<?= BASE_URL ?>/admin/acl/save">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                        <th style="padding: 12px; text-align: left; color: #334155; width: 250px;">Privilégio (Capability)</th>
                        <?php foreach ($roles as $role): ?>
                            <th style="padding: 12px; text-align: center; color: #334155;">
                                <?= htmlspecialchars($role['name']) ?><br>
                                <small style="color: #94a3b8; font-weight: normal;"><?= htmlspecialchars($role['slug']) ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($capabilities)): ?>
                        <tr>
                            <td colspan="<?= count($roles) + 1 ?>" style="padding: 20px; text-align: center; color: #94a3b8;">Nenhum privilégio registrado no sistema ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($capabilities as $cap): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; color: #475569;">
                                    <strong><?= htmlspecialchars($cap['slug']) ?></strong><br>
                                    <small style="color: #94a3b8;"><?= htmlspecialchars($cap['context'] ?? '') ?></small>
                                </td>
                                <?php foreach ($roles as $role): ?>
                                    <?php 
                                        $roleId = $role['id'];
                                        $capId = $cap['id'];
                                        $isChecked = isset($role_caps[$roleId]) && in_array($capId, $role_caps[$roleId]);
                                        
                                        // O super admin não deve ser desmarcado facilmente ou as marcações são ignoradas no backend (já que tem wildcard)
                                        $isDisabled = ($role['slug'] === 'admin') ? 'disabled checked title="Admin tem passe livre (*)"' : '';
                                    ?>
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="permissions[<?= $roleId ?>][]" value="<?= $capId ?>" <?= $isChecked ? 'checked' : '' ?> <?= $isDisabled ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                <i class="fas fa-save"></i> Salvar Matriz de Acessos
            </button>
        </div>
    </form>
</div>
