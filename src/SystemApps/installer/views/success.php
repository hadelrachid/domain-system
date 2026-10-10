<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação Concluída - Domain-System</title>
    <style>
        body { background-color: #0b1120; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #f8fafc; background-image: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%); padding: 40px 20px; box-sizing: border-box; }
        .setup-box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 1.5rem 2rem 2.5rem 2rem; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 20px rgba(59, 130, 246, 0.1); width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.05); }
        h1 { font-size: 22px; font-weight: 400; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; color: #4ade80; text-align: center; }
        p { color: #94a3b8; font-size: 0.95rem; text-align: center; margin-bottom: 25px; line-height: 1.5; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); margin-top: 10px; display: block; text-align: center; text-decoration: none; box-sizing: border-box;}
        .btn:hover { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5); transform: translateY(-1px); }
        .alert { background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        .warning { background: rgba(234, 179, 8, 0.1); color: #fef08a; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; text-align: left; border: 1px solid rgba(234, 179, 8, 0.3); line-height: 1.5; }
        .logo-container { text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="setup-box">
    <div class="logo-container">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" style="max-width: 150px; height: auto; filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.3));">
    </div>
    
    <h1>🎉 Instalação Concluída!</h1>
    
    <?php if (!empty($existingAdmin)): ?>
        <div class="warning">
            <strong style="color: #fbbf24;">Aviso:</strong> Detectamos que o banco de dados já possuía um Administrador configurado. Por segurança, os dados do usuário que você digitou no formulário foram ignorados e a conta original foi preservada.
        </div>
    <?php else: ?>
        <p>O sistema foi configurado com sucesso e o instalador foi selado por segurança <code>(config/installed.lock)</code>.</p>
    <?php endif; ?>

    <p style="color: #f8fafc; font-weight: 500;">Você já pode acessar o Painel de Controle e começar a usar seu sistema.</p>
    
    <a href="<?= BASE_URL ?>/login" class="btn">Ir para o Login do Painel ↗</a>
</div>

</body>
</html>
