<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Médico - Daher Clínica</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <style>
        body { margin: 0; font-family: "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Glassmorphism */
        #doctor-menu { width: 220px; background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border-right: 1px solid rgba(255,255,255,0.1); height: 100%; display: flex; flex-direction: column; }
        
        .logo-area { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo-area img { max-width: 150px; }
        
        .nav-items { flex-grow: 1; padding: 20px 0; margin: 0; list-style: none; }
        .nav-items li a { display: flex; align-items: center; gap: 12px; padding: 15px 25px; color: #cbd5e1; text-decoration: none; font-size: 15px; transition: all 0.3s ease; }
        .nav-items li a:hover { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-left: 3px solid #38bdf8; }
        
        .user-profile { padding: 20px; background: rgba(15, 23, 42, 0.5); border-top: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .user-profile a { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold; margin-top: 10px; display: inline-block; }

        /* Main Content */
        #main-workspace { flex-grow: 1; padding: 40px; overflow-y: auto; background: radial-gradient(circle at top right, #1e293b, #0f172a); }
        
        /* Table Styles for the Doctor */
        .wp-list-table { width: 100%; border-collapse: collapse; background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(8px); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
        .wp-list-table th, .wp-list-table td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #e2e8f0; }
        .wp-list-table th { background: rgba(15, 23, 42, 0.8); font-weight: 600; color: #38bdf8; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        .wp-list-table tr:hover { background: rgba(255,255,255,0.02); }
        
        h1 { font-size: 28px; font-weight: 300; margin-bottom: 30px; color: #fff; }
    </style>
</head>
<body>
    <div id="doctor-menu">
        <div class="logo-area">
            <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Daher Clínica">
        </div>
        <ul class="nav-items">
            <li><a href="admin/appointments/history">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Prontuários / Fila
            </a></li>
        </ul>
        <div class="user-profile">
            <div style="color: #fff; font-weight: bold;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Médico') ?></div>
            <div style="color: #94a3b8; font-size: 12px; margin-top: 5px;">Workspace Médico</div>
            <a href="logout">Desconectar</a>
        </div>
    </div>
    
    <div id="main-workspace">
        <?= $content ?? '' ?>
    </div>
</body>
</html>
