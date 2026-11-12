<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div style="max-width: 460px; margin: 60px auto; padding: 34px; background: linear-gradient(145deg, #101724 0%, #0b0f17 100%); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 16px; color: #e2e8f0; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #F0D060; text-transform: uppercase; background: rgba(212,175,55,0.12); padding: 4px 12px; border-radius: 99px; border: 1px solid rgba(212,175,55,0.35);">
            Área do Cliente
        </span>
        <h1 style="margin: 14px 0 8px 0; font-size: 25px; font-weight: 800; color: #fff;">Entrar na Minha Conta</h1>
        <p style="margin: 0; font-size: 13.5px; color: #94a3b8;">
            Consulte o andamento das suas entregas ou abra uma reclamação com envio de fotos ou PDF.
        </p>
    </div>

    <?php if (!empty($flash['msg'])): ?>
        <?php $isErr = ($flash['type'] ?? '') === 'error'; ?>
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; font-size: 13px; background: <?= $isErr ? 'rgba(239,68,68,0.14)' : 'rgba(16,185,129,0.14)' ?>; border: 1px solid <?= $isErr ? 'rgba(239,68,68,0.45)' : 'rgba(16,185,129,0.45)' ?>; color: <?= $isErr ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= $e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $e($baseUrl) ?>/entrar" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken ?? '') ?>">

        <div>
            <label for="login-email" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">E-mail Cadastrado</label>
            <input id="login-email" type="email" name="email" required value="<?= $e($prefillEmail ?? '') ?>" placeholder="seu@email.com" style="width: 100%; box-sizing: border-box; padding: 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
        </div>

        <div>
            <label for="login-pass" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Senha</label>
            <div style="position: relative;">
                <input id="login-pass" type="password" name="password" required placeholder="••••••" style="width: 100%; box-sizing: border-box; padding: 12px 40px 12px 14px; background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 14px;">
                <button type="button" onclick="(function(b){var i=document.getElementById('login-pass');if(!i)return;var s=i.type==='password';i.type=s?'text':'password';b.textContent=s?'🙈':'👁️';b.style.color=s?'#F0D060':'#94a3b8';})(this)" aria-label="Mostrar ou ocultar senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: inline-flex; align-items: center;" title="Mostrar/ocultar senha">👁️</button>
            </div>
        </div>

        <button type="submit" style="margin-top: 6px; padding: 14px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-size: 15px; font-weight: 800; cursor: pointer;">
            Entrar Agora ➔
        </button>
    </form>

    <div style="text-align: center; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 13px; color: #94a3b8;">
        Ainda não tem conta? <a href="<?= $e($baseUrl) ?>/cadastro" style="color: #F0D060; font-weight: 700; text-decoration: none;">Cadastre-se gratuitamente</a>
    </div>
</div>
