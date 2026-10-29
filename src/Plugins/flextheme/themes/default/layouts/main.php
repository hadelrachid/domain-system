<?php

declare(strict_types=1);

// Layout Base Principal (Main HTML Shell)
// O array $seo (SeoManagerInterface) já foi injetado pelo ThemeController

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$canonicalUrl = $protocol . $host . $currentUri;

$seo->setCanonical($canonicalUrl)
    ->setFavicon((defined('BASE_URL') ? BASE_URL : '') . '/assets/img/logo.png')
    ->addMeta('theme-color', '#0b0c10');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->generateTags() ?>
    
    <!-- Modern Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Inline Crítico para performance máxima no LCP (Largest Contentful Paint) */
        :root {
            --bg-color: #0b0c10;
            --surface: rgba(31, 40, 51, 0.7);
            --primary: #45f3ff;
            --primary-glow: rgba(69, 243, 255, 0.6);
            --text-main: #c5c6c7;
            --text-muted: #8b8c8d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Cyberpunk Grid Background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(rgba(69, 243, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(69, 243, 255, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -2;
            pointer-events: none;
        }

        /* Glow effect in center */
        body::after {
            content: '';
            position: fixed;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 60%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: -1;
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Utilitários */
        .text-primary { color: var(--primary); }
        .font-mono { font-family: 'Fira Code', monospace; }
        .glass-panel {
            background: var(--surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(69, 243, 255, 0.15);
            border-radius: 16px;
        }

        /* Responsive Navbar */
        .nav-menu { display: flex; }
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: block; }
            .nav-menu {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 80px;
                left: 0;
                right: 0;
                background: var(--bg-color);
                padding: 20px;
                border-bottom: 1px solid rgba(69, 243, 255, 0.1);
                z-index: 100;
            }
            .nav-menu.active { display: flex; }
            
            /* Typography adjustments */
            h1 { font-size: 2.5rem !important; }
            
            /* Responsive Grid */
            .grid-responsive {
                grid-template-columns: 1fr !important;
            }
            
            /* Center Book Buttons */
            .livro-buttons {
                justify-content: center !important;
            }
        }

        /* Buttons and Interactive Elements */
        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 1rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary);
            color: #0b0c10;
            border: 2px solid var(--primary);
            box-shadow: 0 0 15px rgba(69, 243, 255, 0.3);
        }
        
        .btn-primary:hover {
            background: transparent;
            color: var(--primary);
            box-shadow: 0 0 25px rgba(69, 243, 255, 0.6);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: 0 0 15px rgba(69, 243, 255, 0.1);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: #0b0c10;
            box-shadow: 0 0 25px rgba(69, 243, 255, 0.4);
            transform: translateY(-2px);
        }

        .nav-link {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease, text-shadow 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            text-shadow: 0 0 10px var(--primary-glow);
        }

        .mobile-menu-toggle {
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            color: #fff;
            transform: scale(1.1);
        }

        /* Logo Animation */
        .logo {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-block;
        }

        .logo:hover {
            transform: scale(1.05);
            text-shadow: 0 0 15px rgba(69, 243, 255, 0.5);
        }

        .logo-img {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            filter: drop-shadow(0 0 8px rgba(69, 243, 255, 0.2)) brightness(1);
            transform-origin: center center;
        }
        
        .logo-img:hover {
            filter: drop-shadow(0 0 25px rgba(69, 243, 255, 0.9)) brightness(1.2) contrast(1.1);
            transform: scale(1.05);
        }

        /* Academy Course Navigation & Classroom */
        .ds-academy-nav {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .course-nav-header {
            font-weight: 800;
            font-size: 1.1rem;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(69, 243, 255, 0.15);
        }
        .course-nav-home-link {
            color: #fff;
            text-decoration: none;
            transition: color 0.2s;
        }
        .course-nav-home-link:hover {
            color: var(--primary);
        }
        .course-nav-module-title {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--primary);
            margin: 14px 0 8px 0;
            font-weight: 700;
        }
        .course-nav-lessons {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .course-nav-lesson-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
        .course-nav-lesson-link:hover {
            background: rgba(69, 243, 255, 0.08);
            color: #fff;
            transform: translateX(3px);
        }
        .course-nav-lesson-item.is-active .course-nav-lesson-link {
            background: rgba(69, 243, 255, 0.15);
            color: var(--primary);
            font-weight: 600;
            border-left: 3px solid var(--primary);
            box-shadow: 0 0 15px rgba(69, 243, 255, 0.1);
        }
        .lesson-index {
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            opacity: 0.75;
        }
        @media (max-width: 900px) {
            .ds-academy-grid {
                grid-template-columns: 1fr !important;
            }
            .ds-academy-sidebar {
                position: static !important;
                max-height: none !important;
            }
        }
    </style>
</head>
<body>

    <?php 
        $partRenderer = (isset($GLOBALS['app']) && $GLOBALS['app']->getContainer()->has(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class))
            ? $GLOBALS['app']->getContainer()->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class)
            : null;
        $themeFolder = basename($themeDir);
        $customHeader = ($partRenderer !== null) ? $partRenderer->renderHeader($themeFolder, get_defined_vars()) : '';
        if (trim($customHeader) !== '') {
            echo $customHeader;
        } else {
            require $themeDir . '/partials/header.php';
        }
    ?>

    <main>
        <?= $content ?>
    </main>

    <?php 
        $customFooter = ($partRenderer !== null) ? $partRenderer->renderFooter($themeFolder, get_defined_vars()) : '';
        if (trim($customFooter) !== '') {
            echo $customFooter;
        } else {
            require $themeDir . '/partials/footer.php';
        }
    ?>

</body>
</html>
