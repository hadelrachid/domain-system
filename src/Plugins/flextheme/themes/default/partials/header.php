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

            <!-- Área de Login / Aluno (Desktop & Mobile - Sessão Isolada da Academia) -->
            <?php
                $isLoggedIn = false;
                $studentDisplayName = 'Aluno';
                if (isset($GLOBALS['app']) && $GLOBALS['app']->getContainer()->has(\DomainSystem\Core\Security\SessionManager::class)) {
                    try {
                        $sess = $GLOBALS['app']->getContainer()->make(\DomainSystem\Core\Security\SessionManager::class);
                        if ($sess->has('academy_student_id')) {
                            $isLoggedIn = true;
                            $studentDisplayName = (string)$sess->get('academy_student_name', 'Aluno');
                        }
                    } catch (\Throwable $e) {}
                } elseif (isset($_SESSION['academy_student_id'])) {
                    $isLoggedIn = true;
                    $studentDisplayName = (string)($_SESSION['academy_student_name'] ?? 'Aluno');
                }
            ?>
            <div class="ds-header-auth-area" style="display: flex; align-items: center; gap: 8px; margin-left: 20px;">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $baseUrl ?>/aluno/painel" class="ds-auth-nav-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; background: rgba(69, 243, 255, 0.12); border: 1px solid rgba(69, 243, 255, 0.35); color: #45f3ff; font-weight: 700; font-size: 13px; text-decoration: none;">
                        <span>🎓 Meu Painel</span>
                    </a>
                    <a href="<?= $baseUrl ?>/aluno/logout" class="ds-auth-nav-link" style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 6px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); color: #cbd5e1; font-weight: 600; font-size: 12px; text-decoration: none;">
                        Sair
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/aluno/login" class="ds-auth-nav-link" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; background: transparent; border: 1px solid rgba(255, 255, 255, 0.2); color: #f8fafc; font-weight: 600; font-size: 13px; text-decoration: none;">
                        <span>Entrar</span>
                    </a>
                    <a href="<?= $baseUrl ?>/aluno/cadastro" class="ds-auth-nav-link" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 14px; border-radius: 6px; background: #00d284; border: 1px solid #00d284; color: #0b0c10; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 2px 8px rgba(0, 210, 132, 0.25);">
                        <span>Cadastrar</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('nav-menu').classList.toggle('active');
    });
</script>
