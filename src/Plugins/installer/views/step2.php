<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação do Sistema - Criar Administrador</title>
    <style>
        body { background-color: #0b1120; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #f8fafc; background-image: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%); padding: 40px 20px; box-sizing: border-box; }
        .setup-box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 1.5rem 2rem 2.5rem 2rem; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 20px rgba(59, 130, 246, 0.1); width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.05); }
        h1 { font-size: 22px; font-weight: 400; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; color: #4ade80; text-align: center; }
        p { color: #94a3b8; font-size: 0.95rem; text-align: center; margin-bottom: 25px; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select { width: 100%; padding: 12px 15px; background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 6px; box-sizing: border-box; color: #f8fafc; transition: all 0.2s ease; }
        input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); margin-top: 10px; display: block; text-align: center; }
        .btn:hover { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5); transform: translateY(-1px); }
        .alert { background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        .logo-container { text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="setup-box">
    <div class="logo-container">
        <img src="<?= BASE_URL ?>/setup/logo" alt="Logo" style="max-width: 150px; height: auto; filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.3));">
    </div>
    <h1>✅ Conexão Estabelecida!</h1>
    <p>O banco de dados respondeu perfeitamente. O arquivo <b>.env</b> foi gerado com sucesso.</p>
    
    <p>Agora, vamos rodar a instalação do sistema e criar sua conta de administrador primária.</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/setup/install">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="form-group">
            <label for="admin_name">Nome da Clínica (ou Seu Nome)</label>
            <input type="text" name="admin_name" id="admin_name" required placeholder="Ex: Clínica Saúde +">
        </div>
        
        <div class="form-group">
            <label for="admin_email">E-mail de Login</label>
            <input type="email" name="admin_email" id="admin_email" required placeholder="admin@clinica.com">
        </div>
        
        <div class="form-group">
            <label for="admin_pass">Senha</label>
            <div style="position: relative;">
                <input type="password" name="admin_pass" id="admin_pass" required style="padding-right: 40px; margin-bottom: 0;">
                <span onclick="togglePassword('admin_pass', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; font-size: 1.1rem;">&#128065;</span>
            </div>
        </div>

        <button type="submit" class="btn">Concluir Instalação</button>
    </form>
</div>

<script>
    function togglePassword(inputId, icon) {
        var input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.style.color = "#3b82f6";
        } else {
            input.type = "password";
            icon.style.color = "#94a3b8";
        }
    }
</script>
</body>
</html>


