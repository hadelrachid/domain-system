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
        <div style="display: flex; gap: 20px; justify-content: center; margin-bottom: 20px; font-size: 0.9rem; flex-wrap: wrap;">
            <a href="<?= $baseUrl ?>/sobre" style="color: var(--text-muted); text-decoration: none;">Sobre Nós</a>
            <a href="<?= $baseUrl ?>/privacidade" style="color: var(--text-muted); text-decoration: none;">Privacidade</a>
            <a href="<?= $baseUrl ?>/termos" style="color: var(--text-muted); text-decoration: none;">Termos de Uso</a>
            <a href="javascript:void(0)" onclick="window.RachidDCookieConsent &amp;&amp; window.RachidDCookieConsent.open()" style="color: var(--text-muted); text-decoration: none;">Preferências de Cookies</a>
        </div>
        <?php endif; ?>
        <div style="font-size: 0.9rem;">
            &copy; <?= date('Y') ?> Domain System. Todos os direitos reservados.
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 8px; line-height: 1.5;">
            67.761.709 MAURA PEREIRA DE AZEVEDO &bull; CNPJ: 67.761.709/0001-80<br>
            Rua Antonio Vaz Santoro, 84, Casa - Residencial Altos do Bosque - São José dos Campos/SP - CEP 12239-853
        </div>
    </div>
</footer>
