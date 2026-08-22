<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Cockpit - Autenticação</title>
    <style>
        body { background-color: #0b1120; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #f8fafc; background-image: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%); }
        .login-box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 2.5rem 2rem; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 20px rgba(59, 130, 246, 0.1); width: 100%; max-width: 400px; border: 1px solid rgba(255,255,255,0.05); }
        .logo-container { text-align: center; margin-bottom: 30px; }
        .error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        input { width: 100%; padding: 12px 15px; margin-bottom: 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 6px; box-sizing: border-box; color: #f8fafc; transition: all 0.2s ease; }
        input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); margin-top: 10px; }
        .btn-submit:hover { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5); transform: translateY(-1px); }
        .btn-icon { background: transparent !important; border: none !important; box-shadow: none !important; color: #94a3b8; transition: color 0.2s; padding: 0; }
        .btn-icon:hover { color: #f8fafc; transform: none !important; }
        .timer-box { font-weight: bold; color: #fca5a5; }
    </style>
</head>
<body>
    <div class='login-box'>
        <div class="logo-container">
            <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Cockpit Logo" style="max-width: 160px; height: auto; filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.3));">
        </div>
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
                <input type='email' name='email' value="<?= htmlspecialchars($_SESSION['pending_2fa_email']) ?>" readonly style="background: rgba(15, 23, 42, 0.4); color: #64748b; border-color: #1e293b; cursor: not-allowed;">
                
                <input type='hidden' name='password' value="hidden">

                <label style="color: #60a5fa;">Código 2FA (6 dígitos)</label>
                <input type="text" name="twofa_code" id="twofa_code" maxlength="6" required autofocus placeholder="000000" style="font-size: 24px; text-align: center; letter-spacing: 5px; border: 2px solid #3b82f6; background: rgba(15, 23, 42, 0.8); color: #fff;">

                <?php if (($_SESSION['pending_2fa_type'] ?? '') === 'email'): ?>
                    <div id="resend_container" style="text-align: center; margin-bottom: 20px;">
                        <a href="#" onclick="document.getElementById('twofa_code').removeAttribute('required'); document.getElementById('twofa_code').value=''; document.forms[0].submit(); return false;" style="font-size: 13px; color: #60a5fa; text-decoration: none; font-weight: 600; transition: color 0.2s;">&#10227; Reenviar Código por E-mail</a>
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
                    <a href="<?= BASE_URL ?>/logout" style="display: block; padding: 10px; background-color: rgba(255, 255, 255, 0.05); color: #cbd5e1; text-decoration: none; border-radius: 6px; font-weight: 600; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s; font-size: 0.9rem;">&larr; Voltar (Acessar com outra conta)</a>
                </div>

            <?php else: ?>
                <label>E-mail</label>
                <input type='email' name='email' required autofocus>
                
                <label>Senha</label>
                <div style="position: relative;">
                    <input type='password' name='password' id='login_password' required style="padding-right: 40px;">
                    <button type="button" class="btn-icon" onclick="togglePassword('login_password', this)" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
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
            
            <button type='submit' id="btn_submit" class="btn-submit">Entrar no Cockpit</button>
        </form>
    </div>
</body>
</html>
