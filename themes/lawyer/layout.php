<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Jurídico</title>
    <style>
        body { margin: 0; font-family: "Georgia", serif; background: #1a1a1a; color: #f4f4f4; display: flex; height: 100vh; overflow: hidden; }
        
        #lawyer-menu { width: 250px; background: #0a0a0a; border-right: 2px solid #d4af37; height: 100%; display: flex; flex-direction: column; }
        
        .logo-area { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(212,175,55,0.2); }
        .logo-area h2 { color: #d4af37; margin: 0; font-weight: normal; letter-spacing: 2px; }
        .logo-area span { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 4px; }
        
        .nav-items { flex-grow: 1; padding: 20px 0; margin: 0; list-style: none; }
        .nav-items li a { display: block; padding: 15px 25px; color: #ccc; text-decoration: none; font-size: 16px; border-left: 3px solid transparent; transition: all 0.3s ease; }
        .nav-items li a:hover { background: rgba(212,175,55,0.1); color: #d4af37; border-left: 3px solid #d4af37; }
        
        .user-profile { padding: 20px; text-align: center; border-top: 1px solid rgba(212,175,55,0.2); background: #111; }
        .user-profile a { color: #d4af37; text-decoration: none; font-size: 12px; margin-top: 10px; display: inline-block; }

        #main-workspace { flex-grow: 1; padding: 40px; overflow-y: auto; background: url('https://www.transparenttextures.com/patterns/stardust.png'); }
    </style>
</head>
<body>
    <div id="lawyer-menu">
        <div class="logo-area">
            <h2>SPECTER LITT</h2>
            <span>Advogados Associados</span>
        </div>
        <ul class="nav-items">
            <li><a href="<?= BASE_URL ?>/admin/legal">⚖️ Meus Processos</a></li>
            <li><a href="<?= BASE_URL ?>/admin/legal">📅 Prazos</a></li>
        </ul>
        <div class="user-profile">
            <div style="color: #fff; font-weight: bold;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Advogado') ?></div>
            <a href="<?= BASE_URL ?>/logout">Desconectar</a>
        </div>
    </div>
    
    <div id="main-workspace">
        <?= $content ?? '' ?>
    </div>
</body>
</html>
