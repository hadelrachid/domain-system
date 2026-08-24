<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepção - Daher Clínica</title>
    <base href="<?= defined('BASE_URL') && BASE_URL ? BASE_URL . '/' : '/' ?>">
    <style>
        body { margin: 0; font-family: "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #334155; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Clean */
        #reception-menu { width: 220px; background: #ffffff; border-right: 1px solid #e2e8f0; height: 100%; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.02); }
        
        .logo-area { padding: 30px 20px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        .logo-area img { max-width: 150px; filter: invert(1); /* Inverte o logo branco para ficar visível no fundo claro */ }
        
        .nav-items { flex-grow: 1; padding: 20px 0; margin: 0; list-style: none; }
        .nav-items li a { display: flex; align-items: center; gap: 12px; padding: 15px 25px; color: #64748b; text-decoration: none; font-size: 15px; font-weight: 500; transition: all 0.2s ease; }
        .nav-items li a:hover { background: #f1f5f9; color: #8b5cf6; border-left: 3px solid #8b5cf6; }
        
        .user-profile { padding: 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; }
        .user-profile a { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold; margin-top: 10px; display: inline-block; }

        /* Main Content */
        #main-workspace { flex-grow: 1; padding: 40px; overflow-y: auto; background: #f8fafc; }
        
        /* Table Styles for Reception */
        .wp-list-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .wp-list-table th, .wp-list-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f5f9; color: #475569; }
        .wp-list-table th { background: #f8fafc; font-weight: 600; color: #8b5cf6; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        .wp-list-table tr:hover { background: #f8fafc; }
        
        h1 { font-size: 28px; font-weight: 600; margin-bottom: 30px; color: #1e293b; }
    </style>
</head>
<body>
    <div id="reception-menu">
        <div class="logo-area">
            <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Daher Clínica">
        </div>
        <ul class="nav-items">
            <li><a href="admin/appointments">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Fila de Espera
            </a></li>
            <li><a href="admin/appointments/history">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Todos Agendamentos
            </a></li>
        </ul>
        <div class="user-profile">
            <div style="color: #334155; font-weight: bold;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Recepção') ?></div>
            <div style="color: #94a3b8; font-size: 12px; margin-top: 5px;">Workspace Recepção</div>
            <a href="logout">Desconectar</a>
        </div>
    </div>
    
    <div id="main-workspace">
        <?= $content ?? '' ?>
    </div>
</body>
</html>
