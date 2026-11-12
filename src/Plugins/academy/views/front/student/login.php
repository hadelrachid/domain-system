<?php
/**
 * View: Login & Cadastro do Aluno Lado a Lado (Academy - Estilo Udemy)
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
$isCheckoutRedirect = str_contains((string)($redirect ?? ''), '/checkout/');
$pageTitle = $pageTitle ?? 'Área do Aluno | Entrar na Sala de Aula';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: rgba(15, 20, 28, 0.95);
            --border-cyan: rgba(69, 243, 255, 0.28);
            --border-green: rgba(0, 210, 132, 0.32);
            --accent-cyan: #45f3ff;
            --accent-green: #00d284;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            background: var(--bg-body);
            background-image: radial-gradient(circle at 25% 15%, rgba(69, 243, 255, 0.08) 0%, transparent 55%),
                              radial-gradient(circle at 75% 85%, rgba(0, 210, 132, 0.08) 0%, transparent 55%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }
        .ds-auth-wrapper {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
        }
        .ds-auth-top-banner {
            text-align: center;
            margin-bottom: 24px;
        }
        .ds-auth-top-banner h1 {
            color: #fff;
            font-size: 1.85rem;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        .ds-auth-top-banner p {
            color: var(--text-muted);
            font-size: 0.96rem;
            max-width: 680px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .ds-checkout-step-banner {
            background: rgba(0, 210, 132, 0.1);
            border: 1px solid rgba(0, 210, 132, 0.38);
            color: #d1fae5;
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 0.92rem;
            line-height: 1.5;
            text-align: center;
        }
        .ds-dual-cards-grid {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 24px;
            align-items: stretch;
        }
        @media (max-width: 860px) {
            .ds-dual-cards-grid {
                grid-template-columns: 1fr;
            }
        }
        .ds-student-auth-box {
            width: 100%;
            background: var(--bg-card);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.6);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .ds-card-login {
            border: 1px solid var(--border-cyan);
        }
        .ds-card-register {
            border: 1px solid var(--border-green);
        }
        .ds-auth-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .ds-icon-cyan {
            background: rgba(69, 243, 255, 0.1);
            border: 1px solid rgba(69, 243, 255, 0.3);
        }
        .ds-icon-green {
            background: rgba(0, 210, 132, 0.1);
            border: 1px solid rgba(0, 210, 132, 0.3);
        }
        .ds-auth-header {
            margin-bottom: 22px;
        }
        .ds-auth-header h2 {
            color: #fff;
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0 0 6px 0;
            letter-spacing: -0.02em;
        }
        .ds-auth-header p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin: 0;
            line-height: 1.45;
        }
        .ds-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            line-height: 1.45;
            text-align: center;
        }
        .ds-alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
        .ds-alert-success {
            background: rgba(0, 210, 132, 0.15);
            border: 1px solid rgba(0, 210, 132, 0.4);
            color: #6ee7b7;
        }
        .ds-form-group {
            margin-bottom: 15px;
        }
        .ds-form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 5px;
        }
        .ds-input {
            width: 100%;
            box-sizing: border-box;
            background: #0b0c10;
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 11px 14px;
            color: #fff;
            font-size: 0.93rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .ds-input:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 3px rgba(69, 243, 255, 0.18);
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
        @media (max-width: 520px) {
            .ds-grid-2 {
                grid-template-columns: 1fr;
            }
        }
        .ds-submit-btn {
            width: 100%;
            padding: 13px;
            border-radius: 8px;
            border: none;
            font-size: 0.98rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.2s, box-shadow 0.2s;
            margin-top: 6px;
        }
        .ds-btn-cyan {
            background: var(--accent-cyan);
            color: #0b0c10;
            box-shadow: 0 4px 14px rgba(69, 243, 255, 0.3);
        }
        .ds-btn-green {
            background: var(--accent-green);
            color: #0b0c10;
            box-shadow: 0 4px 14px rgba(0, 210, 132, 0.3);
        }
        .ds-submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .ds-back-link {
            margin-top: 22px;
            text-align: center;
        }
        .ds-back-link a {
            color: #94a3b8;
            font-size: 0.88rem;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
        }
        .ds-back-link a:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="ds-auth-wrapper">
        <div class="ds-auth-top-banner">
            <h1>🎓 Área de Estudos Academy</h1>
            <p>Entre na sua conta de aluno ou crie seu cadastro gratuito ao lado para acessar suas aulas, apostilas em PDF, anotações e certificados.</p>
        </div>

        <?php if ($isCheckoutRedirect): ?>
            <div class="ds-checkout-step-banner">
                🔒 <strong>Identificação para Matrícula:</strong> Entre na sua conta (card à esquerda) ou cadastre-se gratuitamente (card à direita). Assim que confirmar, você será <strong>redirecionado automaticamente para a página de venda do curso</strong>!
            </div>
        <?php endif; ?>

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

        <!-- DOIS CARDS LADO A LADO: LOGIN (ESQUERDA) + CADASTRO (DIREITA) -->
        <div class="ds-dual-cards-grid">
            <!-- CARD 1: JÁ SOU ALUNO (LOGIN) -->
            <div class="ds-student-auth-box ds-card-login">
                <div>
                    <div class="ds-auth-header">
                        <div class="ds-auth-icon ds-icon-cyan">🔑</div>
                        <h2>Já sou Aluno (Entrar)</h2>
                        <p>Acesse sua Área de Estudos com seu e-mail e senha cadastrados.</p>
                    </div>

                    <form method="POST" action="<?= $baseUrl ?>/aluno/login">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?= $redirect ?>">
                        <?php endif; ?>

                        <div class="ds-form-group">
                            <label for="student-email">Seu E-mail</label>
                            <input type="email" id="student-email" name="email" required placeholder="seu.email@exemplo.com" class="ds-input">
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

                        <button type="submit" class="ds-submit-btn ds-btn-cyan">
                            🚀 <?= $isCheckoutRedirect ? 'Entrar e Ir para Pagamento &rarr;' : 'Entrar na Sala de Aula &rarr;' ?>
                        </button>
                    </form>
                </div>

                <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                    💡 Seus cursos matriculados, progresso de aulas e certificados ficam salvos permanentemente no seu painel <a href="<?= $baseUrl ?>/aluno/painel" style="color: var(--accent-cyan); text-decoration: none; font-weight: 700;">Meus Cursos</a>.
                </div>
            </div>

            <!-- CARD 2: NOVO ALUNO (CADASTRO) -->
            <div class="ds-student-auth-box ds-card-register">
                <div>
                    <div class="ds-auth-header">
                        <div class="ds-auth-icon ds-icon-green">✍️</div>
                        <h2>Criar Conta de Aluno</h2>
                        <p>Primeira vez aqui? Crie sua conta gratuita em 30 segundos e continue imediatamente.</p>
                    </div>

                    <form method="POST" action="<?= $baseUrl ?>/aluno/cadastro">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?= $redirect ?>">
                        <?php endif; ?>

                        <div class="ds-form-group">
                            <label for="student-name">Nome Completo</label>
                            <input type="text" id="student-name" name="name" required placeholder="Ex: HADEL RACHID DAHER JUNIOR" class="ds-input">
                        </div>

                        <div class="ds-grid-2">
                            <div class="ds-form-group">
                                <label for="student-username">Usuário (@apelido)</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 12px; color: var(--accent-cyan); font-weight: 700; font-size: 0.9rem; pointer-events: none;">@</span>
                                    <input type="text" id="student-username" name="username" required placeholder="hadelrachid" pattern="[a-z0-9_.-]{3,40}" class="ds-input" style="padding-left: 28px;" title="Apenas letras minúsculas, números, ponto ou traço">
                                </div>
                            </div>

                            <div class="ds-form-group">
                                <label for="student-reg-email">Seu Melhor E-mail</label>
                                <input type="email" id="student-reg-email" name="email" required placeholder="seu.email@exemplo.com" class="ds-input">
                            </div>
                        </div>

                        <div class="ds-grid-2">
                            <div class="ds-form-group">
                                <label for="student-pass">Senha (mín. 8 caracteres)</label>
                                <div class="ds-pass-wrapper">
                                    <input type="password" id="student-pass" name="password" required minlength="8" placeholder="••••••••" class="ds-input">
                                    <button type="button" class="ds-toggle-pass-btn" onclick="togglePassVisibility('student-pass', this)" title="Ver senha" aria-label="Ver senha">
                                        <span class="ds-eye-icon">👁️</span>
                                    </button>
                                </div>
                            </div>
                            <div class="ds-form-group">
                                <label for="student-pass-conf">Confirmar Senha</label>
                                <div class="ds-pass-wrapper">
                                    <input type="password" id="student-pass-conf" name="password_confirmation" required minlength="8" placeholder="••••••••" class="ds-input">
                                    <button type="button" class="ds-toggle-pass-btn" onclick="togglePassVisibility('student-pass-conf', this)" title="Ver senha" aria-label="Ver senha">
                                        <span class="ds-eye-icon">👁️</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="ds-submit-btn ds-btn-green">
                            ✨ <?= $isCheckoutRedirect ? 'Criar Conta e Ir para Pagamento &rarr;' : 'Criar Minha Conta de Aluno &rarr;' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="ds-back-link">
            <a href="<?= $baseUrl ?>/cursos">&larr; Voltar para Cursos &amp; Tutoriais</a>
            <a href="<?= $baseUrl !== '' ? $baseUrl : '/' ?>">🏠 Página Inicial</a>
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
