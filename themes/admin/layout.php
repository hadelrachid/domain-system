<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain-System (Dark OS)</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        <?php
            // Motor Dinâmico de Skins (Estilo VS Code)
            $skinDir = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 2);
            $activeSkinFile = $skinDir . '/config/active_skin.json';
            $activeSkinName = 'dark_futurista';
            if (file_exists($activeSkinFile)) {
                $activeData = json_decode(file_get_contents($activeSkinFile), true);
                if (isset($activeData['active'])) $activeSkinName = $activeData['active'];
            }
            
            $skinFile = $skinDir . '/config/skins/' . $activeSkinName . '.json';
            $vars = [
                "--bg-deep" => "#0d1117", "--bg-panel" => "#161b22", "--bg-hover" => "#21262d", 
                "--border" => "#30363d", "--text-main" => "#c9d1d9", "--text-muted" => "#8b949e",
                "--accent-green" => "#00d284", "--accent-orange" => "#f56e28", "--accent-blue" => "#58a6ff"
            ];
            
            if (file_exists($skinFile)) {
                $skinData = json_decode(file_get_contents($skinFile), true);
                if (isset($skinData['variables'])) {
                    $vars = array_merge($vars, $skinData['variables']);
                }
            }
            
            echo ":root {\n";
            foreach ($vars as $key => $val) {
                echo "            {$key}: {$val};\n";
            }
            echo "        }\n";
        ?>

        body { 
            margin: 0; 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--bg-deep); 
            color: var(--text-main);
            display: flex; 
            height: 100vh; 
            /* Subtle grid pattern for futuristic look */
            background-image: 
                linear-gradient(rgba(48, 54, 61, 0.3) 1px, transparent 1px),
                linear-gradient(90deg, rgba(48, 54, 61, 0.3) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* SIDEBAR (GLASSMORPHISM / NEON) */
        #adminmenuback { 
            width: 200px; 
            background: rgba(22, 27, 34, 0.85); 
            backdrop-filter: blur(10px);
            color: #fff; 
            height: 100%; 
            position: fixed; 
            border-right: 1px solid var(--border); 
            overflow-y: auto; 
            box-shadow: 4px 0 15px rgba(0,0,0,0.5);
            z-index: 100;
        }

        #adminmenu { padding: 0; margin: 0; list-style: none; }
        #adminmenu li a { 
            display: flex; align-items: center; gap: 10px; 
            padding: 12px 20px; 
            color: var(--text-muted); 
            text-decoration: none; 
            font-size: 14px; 
            transition: all 0.2s ease-in-out; 
            border-left: 3px solid transparent;
        }
        
        #adminmenu li a:hover { 
            background: var(--bg-hover); 
            color: var(--accent-green); 
        }

        #adminmenu li.current > a { 
            background: rgba(0, 210, 132, 0.1); 
            color: var(--accent-green); 
            border-left: 3px solid var(--accent-green);
            text-shadow: 0 0 8px rgba(0, 210, 132, 0.4);
        }

        #adminmenu li ul {
            background: rgba(13, 17, 23, 0.9);
            border-left: 1px solid var(--border);
        }

        /* MAIN CONTENT AREA */
        #wpcontent { 
            margin-left: 200px; 
            padding: 40px; 
            width: calc(100% - 200px); 
            box-sizing: border-box; 
            overflow-y: auto; 
        }

        h1 { 
            font-size: 24px; 
            font-weight: 500; 
            margin: 0 0 25px; 
            color: #fff; 
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .wrap { max-width: 1400px; margin: 0 auto; }

        /* TABLES - FUTURISTIC UI */
        table.wp-list-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: var(--bg-panel); 
            border: 1px solid var(--border); 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4); 
        }
        
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border); }
        th { font-weight: 600; background: var(--bg-hover); color: #fff; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;}
        
        /* BUTTONS - CYBERPUNK FEEL (GLOBAL OVERRIDE) */
        input[type="submit"], input[type="button"], .btn, .page-title-action, .button { 
            padding: 8px 16px !important; 
            border: 1px solid var(--accent-blue) !important; 
            border-radius: 6px !important; 
            cursor: pointer !important; 
            text-decoration: none !important; 
            font-size: 12px !important; 
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            color: #fff !important;
            background: linear-gradient(135deg, rgba(88,166,255,0.1) 0%, rgba(88,166,255,0.3) 100%) !important;
            box-shadow: 0 0 10px rgba(88,166,255,0.2) !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            position: relative;
            overflow: hidden;
        }

        input[type="submit"]:hover, .btn:hover, .button:hover {
            background: linear-gradient(135deg, rgba(88,166,255,0.3) 0%, rgba(88,166,255,0.6) 100%) !important;
            box-shadow: 0 0 20px rgba(88,166,255,0.6), inset 0 0 10px rgba(255,255,255,0.2) !important;
            transform: translateY(-2px);
        }
        
        input[type="submit"]:active, .btn:active, .button:active {
            transform: translateY(1px);
            box-shadow: 0 0 5px rgba(88,166,255,0.4) !important;
        }

        /* Cores Específicas de Ação (Overrides) */
        .button-primary, .btn-activate {
            border-color: var(--accent-green) !important;
            background: linear-gradient(135deg, rgba(0,210,132,0.1) 0%, rgba(0,210,132,0.3) 100%) !important;
            box-shadow: 0 0 10px rgba(0,210,132,0.2) !important;
        }
        .button-primary:hover, .btn-activate:hover {
            background: linear-gradient(135deg, rgba(0,210,132,0.3) 0%, rgba(0,210,132,0.6) 100%) !important;
            box-shadow: 0 0 20px rgba(0,210,132,0.6), inset 0 0 10px rgba(255,255,255,0.2) !important;
        }

        .btn-deactivate, .btn-danger, input[type="submit"][style*="color: #dc2626"] {
            border-color: var(--accent-orange) !important;
            background: linear-gradient(135deg, rgba(245,110,40,0.1) 0%, rgba(245,110,40,0.3) 100%) !important;
            box-shadow: 0 0 10px rgba(245,110,40,0.2) !important;
            color: #fff !important;
        }
        .btn-deactivate:hover, .btn-danger:hover, input[type="submit"][style*="color: #dc2626"]:hover {
            background: linear-gradient(135deg, rgba(245,110,40,0.3) 0%, rgba(245,110,40,0.6) 100%) !important;
            box-shadow: 0 0 20px rgba(245,110,40,0.6), inset 0 0 10px rgba(255,255,255,0.2) !important;
        }

        .btn-core { border-color: var(--border) !important; color: var(--text-muted) !important; background: var(--bg-deep) !important; cursor: not-allowed !important; box-shadow: none !important; }

        .badge { font-size: 11px; padding: 2px 8px; border-radius: 12px; background: var(--border); color: var(--text-main); margin-left: 5px; }

        /* UPLOAD BOXES */
        .upload-box { 
            background: var(--bg-panel); 
            padding: 25px; 
            border: 1px dashed var(--border); 
            border-radius: 8px;
            margin-bottom: 25px; 
        }

        input[type="text"], input[type="password"], select {
            background: var(--bg-deep);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 4px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 5px rgba(88,166,255,0.3);
        }

        /* SCROLLBARS */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-deep); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* LEGACY UI OVERRIDES (Forcing Dark Mode on Plugin Views) */
        .postbox, .card, .dashboard-widget, .sys-content { 
            background: var(--bg-panel) !important; 
            border: 1px solid var(--border) !important; 
            border-radius: 8px !important;
            color: var(--text-main) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
        }

        /* AGGRESSIVE INLINE STYLE OVERRIDES (Catching <div style="background: #fff">) */
        div[style*="background: #fff"], 
        div[style*="background:#fff"], 
        div[style*="background-color: #fff"],
        div[style*="background: white"],
        div[style*="background: #f0f6fc"],
        div[style*="background: #fafafa"] {
            background: var(--bg-panel) !important;
            border-color: var(--border) !important;
            color: var(--text-main) !important;
        }

        /* Removendo borders antigas hardcoded */
        div[style*="border: 1px solid #c3c4c7"],
        div[style*="border: 1px solid #ccc"] {
            border-color: var(--border) !important;
        }
        
        /* Headers das postboxes ou cards */
        .postbox h2, .postbox h3, .card h2, .card h3 { 
            color: #fff !important; 
            border-bottom: 1px solid var(--border) !important;
            margin-top: 0;
            padding-bottom: 10px;
        }
        
        .inside { padding: 15px !important; }
        
        /* Formulários Legados */
        .form-table th { color: var(--text-main) !important; font-weight: 500; }
        .form-table td { color: var(--text-muted) !important; }
        
        input[type="text"], input[type="password"], input[type="email"], input[type="number"], select, textarea {
            background: var(--bg-deep) !important;
            border: 1px solid var(--border) !important;
            color: var(--text-main) !important;
            padding: 8px 12px !important;
            border-radius: 4px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none !important;
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 5px rgba(88,166,255,0.3) !important;
        }
        
        /* Textos soltos que ficaram pretos */
        p, label, span, strong, h2, h3, h4 { color: var(--text-main) !important; }
        
        /* Logs e blocos de código */
        pre, code {
            background: var(--bg-deep) !important;
            color: var(--accent-green) !important;
            border: 1px solid var(--border) !important;
        }
        
        /* Abas (Nav-tabs) e Tabelas Hardcoded */
        .nav-tab-wrapper { border-bottom: 1px solid var(--border) !important; }
        .nav-tab { 
            background: var(--bg-panel) !important; 
            border: 1px solid var(--border) !important; 
            color: var(--text-muted) !important; 
        }
        .nav-tab-active { 
            background: var(--bg-hover) !important; 
            color: var(--accent-green) !important; 
            border-bottom-color: var(--bg-hover) !important;
        }
        
        /* Forçando fundos das tabelas */
        table, tr, td, th {
            background-color: transparent !important;
            border-color: var(--border) !important;
            color: var(--text-main) !important;
        }

        /* FORMULÁRIOS E CALENDÁRIOS (GLOBAL OVERRIDE) */
        input[type="text"], input[type="password"], input[type="email"], input[type="date"], input[type="time"], input[type="number"], input[type="url"], textarea, select {
            background: rgba(13, 17, 23, 0.7) !important;
            border: 1px solid var(--border) !important;
            color: var(--text-main) !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-family: inherit;
            transition: all 0.3s ease !important;
            box-sizing: border-box;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none !important;
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 10px rgba(88, 166, 255, 0.3) !important;
            background: var(--bg-deep) !important;
        }

        /* Customização dos ícones nativos (Calendário e Spinners de Números) para Dark Mode */
        ::-webkit-calendar-picker-indicator,
        ::-webkit-inner-spin-button, 
        ::-webkit-outer-spin-button {
            filter: invert(0.8) sepia(1) hue-rotate(180deg) saturate(500%) opacity(0.8);
            cursor: pointer;
            transition: all 0.2s;
        }
        ::-webkit-calendar-picker-indicator:hover,
        ::-webkit-inner-spin-button:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        /* UPLOAD DE ARQUIVOS (Procurar) - CYBERPUNK */
        input[type="file"] {
            color: var(--text-muted) !important;
            background: rgba(13, 17, 23, 0.5) !important;
            border: 1px dashed var(--accent-blue) !important;
            padding: 15px !important;
            border-radius: 6px !important;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        input[type="file"]:hover {
            border-color: var(--accent-green) !important;
            background: rgba(0, 210, 132, 0.05) !important;
        }
        input[type="file"]::file-selector-button {
            background: linear-gradient(135deg, rgba(88,166,255,0.1) 0%, rgba(88,166,255,0.3) 100%) !important;
            border: 1px solid var(--accent-blue) !important;
            color: #fff !important;
            padding: 8px 16px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            font-weight: bold !important;
            transition: all 0.3s ease !important;
            margin-right: 15px;
        }
        input[type="file"]::file-selector-button:hover {
            background: linear-gradient(135deg, rgba(88,166,255,0.3) 0%, rgba(88,166,255,0.6) 100%) !important;
            box-shadow: 0 0 15px rgba(88,166,255,0.6) !important;
            transform: translateY(-1px);
        }

        /* ABAS GLOBAIS (sys-tabs) */
        .sys-tabs { 
            border-bottom: 1px solid var(--border) !important; 
            display: flex; gap: 15px; margin-bottom: 25px; padding-bottom: 0 !important;
        }
        .sys-tab { 
            background: transparent !important; 
            border: none !important; 
            color: var(--text-muted) !important; 
            padding: 10px 20px !important; 
            font-size: 13px !important; 
            cursor: pointer !important; 
            transition: all 0.3s ease !important;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            border-bottom: 3px solid transparent !important;
            border-radius: 0 !important;
        }
        .sys-tab:hover { 
            color: var(--accent-blue) !important; 
            text-shadow: 0 0 8px rgba(88, 166, 255, 0.4) !important; 
            border-bottom-color: rgba(88, 166, 255, 0.5) !important;
        }
        .sys-tab.active { 
            color: var(--accent-green) !important; 
            border-bottom: 3px solid var(--accent-green) !important;
            text-shadow: 0 0 10px rgba(0, 210, 132, 0.5) !important;
        }
        
        .sys-content { display: none; }
        .sys-content.active { display: block; }

        /* LINKS E DIVISORES UNIVERSAIS */
        a { color: var(--accent-blue); text-decoration: none; transition: color 0.2s ease; }
        a:hover { color: var(--accent-green); text-shadow: 0 0 5px rgba(0, 210, 132, 0.4); }
        hr { border: 0; height: 1px; background: var(--border) !important; margin: 20px 0; }
        
        /* SCROLL E HIGHLIGHT DE SELEÇÃO */
        ::selection { background: var(--accent-blue); color: #fff; }

    </style>
    <!-- JS Kernel (DS) -->
    <script>window.DS_BASE_URL = '<?= BASE_URL ?>/';</script>
    <script src="<?= BASE_URL ?>/assets/js/core/ds.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/core/events.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/core/api.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/core/toast.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/password-meter.js"></script>
</head>
<body>
    <div id="adminmenuback">
        <div style="padding: 25px 15px; text-align: center; border-bottom: 1px solid var(--border);">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="CockPit OS" style="max-width: 100%; height: auto; max-height: 100px; filter: drop-shadow(0 0 15px rgba(0,210,132,0.1)); border-radius: 8px;">
        </div>
        <ul id="adminmenu">
            <?php 
                $userRole = $_SESSION['user_role'] ?? 'admin';
                
                if ($userRole === 'admin' || $userRole === 'receptionist') {
                    echo '<li><a href="' . BASE_URL . '/admin"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg> Painel</a></li>';
                }

                if (isset($this->dispatcher)) { 
                    $menus = $this->dispatcher->applyFilters('admin.menu', [], $userRole);
                    foreach ($menus as $menu) {
                        $icon = $menu['icon'] ?? '';
                        $hasSub = isset($menu['submenu']) && is_array($menu['submenu']);
                        $liClass = $hasSub ? 'class="has-submenu"' : '';
                        echo '<li ' . $liClass . '><a href="' . htmlspecialchars(ltrim($menu['url'] ?? '#', '/')) . '">' . $icon . ' ' . htmlspecialchars($menu['title']) . '</a>';
                        if ($hasSub) {
                            echo '<ul style="list-style: none; padding-left: 15px; margin: 0; display: none;">';
                            foreach ($menu['submenu'] as $sub) {
                                $subIcon = $sub['icon'] ?? '';
                                echo '<li><a href="' . htmlspecialchars(ltrim($sub['url'] ?? '#', '/')) . '">' . $subIcon . ' ' . htmlspecialchars($sub['title']) . '</a></li>';
                            }
                            echo '</ul>';
                        }
                        echo '</li>';
                    } 
                }

                if ($userRole === 'admin') {
                    echo '<li><a href="admin/plugins"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"></path></svg> Módulos (Plugins)</a></li>';
                    echo '<li><a href="admin/themes"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg> Temas</a></li>';
                }
            ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <li style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 10px;">
                <a href="#" style="color: var(--text-muted); cursor: default; font-size: 12px; text-transform: uppercase;">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></a>
            </li>
            <li><a href="logout" style="color: var(--accent-orange);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> Encerrar Sessão</a></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div id="wpcontent">
        <div class="wrap">
            <?= $content ?? '' ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hasSubmenuLinks = document.querySelectorAll('.has-submenu > a');
            const currentPath = window.location.pathname.replace(/\/$/, "");
            
            let activeLinkFound = false;
            document.querySelectorAll('#adminmenu a').forEach(link => {
                let href = link.getAttribute('href');
                if (!href.startsWith('http')) {
                    href = '<?= BASE_URL ?>/' + href;
                }
                
                let linkPath = new URL(href, window.location.origin).pathname.replace(/\/$/, "");
                
                if (linkPath === currentPath) {
                    link.parentElement.classList.add('current');
                    let parentUl = link.closest('ul[style*="none"]');
                    if(parentUl) {
                        parentUl.style.display = 'block';
                        localStorage.setItem('openMenu_' + parentUl.previousElementSibling.textContent.trim(), 'open');
                        activeLinkFound = true;
                    }
                }
            });

            hasSubmenuLinks.forEach(link => {
                const parent = link.parentElement;
                const ul = parent.querySelector('ul');
                const menuTitle = link.textContent.trim();
                
                if (localStorage.getItem('openMenu_' + menuTitle) === 'open') {
                    ul.style.display = 'block';
                }

                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#' || this.getAttribute('href') === 'admin/clinic' || this.getAttribute('href') === 'admin/ai-hub') {
                        e.preventDefault();
                        if (ul) {
                            if (ul.style.display === 'none') {
                                ul.style.display = 'block';
                                localStorage.setItem('openMenu_' + menuTitle, 'open');
                            } else {
                                ul.style.display = 'none';
                                localStorage.setItem('openMenu_' + menuTitle, 'closed');
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
