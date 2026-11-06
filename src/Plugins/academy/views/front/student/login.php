<?php
/**
 * View: Login do Aluno (Academy)
 * 100% Desacoplada e Isolada do Painel de Administração.
 *
 * @var string $baseUrl
 * @var string $csrfToken
 * @var string $redirect
 * @var string|array|null $error
 * @var string|array|null $success
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
    <title>Área do Aluno | Entrar na Sala de Aula</title>
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: rgba(15, 20, 28, 0.95);
            --border-color: rgba(69, 243, 255, 0.25);
            --accent-cyan: #45f3ff;
            --accent-green: #00d284;
            --text-main: #f8fafc;
            --text-muted: #8c8f94;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            background: var(--bg-body);
            background-image: radial-gradient(circle at 50% 15%, rgba(69, 243, 255, 0.08) 0%, transparent 60%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .ds-student-auth-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.6), 0 0 24px rgba(69, 243, 255, 0.08);
            backdrop-filter: blur(12px);
        }
        .ds-auth-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(69, 243, 255, 0.1);
            border: 1px solid rgba(69, 243, 255, 0.3);
            font-size: 26px;
            margin-bottom: 14px;
        }
        .ds-auth-header {
            text-align: center;
            margin-bottom: 28px;
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
        .ds-alert-success {
            background: rgba(0, 210, 132, 0.15);
            border: 1px solid rgba(0, 210, 132, 0.4);
            color: #00d284;
        }
        .ds-form-group {
            margin-bottom: 18px;
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
            padding: 12px 14px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .ds-input:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 3px rgba(69, 243, 255, 0.2);
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
        .ds-submit-btn {
            width: 100%;
            padding: 13px;
            border-radius: 8px;
            border: none;
            background: var(--accent-cyan);
            color: #0b0c10;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(69, 243, 255, 0.3);
            margin-top: 4px;
        }
        .ds-submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(69, 243, 255, 0.4);
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
            <div class="ds-auth-icon">🎓</div>
            <h1>Área do Aluno</h1>
            <p>Acesse suas aulas, apostilas e certificados</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="ds-alert ds-alert-error">
                ⚠ <?= is_array($error) ? implode('<br>⚠ ', array_map('htmlspecialchars', $error)) : htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="ds-alert ds-alert-success">
                ✓ <?= is_array($success) ? implode('<br>✓ ', array_map('htmlspecialchars', $success)) : htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/aluno/login">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= $redirect ?>">
            <?php endif; ?>

            <div class="ds-form-group">
                <label for="student-email">Seu E-mail</label>
                <input type="email" id="student-email" name="email" required autofocus placeholder="seu.email@exemplo.com" class="ds-input">
            </div>

            <div class="ds-form-group">
                <label for="student-password">Sua Senha</label>
                <div class="ds-pass-wrapper">
                    <input type="password" id="student-password" name="password" required placeholder="••••••••" class="ds-input">
                    <button type="button" class="ds-toggle-pass-btn" onclick="togglePassVisibility('student-password', this)" title="Ver senha" aria-label="Ver senha">
                        <span class="ds-eye-icon">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="ds-submit-btn">
                🚀 Entrar na Sala de Aula &rarr;
            </button>
        </form>

        <div class="ds-auth-links">
            Ainda não tem conta?
            <a href="<?= $baseUrl ?>/aluno/cadastro<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">
                Cadastre-se Gratuitamente &rarr;
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
            btn.style.color = isPassword ? '#45f3ff' : '#94a3b8';
        }
    </script>
</body>
</html>
