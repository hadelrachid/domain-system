<?php
/**
 * Partial: _profile_photo_field.php
 *
 * Campo de foto dentro do formulário de configurações de qualquer cockpit.
 * Inclui _avatar.php (tamanho "lg") + botão "Alterar Foto".
 *
 * Variáveis esperadas:
 *   string $avatarName   nome completo
 *   string $avatarPhoto  URL/caminho da foto atual (ou vazio)
 *   string $avatarId     id do avatar no modal (ex: "modal-avatar")
 *   string $headerId     id do avatar no header (ex: "header-avatar")
 */
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
$avatarSize = 'lg';
?>
<div style="text-align:center; margin-bottom:22px;">
    <?php include __DIR__ . '/_avatar.php'; ?>
    <label style="display:inline-block; margin-top:10px; padding:6px 14px;
                  background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px;
                  cursor:pointer; font-size:13px; font-weight:600; color:#475569;">
        <i class="fas fa-camera"></i> Alterar Foto
        <input type="file" name="photo" accept="image/*" style="display:none;"
               onchange="previewAvatar(this,
                   '<?= htmlspecialchars($avatarId ?? 'modal-avatar') ?>',
                   '<?= htmlspecialchars($headerId ?? 'header-avatar') ?>')">
    </label>
</div>
