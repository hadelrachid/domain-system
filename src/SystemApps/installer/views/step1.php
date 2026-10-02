<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação do Sistema - Banco de Dados</title>
    <style>
        body { background-color: #0b1120; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #f8fafc; background-image: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%); padding: 40px 20px; box-sizing: border-box; }
        .setup-box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 1.5rem 2rem 2.5rem 2rem; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 20px rgba(59, 130, 246, 0.1); width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.05); }
        h1 { font-size: 22px; font-weight: 400; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; color: #f8fafc; text-align: center; }
        p { color: #94a3b8; font-size: 0.95rem; text-align: center; margin-bottom: 25px; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select { width: 100%; padding: 12px 15px; background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 6px; box-sizing: border-box; color: #f8fafc; transition: all 0.2s ease; }
        input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        option { background: #0f172a; color: #f8fafc; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); margin-top: 10px; display: block; text-align: center; }
        .btn:hover { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5); transform: translateY(-1px); }
        .alert { background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        .logo-container { text-align: center; margin-bottom: 15px; }
    </style>
    <script>
        function toggleDriver() {
            const driver = document.getElementById('db_driver').value;
            const mysqlFields = document.getElementById('mysql_fields');
            if (driver === 'sqlite') {
                mysqlFields.style.display = 'none';
            } else {
                mysqlFields.style.display = 'block';
            }
        }
    </script>
</head>
<body>

<div class="setup-box">
    <div class="logo-container">
        <img src="<?= BASE_URL ?>/setup/logo" alt="Logo" style="max-width: 150px; height: auto; filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.3));">
    </div>
    <h1>1. Conexão com Banco de Dados</h1>
    <p>Bem-vindo! Antes de iniciarmos, precisamos configurar o seu banco de dados.</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/setup/database">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="form-group">
            <label for="db_driver">Tipo de Banco de Dados</label>
            <select name="db_driver" id="db_driver" onchange="toggleDriver()">
                <option value="mysql">MySQL (Hostinger, cPanel, Localhost)</option>
                <option value="sqlite">SQLite (Para testes locais rápidos)</option>
            </select>
        </div>

        <div id="mysql_fields">
            <div class="form-group">
                <label for="db_host">Host (Servidor)</label>
                <input type="text" name="db_host" id="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label for="db_port">Porta</label>
                <input type="text" name="db_port" id="db_port" value="3306" required>
            </div>
            
            <div class="form-group">
                <label for="db_name">Nome do Banco de Dados</label>
                <input type="text" name="db_name" id="db_name" placeholder="ex: u979934992_agendamento_db">
            </div>
            
            <div class="form-group">
                <label for="db_user">Usuário do Banco</label>
                <input type="text" name="db_user" id="db_user" placeholder="ex: u979934992_admin">
            </div>
            
            <div class="form-group">
                <label for="db_pass">Senha do Banco</label>
                <div style="position: relative;">
                    <input type="password" name="db_pass" id="db_pass" style="padding-right: 40px; margin-bottom: 0;">
                    <span onclick="togglePassword('db_pass', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; font-size: 1.1rem;">&#128065;</span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn">Testar Conexão e Salvar</button>
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
