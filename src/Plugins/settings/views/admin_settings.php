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
    <form method="POST" action="<?= BASE_URL ?>/admin/settings" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nome da Clínica / Negócio</label>
            <input type="text" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">CNPJ (Opcional)</label>
            <input type="text" name="clinic_cnpj" value="<?= htmlspecialchars($settings['clinic_cnpj'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;" placeholder="00.000.000/0001-00">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Slogan / Subtítulo</label>
            <input type="text" name="clinic_slogan" value="<?= htmlspecialchars($settings['clinic_slogan'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
            <small style="color: #666;">Aparece no cabeçalho do receituário PDF.</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Endereço Completo</label>
            <input type="text" name="clinic_address" value="<?= htmlspecialchars($settings['clinic_address'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Telefone Fixo</label>
            <input type="text" name="clinic_phone" value="<?= htmlspecialchars($settings['clinic_phone'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">WhatsApp Oficial</label>
            <input type="text" name="clinic_whatsapp" value="<?= htmlspecialchars($settings['clinic_whatsapp'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px; padding-top: 15px; border-top: 1px solid #eee;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Logo da Clínica (PNG, máx 512x512)</label>
            <?php if(!empty($settings['clinic_logo'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?= $settings['clinic_logo'] ?>" alt="Logo" style="max-height: 80px; border: 1px solid #ccc; padding: 5px; background: #fafafa;">
                </div>
            <?php endif; ?>
            <input type="file" name="clinic_logo" accept="image/png" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" class="btn btn-activate" style="padding: 10px 20px; font-size: 14px;">Salvar Configurações</button>
    </form>
</div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
