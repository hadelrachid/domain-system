<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Cockpit - Autenticação</title>
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 { margin-top: 0; color: #333; text-align: center; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; }
        label { display: block; margin-bottom: 5px; color: #666; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: bold; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class='login-box'>
        <h1>Autenticação</h1>
        <?php if (!empty($error)): ?>
            <div class='error'><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method='POST' action='<?= BASE_URL ?>/login'>
            <label>E-mail</label>
            <input type='email' name='email' required autofocus>
            
            <label>Senha</label>
            <input type='password' name='password' required>
            
            <button type='submit'>Entrar no Cockpit</button>
        </form>
    </div>
    <script>
        // Limpa a mensagem de erro da sessão logo após renderizar
        fetch('<?= BASE_URL ?>/login', {method: 'POST', body: new URLSearchParams({clear_error: 1})}).catch(()=>null);
    </script>
</body>
</html>
