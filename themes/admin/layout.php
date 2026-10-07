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

        input[type="submit"]:hover, .btn:hover, .button:hover, .page-title-action:hover {
            background: linear-gradient(135deg, rgba(88,166,255,0.3) 0%, rgba(88,166,255,0.6) 100%) !important;
            box-shadow: 0 0 20px rgba(88,166,255,0.6), inset 0 0 10px rgba(255,255,255,0.2) !important;
            transform: translateY(-2px);
        }
        
        input[type="submit"]:active, .btn:active, .button:active, .page-title-action:active {
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

        /* WIDGETS */
        .widget-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; max-height: 380px; min-height: 200px; box-sizing: border-box; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; }
        .widget-card:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); border-color: var(--accent-blue); }
        .widget-header { border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 5px; flex-shrink: 0; }
        .widget-title { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .widget-body { flex: 1; font-size: 14px; overflow-y: auto; overflow-x: hidden; padding-right: 5px; }
        
        /* Custom Scrollbar for Widgets */
        .widget-body::-webkit-scrollbar { width: 6px; }
        .widget-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
        .widget-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .widget-body::-webkit-scrollbar-thumb:hover { background: var(--accent-blue); }
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
                    let parentUl = link.parentElement.parentElement;
                    if(parentUl && parentUl.tagName === 'UL' && parentUl.id !== 'adminmenu') {
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
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                        if (ul) {
                            if (ul.style.display === 'none' || ul.style.display === '') {
                                ul.style.display = 'block';
                                localStorage.setItem('openMenu_' + menuTitle, 'open');
                            } else {
                                ul.style.display = 'none';
                                localStorage.setItem('openMenu_' + menuTitle, 'closed');
                            }
                        }
                    } else {
                        // Para links reais, apenas garante que abra no próximo reload
                        localStorage.setItem('openMenu_' + menuTitle, 'open');
                    }
                });
            });
        });
    </script>
        <?php
        // Notificações agora são injetadas pelo Workspace Wrapper
    if (!isset($unreadNotifs)) $unreadNotifs = [];
    if (!isset($allNotifs)) $allNotifs = [];
    // Nota: O markAsRead() foi transferido ou deve ser gerenciado por API/Background.
    ?>
    <!-- OS Notification System (Hub & Toasts) -->
    <style>
        /* Toast Container */
        #os-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        /* Notification Hub Button */
        #os-notif-hub-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #1e293b;
            border: 1px solid #334155;
            color: #94a3b8;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 99999;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        #os-notif-hub-btn:hover { background: #334155; color: #fff; }
        
        .os-notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            display: none;
        }

        /* Notification Flyout Panel */
        #os-notif-panel {
            position: fixed;
            bottom: 75px;
            right: 20px;
            width: 350px;
            max-height: 450px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 99998;
            display: none;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        #os-notif-panel.open {
            display: flex;
            transform: translateY(0);
            opacity: 1;
        }

        .os-notif-header {
            padding: 15px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
        }
        .os-notif-header h3 { margin: 0; font-size: 14px; color: #f8fafc; }
        .os-notif-clear { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 12px; }
        .os-notif-clear:hover { color: #fff; text-decoration: underline; }

        .os-notif-body {
            padding: 10px;
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .os-notif-item {
            background: #1e293b;
            border-radius: 6px;
            padding: 12px;
            font-size: 13px;
            color: #cbd5e1;
            border-left: 4px solid #3b82f6;
            line-height: 1.4;
        }
        .os-notif-item.success { border-left-color: #10b981; }
        .os-notif-item.error { border-left-color: #ef4444; }
        .os-notif-item.warning { border-left-color: #f59e0b; }
        .os-notif-time { font-size: 11px; color: #64748b; margin-top: 5px; display: block; }

        @keyframes ring-bell {
            0% { transform: rotate(0); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-15deg); }
            30% { transform: rotate(10deg); }
            40% { transform: rotate(-10deg); }
            50% { transform: rotate(5deg); }
            60% { transform: rotate(-5deg); }
            70% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }
        .os-ringing {
            animation: ring-bell 0.8s ease-in-out;
            transform-origin: top center;
            color: #ef4444; /* Fica vermelho enquanto bate */
        }
    </style>

    <div id="os-toast-container"></div>
    
    <div id="os-notif-panel">
        <div class="os-notif-header">
            <h3>Notificações</h3>
            <button class="os-notif-clear" onclick="OS.clearHub()">Limpar Fila</button>
        </div>
        <div class="os-notif-body" id="os-notif-list">
            <!-- Items go here -->
        </div>
    </div>

    <button id="os-notif-hub-btn" onclick="OS.toggleHub()">
        <svg id="os-notif-bell" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <div class="os-notif-badge" id="os-notif-badge">0</div>
    </button>

    <script>
        window.OS = window.OS || {};
        
        window.OS.confirm = function(message, onConfirm) {
            const overlay = document.createElement('div');
            overlay.style = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 999999; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.2s;';
            
            const box = document.createElement('div');
            box.style = 'background: #1e1e2d; border: 1px solid #323248; border-radius: 8px; padding: 25px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transform: scale(0.9); transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);';
            
            const text = document.createElement('p');
            text.style = 'color: #fff; font-size: 16px; margin-bottom: 25px; line-height: 1.5;';
            text.innerText = message;
            
            const btnGroup = document.createElement('div');
            btnGroup.style = 'display: flex; justify-content: center; gap: 15px;';
            
            const btnCancel = document.createElement('button');
            btnCancel.innerText = 'Cancelar';
            btnCancel.style = 'background: transparent; border: 1px solid #4a4a6a; color: #a1a1b5; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;';
            btnCancel.onclick = function() {
                overlay.style.opacity = '0';
                box.style.transform = 'scale(0.9)';
                setTimeout(() => overlay.remove(), 200);
            };
            
            const btnConfirm = document.createElement('button');
            btnConfirm.innerText = 'Confirmar';
            btnConfirm.style = 'background: #45f3ff; border: none; color: #0b0c10; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;';
            btnConfirm.onclick = function() {
                overlay.style.opacity = '0';
                box.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    overlay.remove();
                    if(onConfirm) onConfirm();
                }, 200);
            };
            
            btnGroup.appendChild(btnCancel);
            btnGroup.appendChild(btnConfirm);
            box.appendChild(text);
            box.appendChild(btnGroup);
            overlay.appendChild(box);
            document.body.appendChild(overlay);
            
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                box.style.transform = 'scale(1)';
            });
        };
        
        let notifQueue = [];
        let unreadCount = <?= !empty($unreadNotifs) ? count($unreadNotifs) : 0 ?>;
        
        // Exibe a badge no load inicial se houver não lidas e abre o painel
        window.addEventListener('DOMContentLoaded', () => {
            if (unreadCount > 0) {
                const b = document.getElementById('os-notif-badge');
                b.innerText = unreadCount;
                b.style.display = 'block';

            }
        });

        // Toggles the flyout panel
        window.OS.toggleHub = function() {
            const panel = document.getElementById('os-notif-panel');
            if (panel.classList.contains('open')) {
                panel.classList.remove('open');
                setTimeout(() => panel.style.display = 'none', 200); // Wait for transition
            } else {
                panel.style.display = 'flex';
                // Reset unread count when opening
                unreadCount = 0;
                document.getElementById('os-notif-badge').style.display = 'none';
                
                requestAnimationFrame(() => {
                    panel.classList.add('open');
                });
            }
        };

        window.OS.clearHub = function() {
            document.getElementById('os-notif-list').innerHTML = '<div style="text-align:center; padding: 20px; color: #64748b;">Nenhuma notificação na fila.</div>';
            notifQueue = [];
            unreadCount = 0;
            document.getElementById('os-notif-badge').style.display = 'none';
            
            // Apaga de verdade no backend
            const fd = new FormData();
            fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            fetch('<?= BASE_URL ?>/api/os/notifications/clear', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?? '' ?>' },
                body: fd
            });
        };

        // Adds to hub history
        window.OS.addToHub = function(message, type) {
            const list = document.getElementById('os-notif-list');
            
            // Remove empty state text if present
            if (notifQueue.length === 0) {
                list.innerHTML = '';
            }

            const item = document.createElement('div');
            item.className = 'os-notif-item ' + type;
            item.innerHTML = `<strong>${type.toUpperCase()}</strong><br><span class="os-msg-content"></span><span class="os-notif-time">Agora mesmo</span>`;
            item.querySelector('.os-msg-content').textContent = message;
            
            // Add to top
            list.prepend(item);
            notifQueue.unshift({message, type});
            
            // Limit to 50 items in DOM
            if (list.children.length > 50) {
                list.lastChild.remove();
            }

            // Increment badge se não for carga histórica
            if (!arguments[2]) {
                const panel = document.getElementById('os-notif-panel');
                if (!panel.classList.contains('open')) {
                    unreadCount++;
                    const badge = document.getElementById('os-notif-badge');
                    badge.innerText = unreadCount;
                    badge.style.display = 'block';
                    
                    // Anima o sino!
                    const bell = document.getElementById('os-notif-bell');
                    bell.classList.remove('os-ringing');
                    void bell.offsetWidth; // trigger reflow
                    bell.classList.add('os-ringing');
                }
            }
        };

        window.OS.notify = function(message, type = 'success', skipHub = false) {
            const container = document.getElementById('os-toast-container');
            if (!container) return;
            
            // Add to Notification Hub History
            if (!skipHub) {
                window.OS.addToHub(message, type);
            }

            // Render temporary Toast (Auto-hides)
            const toast = document.createElement('div');
            let color = '#3b82f6';
            let icon = 'ℹ️';
            if (type === 'success') { color = '#10b981'; icon = '✅'; }
            if (type === 'error') { color = '#ef4444'; icon = '🚨'; }
            if (type === 'warning') { color = '#f59e0b'; icon = '⚠️'; }
            
            toast.style = `background: #0f172a; color: #f8fafc; padding: 16px 20px; border-radius: 8px; box-shadow: 0 15px 35px rgba(0,0,0,0.6); opacity: 0; transform: translateX(50px); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-weight: 600; font-size: 14px; display: flex; align-items: flex-start; gap: 15px; border: 1px solid #334155; border-left: 6px solid ${color}; letter-spacing: 0.3px; max-width: 450px; line-height: 1.5; cursor: pointer;`;
            
            toast.innerHTML = `
                <span style="font-size: 18px; margin-top: -2px;">${icon}</span> 
                <span class="os-msg-content" style="flex-grow: 1;"></span>
                <button style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:0; font-size:16px; margin-top:-2px;" onclick="this.parentElement.style.opacity='0'; setTimeout(()=>this.parentElement.remove(), 300);">✖</button>
            `;
            toast.querySelector('.os-msg-content').textContent = message;
            
            container.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            });
            
            // Se for erro, fica 15 segundos ou até clicar. Se for sucesso, 5 segundos.
            const delay = (type === 'success') ? 5000 : 15000;
            
            setTimeout(() => {
                if(toast.parentElement) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(50px)';
                    setTimeout(() => toast.remove(), 400);
                }
            }, delay);
        };
    </script>
    

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (empty($allNotifs)): ?>
                document.getElementById('os-notif-list').innerHTML = '<div style="text-align:center; padding: 20px; color: #64748b;">Nenhuma notificação na fila.</div>';
            <?php else: ?>
                <?php foreach (array_reverse($allNotifs) as $n): ?>
                    // Apenas popula no hub em background
                    window.OS.addToHub(<?= json_encode($n['message']) ?>, <?= json_encode($n['type']) ?>, true);
                <?php endforeach; ?>
            <?php endif; ?>

            // Renderiza Flash Messages legadas como Toasts
            <?php if (isset($_SESSION['flash_message'])): ?>
                setTimeout(() => {
                    OS.notify(<?= json_encode($_SESSION['flash_message']['msg']) ?>, <?= json_encode($_SESSION['flash_message']['type']) ?>);
                }, 100);
            <?php unset($_SESSION['flash_message']); endif; ?>

            // Dispara toast de destaque APENAS para Sucesso ou Erro Crítico (Instalação/Quebra)
            <?php foreach (array_reverse($unreadNotifs) as $n): ?>
            <?php if ($n['type'] === 'error' || $n['type'] === 'warning' || $n['type'] === 'success'): ?>
            setTimeout(() => {
                OS.notify(<?= json_encode($n['message']) ?>, <?= json_encode($n['type']) ?>, true);
            }, 500);
            <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>
</body>
</html>