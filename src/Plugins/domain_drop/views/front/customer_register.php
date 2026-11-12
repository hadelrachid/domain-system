<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
.dd-auth-card {
    max-width: 560px;
    margin: 40px auto;
    padding: 34px;
    background: linear-gradient(145deg, #101724 0%, #0b0f17 100%);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 16px;
    color: #e2e8f0;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    box-sizing: border-box;
}
.dd-auth-grid-2 {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 12px;
}
@media (max-width: 600px) {
    .dd-auth-card {
        margin: 20px 12px;
        padding: 22px 16px;
    }
    .dd-auth-grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="dd-auth-card">
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #F0D060; text-transform: uppercase; background: rgba(212,175,55,0.12); padding: 4px 12px; border-radius: 99px; border: 1px solid rgba(212,175,55,0.35);">
            Cadastro de Cliente • Garantia &amp; Suporte
        </span>
        <h1 style="margin: 14px 0 8px 0; font-size: 26px; font-weight: 800; color: #fff;">Crie sua Conta na RachidD</h1>
        <p style="margin: 0; font-size: 14px; color: #94a3b8; line-height: 1.5;">
            Acompanhe seus pedidos em tempo real, baixe comprovantes e tenha canal direto de devolução e reembolso com envio de fotos ou PDF.
        </p>
    </div>

    <?php if (!empty($flash['msg'])): ?>
        <?php $isErr = ($flash['type'] ?? '') === 'error'; ?>
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; font-size: 13px; background: <?= $isErr ? 'rgba(239,68,68,0.14)' : 'rgba(16,185,129,0.14)' ?>; border: 1px solid <?= $isErr ? 'rgba(239,68,68,0.45)' : 'rgba(16,185,129,0.45)' ?>; color: <?= $isErr ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= $e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $e($baseUrl) ?>/cadastro" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken ?? '') ?>">

        <div>
            <label for="reg-name" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Nome Completo *</label>
            <input id="reg-name" type="text" name="name" required value="<?= $e($prefillName ?? '') ?>" placeholder="Ex.: Maria Oliveira" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
        </div>

        <div>
            <label for="reg-email" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">E-mail *</label>
            <input id="reg-email" type="email" name="email" required value="<?= $e($prefillEmail ?? '') ?>" placeholder="seu@email.com" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
        </div>

        <div class="dd-auth-grid-2">
            <div>
                <label for="reg-cpf" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">CPF (opcional)</label>
                <input id="reg-cpf" type="text" name="cpf" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" oninput="ddMaskCpf(this)" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
            </div>
            <div>
                <label for="reg-phone" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">WhatsApp / Telefone</label>
                <input id="reg-phone" type="tel" name="phone" inputmode="tel" maxlength="15" placeholder="(11) 99999-9999" oninput="ddMaskPhone(this)" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
            </div>
        </div>

        <div class="dd-auth-grid-2">
            <div>
                <label for="reg-pass" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Senha (mín. 6 dígitos) *</label>
                <div style="position: relative;">
                    <input id="reg-pass" type="password" name="password" required minlength="6" placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 12px 40px 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                    <button type="button" onclick="ddTogglePass('reg-pass', this)" aria-label="Mostrar ou ocultar senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: inline-flex; align-items: center;" title="Mostrar/ocultar senha">👁️</button>
                </div>
            </div>
            <div>
                <label for="reg-pass2" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Confirmar Senha *</label>
                <div style="position: relative;">
                    <input id="reg-pass2" type="password" name="password_confirm" required minlength="6" placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 12px 40px 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                    <button type="button" onclick="ddTogglePass('reg-pass2', this)" aria-label="Mostrar ou ocultar confirmação de senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: inline-flex; align-items: center;" title="Mostrar/ocultar senha">👁️</button>
                </div>
            </div>
        </div>

        <button type="submit" style="margin-top: 8px; padding: 14px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 20px rgba(212,175,55,0.35);">
            Criar Minha Conta e Acessar Painel ➔
        </button>
    </form>

    <div style="text-align: center; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 13px; color: #94a3b8;">
        Já tem cadastro? <a href="<?= $e($baseUrl) ?>/entrar" style="color: #F0D060; font-weight: 700; text-decoration: none;">Entrar na minha conta</a>
    </div>
</div>

<script>
function ddMaskCpf(el) {
    var v = el.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 9) {
        el.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
        el.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (v.length > 3) {
        el.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    } else {
        el.value = v;
    }
}
function ddMaskPhone(el) {
    var v = el.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 10) {
        el.value = v.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
    } else if (v.length > 6) {
        el.value = v.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
    } else if (v.length > 2) {
        el.value = v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    } else if (v.length > 0) {
        el.value = '(' + v;
    } else {
        el.value = '';
    }
}
function ddTogglePass(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁️';
    btn.style.color = show ? '#F0D060' : '#94a3b8';
}
</script>
