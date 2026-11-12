<?php
/**
 * View Pública: Login & Cadastro de Cliente Lado a Lado (Dois Cards Lado a Lado)
 *
 * @var string $csrfToken
 * @var array|null $flash
 * @var string $prefillEmail
 * @var string $prefillName
 * @var string $redirect
 */
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$redirectVal = (string)($redirect ?? '');
$isCheckoutRedirect = str_contains($redirectVal, '/checkout/');
?>
<style>
.dd-dual-wrapper {
    max-width: 1080px;
    margin: 36px auto 60px auto;
    padding: 0 16px;
    box-sizing: border-box;
    color: #e2e8f0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.dd-dual-header {
    text-align: center;
    margin-bottom: 24px;
}
.dd-dual-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    margin: 10px 0 6px 0;
}
.dd-dual-header p {
    font-size: 14px;
    color: #94a3b8;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.5;
}
.dd-checkout-banner {
    margin-bottom: 22px;
    padding: 14px 18px;
    border-radius: 12px;
    background: rgba(212, 175, 55, 0.12);
    border: 1px solid rgba(212, 175, 55, 0.42);
    color: #fef3c7;
    font-size: 14px;
    text-align: center;
    line-height: 1.5;
}
.dd-dual-cards {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
    gap: 24px;
    align-items: stretch;
}
.dd-auth-card {
    padding: 32px 28px;
    background: linear-gradient(145deg, #101724 0%, #0b0f17 100%);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 16px;
    color: #e2e8f0;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.dd-auth-grid-2 {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 12px;
}
@media (max-width: 860px) {
    .dd-dual-cards {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 560px) {
    .dd-auth-card {
        padding: 22px 16px;
    }
    .dd-auth-grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dd-dual-wrapper">
    <div class="dd-dual-header">
        <span style="display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #F0D060; text-transform: uppercase; background: rgba(212,175,55,0.12); padding: 4px 12px; border-radius: 99px; border: 1px solid rgba(212,175,55,0.35);">
            Área do Cliente • Garantia &amp; Suporte RachidD.com
        </span>
        <h1>Entrar ou Criar sua Conta</h1>
        <p>Acesse seus pedidos e rastreamento em tempo real ou crie seu cadastro rápido ao lado.</p>
    </div>

    <?php if ($isCheckoutRedirect): ?>
        <div class="dd-checkout-banner">
            🛍️ <strong>Identificação para Compra:</strong> Faça login na sua conta (card à esquerda) ou cadastre-se rapidamente (card à direita). Assim que confirmar, você será <strong>redirecionado automaticamente para a página de venda do produto</strong>!
        </div>
    <?php endif; ?>

    <?php if (!empty($flash['msg'])): ?>
        <?php $isErr = ($flash['type'] ?? '') === 'error'; ?>
        <div style="margin-bottom: 20px; padding: 12px 16px; border-radius: 10px; font-size: 13.5px; text-align: center; background: <?= $isErr ? 'rgba(239,68,68,0.14)' : 'rgba(16,185,129,0.14)' ?>; border: 1px solid <?= $isErr ? 'rgba(239,68,68,0.45)' : 'rgba(16,185,129,0.45)' ?>; color: <?= $isErr ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= $e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- DOIS CARDS LADO A LADO: LOGIN (ESQUERDA) + CADASTRO (DIREITA) -->
    <div class="dd-dual-cards">
        <!-- CARD 1: JÁ SOU CLIENTE (LOGIN) -->
        <div class="dd-auth-card">
            <div>
                <div style="margin-bottom: 20px;">
                    <span style="display: inline-block; font-size: 11px; font-weight: 800; color: #F0D060; text-transform: uppercase; letter-spacing: 0.5px;">🔑 Já tenho conta</span>
                    <h2 style="margin: 6px 0 6px 0; font-size: 22px; font-weight: 800; color: #fff;">Entrar na Minha Conta</h2>
                    <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.45;">
                        Informe seu e-mail e senha para continuar.
                    </p>
                </div>

                <form method="POST" action="<?= $e($baseUrl) ?>/entrar" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                    <?php if ($redirectVal !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= $e($redirectVal) ?>">
                    <?php endif; ?>

                    <div>
                        <label for="login-email" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">E-mail Cadastrado</label>
                        <input id="login-email" type="email" name="email" required value="<?= $e($prefillEmail ?? '') ?>" placeholder="seu@email.com" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                    </div>

                    <div>
                        <label for="login-pass" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Senha</label>
                        <div style="position: relative;">
                            <input id="login-pass" type="password" name="password" required placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 12px 42px 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                            <button type="button" onclick="ddTogglePass('login-pass', this)" aria-label="Mostrar ou ocultar senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 6px; display: inline-flex; align-items: center; font-size: 16px;" title="Mostrar/ocultar senha">👁️</button>
                        </div>
                    </div>

                    <button type="submit" style="margin-top: 6px; padding: 14px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 20px rgba(212,175,55,0.3);">
                        <?= $isCheckoutRedirect ? 'Entrar e Ir para Pagamento ➔' : 'Entrar Agora ➔' ?>
                    </button>
                </form>
            </div>

            <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 12.5px; color: #94a3b8; line-height: 1.45;">
                🛡️ Acompanhe o SLA de entrega (7 dias Brasil / 25 dias Internacional), rastreio oficial e suporte em português na sua conta.
            </div>
        </div>

        <!-- CARD 2: NOVO CLIENTE (CADASTRO) -->
        <div class="dd-auth-card" style="border-color: rgba(16, 185, 129, 0.35);">
            <div>
                <div style="margin-bottom: 20px;">
                    <span style="display: inline-block; font-size: 11px; font-weight: 800; color: #34d399; text-transform: uppercase; letter-spacing: 0.5px;">✍️ Primeiro acesso</span>
                    <h2 style="margin: 6px 0 6px 0; font-size: 22px; font-weight: 800; color: #fff;">Criar Conta na RachidD</h2>
                    <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.45;">
                        Preencha seus dados abaixo para criar sua conta e prosseguir imediatamente.
                    </p>
                </div>

                <form method="POST" action="<?= $e($baseUrl) ?>/cadastro" style="display: flex; flex-direction: column; gap: 13px;">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                    <?php if ($redirectVal !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= $e($redirectVal) ?>">
                    <?php endif; ?>

                    <div>
                        <label for="reg-name" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">Nome Completo *</label>
                        <input id="reg-name" type="text" name="name" required value="<?= $e($prefillName ?? '') ?>" placeholder="Ex.: Maria Oliveira" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                    </div>

                    <div>
                        <label for="reg-email" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">E-mail *</label>
                        <input id="reg-email" type="email" name="email" required value="<?= $e($prefillEmail ?? '') ?>" placeholder="seu@email.com" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                    </div>

                    <div class="dd-auth-grid-2">
                        <div>
                            <label for="reg-cpf" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">CPF (opcional)</label>
                            <input id="reg-cpf" type="text" name="cpf" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" oninput="ddMaskCpf(this)" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                        </div>
                        <div>
                            <label for="reg-phone" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">WhatsApp / Telefone</label>
                            <input id="reg-phone" type="tel" name="phone" inputmode="tel" maxlength="15" placeholder="(11) 99999-9999" oninput="ddMaskPhone(this)" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                        </div>
                    </div>

                    <div class="dd-auth-grid-2">
                        <div>
                            <label for="reg-pass" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">Senha (mín. 6 dígitos) *</label>
                            <div style="position: relative;">
                                <input id="reg-pass" type="password" name="password" required minlength="6" placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 11px 42px 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                                <button type="button" onclick="ddTogglePass('reg-pass', this)" aria-label="Mostrar ou ocultar senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 6px; display: inline-flex; align-items: center; font-size: 16px;" title="Mostrar/ocultar senha">👁️</button>
                            </div>
                        </div>
                        <div>
                            <label for="reg-pass2" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 5px;">Confirmar Senha *</label>
                            <div style="position: relative;">
                                <input id="reg-pass2" type="password" name="password_confirm" required minlength="6" placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 11px 42px 11px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                                <button type="button" onclick="ddTogglePass('reg-pass2', this)" aria-label="Mostrar ou ocultar confirmação de senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 6px; display: inline-flex; align-items: center; font-size: 16px;" title="Mostrar/ocultar senha">👁️</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="margin-top: 6px; padding: 14px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 20px rgba(16,185,129,0.3);">
                        <?= $isCheckoutRedirect ? 'Criar Conta e Ir para Pagamento ➔' : 'Criar Minha Conta e Acessar Painel ➔' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function ddTogglePass(id, btn) {
    var input = document.getElementById(id);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁️';
    btn.style.color = show ? '#F0D060' : '#94a3b8';
}
function ddMaskCpf(el) {
    var d = el.value.replace(/\D/g, '').slice(0, 11);
    if (d.length > 9) el.value = d.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    else if (d.length > 6) el.value = d.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    else if (d.length > 3) el.value = d.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    else el.value = d;
}
function ddMaskPhone(el) {
    var d = el.value.replace(/\D/g, '').slice(0, 11);
    if (d.length > 10) el.value = d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    else if (d.length > 6) el.value = d.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
    else if (d.length > 2) el.value = d.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    else el.value = d;
}
</script>
