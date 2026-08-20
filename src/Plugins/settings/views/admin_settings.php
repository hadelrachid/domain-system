<?php ob_start(); ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Configuraes Globais (A Vitrine)</h1>
</div>

<?php if(isset($_GET['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #c3e6cb;">
        Configuraes salvas com sucesso!
    </div>
<?php endif; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px; max-width: 600px;">
    <form method="POST" action="<?= BASE_URL ?>/admin/settings">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nome da Clnica / Negcio</label>
            <input type="text" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
            <small style="color: #666;">Ex: Clnica Daher</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Slogan / Subttulo</label>
            <input type="text" name="clinic_slogan" value="<?= htmlspecialchars($settings['clinic_slogan'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
            <small style="color: #666;">Aparece no cabealho do receiturio PDF.</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Endereo Completo</label>
            <input type="text" name="clinic_address" value="<?= htmlspecialchars($settings['clinic_address'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Telefone Principal (WhatsApp)</label>
            <input type="text" name="clinic_phone" value="<?= htmlspecialchars($settings['clinic_phone'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" class="btn btn-activate" style="padding: 10px 20px; font-size: 14px;">Salvar Configuraes</button>
    </form>
</div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
