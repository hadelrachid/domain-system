<?php
/**
 * View: Cadastro do Aluno (Academy)
 * 100% Desacoplada e Isolada do Painel de Administração.
 *
 * @var string $baseUrl
 * @var string $csrfToken
 * @var string $redirect
 * @var string|array|null $error
 */
$baseUrl = $baseUrl ?? '';
$csrfToken = htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8');
$redirect = htmlspecialchars((string)($redirect ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta de Aluno | Academy LMS</title>
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: rgba(15, 20, 28, 0.95);
            --border-color: rgba(0, 210, 132, 0.3);
            --accent-cyan: #45f3ff;
            --accent-green: #00d284;
            --text-main: #f8fafc;
            --text-muted: #8c8f94;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            background: var(--bg-body);
            background-image: radial-gradient(circle at 50% 15%, rgba(0, 210, 132, 0.08) 0%, transparent 60%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .ds-student-auth-box {
            width: 100%;
            max-width: 500px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.6), 0 0 24px rgba(0, 210, 132, 0.08);
            backdrop-filter: blur(12px);
        }
        .ds-auth-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(0, 210, 132, 0.1);
            border: 1px solid rgba(0, 210, 132, 0.3);
            font-size: 26px;
            margin-bottom: 14px;
        }
        .ds-auth-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .ds-auth-header h1 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0 0 6px 0;
            letter-spacing: -0.02em;
        }
        .ds-auth-header p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin: 0;
        }
        .ds-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            line-height: 1.4;
        }
        .ds-alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #ef4444;
        }
        .ds-form-group {
            margin-bottom: 16px;
        }
        .ds-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }
        .ds-input {
            width: 100%;
            box-sizing: border-box;
            background: #0b0c10;
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 11px 14px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .ds-input:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(0, 210, 132, 0.2);
        }
        .ds-pass-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .ds-pass-wrapper .ds-input {
            padding-right: 44px;
        }
        .ds-toggle-pass-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            font-size: 1.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: color 0.2s, background 0.2s;
        }
        .ds-toggle-pass-btn:hover {
            color: var(--accent-cyan);
            background: rgba(255, 255, 255, 0.05);
        }
        .ds-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 480px) {
            .ds-grid-2 {
                grid-template-columns: 1fr;
            }
        }
        .ds-submit-btn {
            width: 100%;
            padding: 13px;
            border-radius: 8px;
            border: none;
            background: var(--accent-green);
            color: #0b0c10;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(0, 210, 132, 0.3);
            margin-top: 6px;
        }
        .ds-submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 210, 132, 0.4);
        }
        .ds-auth-links {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
        }
        .ds-auth-links a {
            color: var(--accent-cyan);
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }
        .ds-auth-links a:hover {
            text-decoration: underline;
        }
        .ds-back-link {
            margin-top: 14px;
            text-align: center;
        }
        .ds-back-link a {
            color: #64748b;
            font-size: 0.82rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .ds-back-link a:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="ds-student-auth-box">
        <div class="ds-auth-header">
            <div class="ds-auth-icon">✍️</div>
            <h1>Criar Conta de Aluno</h1>
            <p>Cadastre-se para acessar cursos, salvar seu progresso e certificados</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="ds-alert ds-alert-error">
                ⚠ <?= is_array($error) ? implode('<br>⚠ ', array_map('htmlspecialchars', $error)) : htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/aluno/cadastro">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= $redirect ?>">
            <?php endif; ?>

            <div class="ds-form-group">
                <label for="student-name">Nome Completo</label>
                <input type="text" id="student-name" name="name" required autofocus placeholder="Ex: HADEL RACHID DAHER JUNIOR" class="ds-input">
            </div>

            <div class="ds-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="student-username" style="margin-bottom: 0;">Nome de Usuário (Apelido)</label>
                    <span style="font-size: 0.78rem; color: #94a3b8;">Ex: @hadelrachid</span>
                </div>
                <div style="position: relative; display: flex; align-items: center;">
                    <span style="position: absolute; left: 14px; color: var(--accent-cyan); font-weight: 700; font-size: 0.95rem; pointer-events: none;">@</span>
                    <input type="text" id="student-username" name="username" required placeholder="hadelrachid" pattern="[a-z0-9_.-]{3,40}" class="ds-input" style="padding-left: 32px;" title="Apenas letras minúsculas, números, ponto ou traço">
                </div>
                <span style="display: block; font-size: 0.76rem; color: #64748b; margin-top: 4px;">Usado para identificar você no ambiente e certificados</span>
            </div>

            <div class="ds-form-group">
                <label for="student-email">Seu Melhor E-mail</label>
                <input type="email" id="student-email" name="email" required placeholder="seu.email@exemplo.com" class="ds-input">
            </div>

            <div class="ds-grid-2">
                <div class="ds-form-group">
                    <label for="student-pass">Senha (mín. 6)</label>
                    <div class="ds-pass-wrapper">
                        <input type="password" id="student-pass" name="password" required minlength="6" placeholder="••••••••" class="ds-input">
                        <button type="button" class="ds-toggle-pass-btn" onclick="togglePassVisibility('student-pass', this)" title="Ver senha" aria-label="Ver senha">
                            <span class="ds-eye-icon">👁️</span>
                        </button>
                    </div>
                </div>
                <div class="ds-form-group">
                    <label for="student-pass-conf">Confirmar Senha</label>
                    <div class="ds-pass-wrapper">
                        <input type="password" id="student-pass-conf" name="password_confirmation" required minlength="6" placeholder="••••••••" class="ds-input">
                        <button type="button" class="ds-toggle-pass-btn" onclick="togglePassVisibility('student-pass-conf', this)" title="Ver senha" aria-label="Ver senha">
                            <span class="ds-eye-icon">👁️</span>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="ds-submit-btn">
                ✨ Criar Minha Conta &rarr;
            </button>
        </form>

        <div class="ds-auth-links">
            Já possui uma conta?
            <a href="<?= $baseUrl ?>/aluno/login<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">
                Fazer Login &rarr;
            </a>
        </div>

        <div class="ds-back-link">
            <a href="<?= $baseUrl !== '' ? $baseUrl : '/' ?>">&larr; Voltar para a Página Inicial</a>
        </div>
    </div>

    <script>
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = btn.querySelector('.ds-eye-icon');
            if (icon) {
                icon.textContent = isPassword ? '🙈' : '👁️';
            }
            btn.title = isPassword ? 'Ocultar senha' : 'Ver senha';
            btn.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Ver senha');
            btn.style.color = isPassword ? '#00d284' : '#94a3b8';
        }

        // Sugestão automática de username a partir do Nome Completo
        const nameInput = document.getElementById('student-name');
        const usernameInput = document.getElementById('student-username');
        let usernameManuallyEdited = false;

        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                usernameManuallyEdited = this.value.trim().length > 0;
            });
        }

        if (nameInput && usernameInput) {
            nameInput.addEventListener('input', function() {
                if (!usernameManuallyEdited) {
                    const clean = this.value
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]/g, '');
                    usernameInput.value = clean;
                }
            });
        }
    </script>
</body>
</html>
