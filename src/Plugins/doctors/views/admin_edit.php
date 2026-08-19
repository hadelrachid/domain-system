<?php ob_start(); ?>

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Editar Médico</h1>
        <a href="<?= BASE_URL ?>/admin/doctors" class="page-title-action">&larr; Voltar para a lista</a>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msg['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="upload-box" style="max-width: 600px; margin-top: 20px;">
        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/update">
            <input type="hidden" name="id" value="<?= $doctor['id'] ?>">

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Nome do Profissional</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($doctor['name'] ?? '') ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">CRM / Registro</label>
                    <input type="text" name="crm" value="<?= htmlspecialchars($doctor['crm'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Especialidade</label>
                    <input type="text" name="specialty" value="<?= htmlspecialchars($doctor['specialty'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Tempo (Minutos)</label>
                    <input type="number" name="consultation_time" value="<?= (int)($doctor['consultation_time'] ?? 30) ?>" min="5" step="5" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <label style="display:block; margin-bottom: 5px;">URL da Foto</label>
            <input type="url" name="photo_url" value="<?= htmlspecialchars($doctor['photo_url'] ?? '') ?>" style="width: 100%; padding: 8px; margin-bottom: 20px; box-sizing: border-box;">

            <div style="text-align:right;">
                <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 14px;">Salvar Alterações</button>
            </div>
        </form>
    </div>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
