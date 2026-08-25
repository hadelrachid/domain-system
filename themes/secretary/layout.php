<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Recepção</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fdfbf7; display: flex; height: 100vh; }
        #sidebar { width: 220px; background: #2c1810; color: #fff; height: 100%; position: fixed; }
        #sidebar .logo { padding: 20px; text-align: center; border-bottom: 1px solid #4a2c20; font-weight: bold; font-size: 18px; color: #e2c094; }
        #menu { padding: 0; margin: 0; list-style: none; }
        #menu li a { display: flex; align-items: center; gap: 10px; padding: 15px 20px; color: #d4ccb8; text-decoration: none; font-size: 15px; transition: all 0.2s; border-left: 3px solid transparent; }
        #menu li a:hover { background: #4a2c20; color: #fff; border-left-color: #e2c094; }
        #content { margin-left: 220px; padding: 30px; width: calc(100% - 220px); box-sizing: border-box; overflow-y: auto; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #efeae1; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #8b5e34; }
        .btn-primary:hover { background: #6f4b29; }
        h1 { margin-top: 0; color: #2c1810; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #efeae1; }
        th { background: #f8f6f0; color: #5a4b41; font-weight: 600; }
    </style>
</head>
<body>
    <div id="sidebar">
        <div class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Recepção
        </div>
        <ul id="menu">
            <li><a href="<?= BASE_URL ?>/admin">Início</a></li>
            <?php 
                $userRole = $_SESSION['user_role'] ?? 'receptionist';
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
