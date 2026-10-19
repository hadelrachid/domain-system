<header style="padding: 15px 0; border-bottom: 1px solid rgba(69, 243, 255, 0.1); position: sticky; top: 0; z-index: 1000; background: rgba(11, 12, 16, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <?php $baseUrl = defined('BASE_URL') ? BASE_URL : ''; ?>
        <a href="<?= $baseUrl ?>/" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
            <img src="<?= $baseUrl ?>/assets/img/logo.png" class="logo-img" alt="Logo" style="height: 40px; width: auto;" onerror="this.style.display='none'">
            <div class="logo" style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; color: #fff;">
                Domain<span class="text-primary">System</span>
            </div>
        </a>
        <button class="mobile-menu-toggle" id="mobile-menu-btn">☰</button>
        <nav class="nav-menu" id="nav-menu">
            <?php 
                $dynamicMenu = '';
                if (isset($GLOBALS['app'])) {
                    try {
                        $ctrl = $GLOBALS['app']->getContainer()->make(\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class);
                        $dynamicMenu = $ctrl->renderNavMenu('header');
                    } catch (\Exception $e) {}
                }
                
                if (!empty(trim(strip_tags($dynamicMenu)))) {
                    echo $dynamicMenu;
                } else {
                    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); 
                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            ?>
                <ul style="list-style: none; gap: 30px; display: flex; flex-direction: inherit; margin:0; padding:0;">
                    <li><a href="<?= $baseUrl ?>/" class="nav-link" style="<?= ($currentUri === $baseUrl . '/' || $currentUri === $baseUrl) ? 'color: var(--primary);' : '' ?>">Início</a></li>
                    <li><a href="<?= $baseUrl ?>/cursos" class="nav-link" style="<?= strpos($currentUri, $baseUrl . '/cursos') === 0 ? 'color: var(--primary);' : '' ?>">Cursos</a></li>
                    <li><a href="<?= $baseUrl ?>/sobre" class="nav-link" style="<?= $currentUri === $baseUrl . '/sobre' ? 'color: var(--primary);' : '' ?>">Sobre</a></li>
                    <li><a href="<?= $baseUrl ?>/admin" class="nav-link" style="color: var(--text-main);">Painel</a></li>
                </ul>
            <?php } ?>
        </nav>
    </div>
</header>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('nav-menu').classList.toggle('active');
    });
</script>
