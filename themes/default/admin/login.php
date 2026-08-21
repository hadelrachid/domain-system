<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Cockpit - Autenticação</title>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { margin-top: 0; color: #333; text-align: center; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; text-align: center; }
        label { display: block; margin-bottom: 5px; color: #666; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: bold; }
        button:hover { background: #2563eb; }
        .timer-box { font-weight: bold; color: red; }
    </style>
</head>
<body>
    <div class='login-box'>
        <h1>Autenticação</h1>
        <?php if (!empty($error)): ?>
            <div class='error'><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method='POST' action='<?= BASE_URL ?>/login'>
            
            <?php if (isset($_SESSION['pending_2fa_email'])): ?>
                
                <p style="text-align: center; font-size: 14px; margin-bottom: 20px;">
                    <?php if (($_SESSION['pending_2fa_type'] ?? '') === 'email'): ?>
                        Enviamos um código para o seu e-mail.<br>
                        Tempo restante: <span id="timer" class="timer-box">05:00</span>
                    <?php else: ?>
                        Abra o aplicativo Google Authenticator e digite o código atual.
                    <?php endif; ?>
                </p>

                <label>E-mail</label>
                <input type='email' name='email' value="<?= htmlspecialchars($_SESSION['pending_2fa_email']) ?>" readonly style="background: #eee;">
                
                <input type='hidden' name='password' value="hidden">

                <label style="color: #3b82f6;">Código 2FA (6 dígitos)</label>
                <input type="text" name="twofa_code" id="twofa_code" maxlength="6" required autofocus placeholder="000000" style="font-size: 24px; text-align: center; letter-spacing: 5px; border: 2px solid #3b82f6;">

                <?php if (($_SESSION['pending_2fa_type'] ?? '') === 'email'): ?>
                    <div id="resend_container" style="text-align: center; margin-bottom: 20px;">
                        <a href="#" onclick="document.getElementById('twofa_code').removeAttribute('required'); document.getElementById('twofa_code').value=''; document.forms[0].submit(); return false;" style="font-size: 13px; color: #3b82f6; text-decoration: none; font-weight: bold;">🔄 Reenviar Código por E-mail</a>
                    </div>
                    <script>
                        // O PHP diz se expirou no servidor. O JS cuida apenas de travar a tela se ficar 5 min aberta.
                        var timeLeft = 300; // 5 minutos
                        var timerEl = document.getElementById('timer');
                        var codeInput = document.getElementById('twofa_code');
                        var btnSubmit = document.getElementById('btn_submit');
                        
                        var countdown = setInterval(function() {
                            timeLeft--;
                            if (timeLeft < 0) return;
                            
                            var m = Math.floor(timeLeft / 60);
                            var s = timeLeft % 60;
                            timerEl.innerText = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                            
                            if (timeLeft === 0) {
                                clearInterval(countdown);
                                timerEl.innerText = "Expirado! Solicite um novo.";
                                codeInput.disabled = true;
                                btnSubmit.disabled = true;
                                btnSubmit.style.opacity = '0.5';
                            }
                        }, 1000);
                    </script>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?= BASE_URL ?>/logout" style="display: block; padding: 10px; background-color: #f1f1f1; color: #333; text-decoration: none; border-radius: 4px; font-weight: bold; border: 1px solid #ccc;">&larr; Voltar (Acessar com outra conta)</a>
                </div>

            <?php else: ?>
                <label>E-mail</label>
                <input type='email' name='email' required autofocus>
                
                <label>Senha</label>
                <div style="position: relative;">
                    <input type='password' name='password' id='login_password' required style="padding-right: 40px;">
                    <button type="button" onclick="togglePassword('login_password', this)" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; background: transparent; border: none; color: #666; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <script>
                    function togglePassword(inputId, btn) {
                        var input = document.getElementById(inputId);
                        var svg = btn.querySelector('svg');
                        if (input.type === 'password') {
                            input.type = 'text';
                            svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                        } else {
                            input.type = 'password';
                            svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                        }
                    }
                </script>
            <?php endif; ?>
            
            <button type='submit' id="btn_submit">Entrar no Cockpit</button>
        </form>
    </div>
</body>
</html>
