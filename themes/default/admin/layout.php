<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain-System Admin</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f0f0f1; display: flex; height: 100vh; }
        #adminmenuback { width: 160px; background: #111; color: #fff; height: 100%; position: fixed; border-right: 1px solid #2c3338; }
        #adminmenu { padding: 0; margin: 0; list-style: none; }
        #adminmenu li a { display: flex; align-items: center; gap: 8px; padding: 12px 15px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        #adminmenu li a:hover { background: rgba(255,255,255,0.05); color: #f8fafc; }
        #adminmenu li.current a { background: #2271b1; color: #fff; font-weight: 600; }
        #wpcontent { margin-left: 160px; padding: 20px; width: calc(100% - 160px); overflow-y: auto; }
        h1 { font-size: 23px; font-weight: 400; margin: 0 0 20px; color: #1d2327; }
        .wrap { max-width: 1000px; }
        /* Forms & Tables */
        table.wp-list-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #c3c4c7; }
        th { font-weight: 600; background: #f6f7f7; }
        .btn { padding: 4px 8px; border: 1px solid; border-radius: 3px; cursor: pointer; text-decoration: none; font-size: 13px; display: inline-block; }
        .btn-activate { background: #f6f7f7; border-color: #2271b1; color: #2271b1; }
        .btn-deactivate { background: #f6f7f7; border-color: #d63638; color: #d63638; }
        .btn-core { background: #e0e0e0; border-color: #ccc; color: #666; cursor: not-allowed; }
        .badge { font-size: 11px; padding: 2px 6px; border-radius: 10px; background: #000; color: #fff; margin-left: 5px; }
        .upload-box { background: #fff; padding: 20px; border: 1px dashed #c3c4c7; margin-bottom: 20px; }
        .page-title-action { display: inline-block; margin-left: 10px; padding: 4px 8px; font-size: 13px; text-decoration: none; border: 1px solid #2271b1; color: #2271b1; border-radius: 3px; }
    </style>
</head>
<body>
    <div id="adminmenuback">
        <div style="padding: 20px 15px; background: #111; text-align: center; border-bottom: 1px solid #2c3338;">
            <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Cockpit Logo" style="max-width: 130px; height: auto;">
        </div>
        <ul id="adminmenu">
            <li><a href="admin"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg> Painel</a></li>
            <?php 
                $app = \DomainSystem\Core\Application::getInstance();
                if ($app) {
                    $menus = $app->getDispatcher()->applyFilters('admin.menu', []);
                    foreach ($menus as $menu) {
                        $icon = $menu['icon'] ?? '';
                        echo '<li><a href="' . htmlspecialchars(ltrim($menu['url'], '/')) . '">' . $icon . ' ' . htmlspecialchars($menu['title']) . '</a></li>';
                    }
                }
            ?>
            <li><a href="admin/plugins"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"></path></svg> Plugins</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <li style="margin-top: 50px; border-top: 1px solid #2c3338;">
                <a href="#" style="color: #999; cursor: default;">Olá, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></a>
            </li>
            <li><a href="logout" style="color: #d63638;">Sair (Logout)</a></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div id="wpcontent">
        <div class="wrap">
            <?= $content ?? '' ?>
        </div>
    </div>
</body>
</html>
