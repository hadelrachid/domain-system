<header style="padding: 15px 0; border-bottom: 1px solid rgba(69, 243, 255, 0.1); position: sticky; top: 0; z-index: 1000; background: rgba(11, 12, 16, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <?php $baseUrl = defined('BASE_URL') ? BASE_URL : ''; ?>
        <a href="<?= $baseUrl ?>/" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
            <img src="<?= $baseUrl ?>/assets/img/site-home/logo-rd.svg" class="logo-img" alt="RD Logo" style="height: 48px; width: auto;">
            <div class="logo" style="font-size: 1.5rem; font-weight: 800; letter-spacing: -1px; color: #fff;">
                Rachid<span class="text-primary">D.com</span>
            </div>
        </a>
        <button class="mobile-menu-toggle" id="mobile-menu-btn">☰</button>
        <nav class="nav-menu" id="nav-menu">
            <ul style="list-style: none; gap: 30px; display: flex; flex-direction: inherit;">
                <?php 
                    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); 
                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                ?>
                <li><a href="<?= $baseUrl ?>/" class="nav-link" style="<?= ($currentUri === $baseUrl . '/' || $currentUri === $baseUrl) ? 'color: var(--primary);' : '' ?>">Início</a></li>
                <li><a href="<?= $baseUrl ?>/docs" class="nav-link" style="<?= $currentUri === $baseUrl . '/docs' ? 'color: var(--primary);' : '' ?>">OS Docs</a></li>
                <li><a href="<?= $baseUrl ?>/p/tutoriais" class="nav-link" style="<?= $currentUri === $baseUrl . '/p/tutoriais' ? 'color: var(--primary);' : '' ?>">Cursos & Tutoriais</a></li>
                <li><a href="<?= $baseUrl ?>/#livro" class="nav-link" style="color: var(--text-main);">Livro SOLID</a></li>
                <li><a href="<?= $baseUrl ?>/p/sobre" class="nav-link" style="<?= $currentUri === $baseUrl . '/p/sobre' ? 'color: var(--primary);' : '' ?>">Sobre</a></li>
            </ul>
        </nav>
    </div>
</header>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('nav-menu').classList.toggle('active');
    });
</script>




