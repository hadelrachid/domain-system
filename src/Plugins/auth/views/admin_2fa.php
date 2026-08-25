<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Configurar 2FA para: <?= htmlspecialchars($user['name']) ?></h1>
    <a href="<?= BASE_URL ?>/admin/users" class="page-title-action">&larr; Voltar para Usuários</a>
</div>

<div style="background: #fff; padding: 30px; border: 1px solid #c3c4c7; border-radius: 4px; max-width: 600px; margin: 0 auto; text-align: center;">
    <h2 style="margin-top:0;">Passo 1: Escanear o QR Code</h2>
    <p>Abra o aplicativo Google Authenticator ou Authy no celular do usuário e escaneie o código abaixo:</p>
    
    <div style="margin: 20px 0;">
        <img src="<?= $qrCodeUrl ?>" alt="QR Code 2FA" style="border: 1px solid #ccc; padding: 10px; border-radius: 4px; background: #fff;">
    </div>
    
    <p style="font-size: 13px; color: #666;">Chave manual: <strong><?= htmlspecialchars($secret) ?></strong></p>

    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

    <h2 style="margin-top:0;">Passo 2: Confirmar o Código</h2>
    <p>Digite os 6 dígitos gerados pelo aplicativo para confirmar que a sincronização funcionou.</p>
    
    <form method="POST" action="<?= BASE_URL ?>/admin/users/2fa">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <input type="hidden" name="secret" value="<?= $secret ?>">
        
        <input type="text" name="code" placeholder="000000" maxlength="6" required style="font-size: 24px; text-align: center; padding: 10px; width: 150px; border: 2px solid #0073aa; border-radius: 4px; margin-bottom: 15px; letter-spacing: 5px;">
        <br>
        <button type="submit" class="btn btn-activate" style="padding: 10px 20px; font-size: 16px;">Confirmar e Ativar 2FA</button>
    </form>
</div>


