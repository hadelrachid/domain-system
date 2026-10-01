<?php
/**
 * Partial: _2fa_field.php
 * Campo de configuração de Autenticação em 2 Fatores.
 *
 * Variáveis esperadas:
 *   string $twoFactorType  valor atual: "none" | "email" | "app"
 *   string $userEmail      e-mail do usuário (para hint)
 */
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
$_2fa = $twoFactorType ?? 'none';
$_2faEmail = $userEmail ?? '';
?>
<div class="settings-form-group" style="margin-top:10px;">
    <label style="font-weight:600;font-size:14px;display:block;margin-bottom:10px;">
        <i class="fas fa-shield-alt" style="color:var(--primary,#2271b1);width:16px;"></i>
        Autenticação em 2 Fatores (2FA)
    </label>

    <label id="lbl-2fa-none" style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border:2px solid <?= $_2fa==='none' ? 'var(--primary,#2271b1)' : '#e2e8f0' ?>;border-radius:8px;cursor:pointer;margin-bottom:8px;transition:border-color 0.2s;">
        <input type="radio" name="two_factor_type" value="none" <?= $_2fa==='none' ? 'checked' : '' ?>
               onchange="highlight2fa('none')" style="margin-top:3px;accent-color:var(--primary,#2271b1);">
        <div>
            <div style="font-weight:700;font-size:13px;">Desativado</div>
            <div style="font-size:12px;color:#64748b;">Apenas usuário e senha para entrar.</div>
        </div>
    </label>

    <label id="lbl-2fa-email" style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border:2px solid <?= $_2fa==='email' ? 'var(--primary,#2271b1)' : '#e2e8f0' ?>;border-radius:8px;cursor:pointer;margin-bottom:8px;transition:border-color 0.2s;">
        <input type="radio" name="two_factor_type" value="email" <?= $_2fa==='email' ? 'checked' : '' ?>
               onchange="highlight2fa('email')" style="margin-top:3px;accent-color:var(--primary,#2271b1);">
        <div>
            <div style="font-weight:700;font-size:13px;"><i class="fas fa-envelope" style="color:#10b981;margin-right:4px;"></i> Por E-mail</div>
            <div style="font-size:12px;color:#64748b;">Código enviado para <strong><?= htmlspecialchars($_2faEmail) ?></strong> a cada login.</div>
        </div>
    </label>

    <label id="lbl-2fa-app" style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border:2px solid <?= $_2fa==='app' ? 'var(--primary,#2271b1)' : '#e2e8f0' ?>;border-radius:8px;cursor:pointer;transition:border-color 0.2s;">
        <input type="radio" name="two_factor_type" value="app" <?= $_2fa==='app' ? 'checked' : '' ?>
               onchange="highlight2fa('app')" style="margin-top:3px;accent-color:var(--primary,#2271b1);">
        <div>
            <div style="font-weight:700;font-size:13px;"><i class="fas fa-mobile-alt" style="color:#8b5cf6;margin-right:4px;"></i> Por Aplicativo (TOTP)</div>
            <div style="font-size:12px;color:#64748b;">Google Authenticator ou similar. Mais seguro.</div>
        </div>
    </label>
</div>
<script>
(function () {
    function highlight2fa(val) {
        ['none', 'email', 'app'].forEach(function (v) {
            var lbl = document.getElementById('lbl-2fa-' + v);
            if (lbl) lbl.style.borderColor = (v === val)
                ? (getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#2271b1')
                : '#e2e8f0';
        });
    }
    window.highlight2fa = highlight2fa;
    var checked = document.querySelector('input[name="two_factor_type"]:checked');
    if (checked) highlight2fa(checked.value);
})();
</script>
