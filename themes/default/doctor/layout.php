<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Médico</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f4f8; display: flex; height: 100vh; }
        #sidebar { width: 220px; background: #0f172a; color: #fff; height: 100%; position: fixed; }
        #sidebar .logo { padding: 20px; text-align: center; border-bottom: 1px solid #1e293b; font-weight: bold; font-size: 18px; color: #38bdf8; }
        #menu { padding: 0; margin: 0; list-style: none; }
        #menu li a { display: flex; align-items: center; gap: 10px; padding: 15px 20px; color: #cbd5e1; text-decoration: none; font-size: 15px; transition: all 0.2s; border-left: 3px solid transparent; }
        #menu li a:hover { background: #1e293b; color: #f8fafc; border-left-color: #38bdf8; }
        #content { margin-left: 220px; padding: 30px; width: calc(100% - 220px); box-sizing: border-box; overflow-y: auto; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #0284c7; }
        .btn-primary:hover { background: #0369a1; }
        h1 { margin-top: 0; color: #0f172a; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
    </style>
</head>
<body>
    <div id="sidebar">
        <div class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Portal Médico
        </div>
        <ul id="menu">
            <li><a href="<?= BASE_URL ?>/admin">Resumo de Hoje</a></li>
            <?php 
                $userRole = $_SESSION['user_role'] ?? 'doctor';
                if (isset($this->dispatcher)) { 
                    $menus = $this->dispatcher->applyFilters('admin.menu', [], $userRole);
                    foreach ($menus as $menu) {
                        $icon = $menu['icon'] ?? '';
                        echo '<li><a href="' . htmlspecialchars(ltrim($menu['url'], '/')) . '">' . $icon . ' ' . htmlspecialchars($menu['title']) . '</a></li>';
                    } 
                }
            ?>
            <li style="margin-top: 40px;"><a href="logout" style="color: #ef4444;">Sair</a></li>
        </ul>
    </div>
    
    <div id="content">
        <?= $content ?? '' ?>
    </div>
</body>
</html>
