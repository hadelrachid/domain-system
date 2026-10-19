<footer style="padding: 60px 0 40px; border-top: 1px solid rgba(69, 243, 255, 0.1); margin-top: 80px;">
    <div class="container" style="text-align: center; color: var(--text-muted);">
        <?php $baseUrl = defined('BASE_URL') ? BASE_URL : ''; ?>
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px;">
            <img src="<?= $baseUrl ?>/assets/img/logo.png" class="logo-img" alt="Logo" style="height: 44px; width: auto; opacity: 0.85;" onerror="this.style.display='none'">
            <div class="logo" style="font-size: 1.5rem; font-weight: 800; color: #fff;">
                Domain<span class="text-primary">System</span>
            </div>
        </div>
        <p style="margin-bottom: 30px;">Plataforma modular e flexível construída sobre o <span class="text-primary">Domain-System OS</span></p>
                <?php 
            $dynamicFooterMenu = '';
            if (isset($GLOBALS['app'])) {
                try {
                    $ctrl = $GLOBALS['app']->getContainer()->make(\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class);
                    $dynamicFooterMenu = $ctrl->renderNavMenu('footer');
                } catch (\Exception $e) {}
            }
            if (!empty(trim(strip_tags($dynamicFooterMenu)))): 
                echo '<div style="margin-bottom: 20px; font-size: 0.9rem; justify-content: center; display: flex;">' . $dynamicFooterMenu . '</div>';
            else:
        ?>
        <div style="display: flex; gap: 20px; justify-content: center; margin-bottom: 20px; font-size: 0.9rem;">
            <a href="<?= $baseUrl ?>/sobre" style="color: var(--text-muted); text-decoration: none;">Sobre Nós</a>
            <a href="<?= $baseUrl ?>/privacidade" style="color: var(--text-muted); text-decoration: none;">Privacidade</a>
            <a href="<?= $baseUrl ?>/termos" style="color: var(--text-muted); text-decoration: none;">Termos de Uso</a>
        </div>
        <?php endif; ?>
        <div style="font-size: 0.9rem;">
            &copy; <?= date('Y') ?> Domain System. Todos os direitos reservados.
        </div>
    </div>
</footer>

<!-- Notificação de Privacidade (LGPD) -->
<div id="privacyNotice" class="glass-panel" style="display: none; position: fixed; bottom: 20px; left: 20px; right: 20px; max-width: 600px; margin: 0 auto; z-index: 9999; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transform: translateY(100%); transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <div style="font-size: 0.95rem; line-height: 1.5;">
            Utilizamos cookies estritamente necessários para melhorar sua experiência no site. Ao continuar navegando, você concorda com nossa
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/privacidade" style="color: var(--primary); text-decoration: none; font-weight: bold;">Política de Privacidade</a> e 
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/termos" style="color: var(--primary); text-decoration: none; font-weight: bold;">Termos de Uso</a>.
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="rejectPrivacy" class="btn-outline" style="padding: 8px 16px; font-size: 0.9rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); color: #fff; background: transparent; cursor: pointer;">Recusar</button>
            <button type="button" id="acceptPrivacy" class="btn-primary" style="padding: 8px 16px; font-size: 0.9rem; border-radius: 6px; border: none; background: var(--primary); color: #0b0c10; font-weight: bold; cursor: pointer;">Aceitar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const privacyNotice = document.getElementById('privacyNotice');
    if (!privacyNotice) return;

    const acceptBtn = document.getElementById('acceptPrivacy');
    const rejectBtn = document.getElementById('rejectPrivacy');

    function hideNotice() {
        privacyNotice.style.transform = 'translateY(100%)';
        setTimeout(() => { privacyNotice.style.display = 'none'; }, 500);
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => {
            localStorage.setItem('ds_privacy_accepted', 'true');
            hideNotice();
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', () => {
            sessionStorage.setItem('ds_privacy_rejected', 'true');
            hideNotice();
        });
    }

    const privacyAccepted = localStorage.getItem('ds_privacy_accepted');
    const privacyRejected = sessionStorage.getItem('ds_privacy_rejected');
    
    if (privacyAccepted !== 'true' && privacyRejected !== 'true') {
        setTimeout(() => {
            privacyNotice.style.display = 'block';
            // Force reflow
            void privacyNotice.offsetWidth;
            privacyNotice.style.transform = 'translateY(0)';
        }, 1000);
    }
});
</script>
