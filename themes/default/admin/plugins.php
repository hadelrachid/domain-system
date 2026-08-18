<?php ob_start(); ?>

<h1>
    Plugins
    <button type="button" class="page-title-action" onclick="document.getElementById('upload-form').style.display='block'">Adicionar Novo</button>
</h1>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div style="padding: 12px; margin-bottom: 20px; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'success' ? '#00a32a' : '#d63638' ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <strong><?= htmlspecialchars($_SESSION['flash_message']['msg']) ?></strong>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<?php if (!empty($crashes)): ?>
    <?php foreach ($crashes as $crash): ?>
        <div class="alert alert-error" style="padding: 15px; margin: 20px 0; border-radius: 4px; border-left: 4px solid #d63638; background: #fff; color: #d63638; font-weight: bold;">
            ❌ O plugin "<?php echo htmlspecialchars($crash['plugin']); ?>" falhou fatalmente ao ser carregado e foi DESATIVADO automaticamente por segurança!<br>
            <span style="font-size: 12px; font-weight: normal; color: #555;">Erro: <?php echo htmlspecialchars($crash['error']); ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div id="upload-form" class="upload-box" style="display: none;">
    <h3>Fazer Upload de Plugin</h3>
    <p>Se você possui um plugin em formato .zip, você pode instalá-lo fazendo o upload do arquivo aqui.</p>
    
    <div id="upload-progress" style="display: none; margin: 15px 0;">
        <div style="font-size: 14px; margin-bottom: 5px; color: #1d2327;">Descompactando e ligando módulos...</div>
        <div style="width: 100%; background: #f0f0f1; border-radius: 4px; overflow: hidden; border: 1px solid #c3c4c7;">
            <div id="progress-bar-fill" style="width: 0%; height: 20px; background: #2271b1; transition: width 0.5s ease;"></div>
        </div>
    </div>

    <form method="POST" action="admin/plugins/upload" enctype="multipart/form-data" id="form-upload-plugin" onsubmit="
        document.getElementById('upload-progress').style.display='block';
        document.getElementById('upload-buttons').style.display='none';
        let w = 0;
        setInterval(() => { w += (100 - w) * 0.2; document.getElementById('progress-bar-fill').style.width = w + '%'; }, 200);
    ">
        <input type="file" name="plugin_zip" accept=".zip" required>
        <div id="upload-buttons" style="display: inline-block;">
            <button type="submit" class="btn btn-activate" style="margin-left: 10px;">Instalar Agora</button>
            <button type="button" class="btn" style="margin-left: 5px; color: #d63638;" onclick="document.getElementById('upload-form').style.display='none'">Cancelar</button>
        </div>
    </form>
</div>

<table class="wp-list-table">
    <thead>
        <tr>
            <th>Plugin</th>
            <th>Descrição</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plugins as $plugin): ?>
        <tr style="background: <?= $plugin['is_active'] ? '#f0f6fc' : '#fff' ?>; box-shadow: <?= $plugin['is_active'] ? 'inset 4px 0 0 0 #00a32a' : 'none' ?>;">
            <td style="padding-left: 15px;">
                <strong><?= htmlspecialchars($plugin['name']) ?></strong> 
                <span class="badge">v<?= htmlspecialchars($plugin['version']) ?></span>
                <br>
                <small style="color:#666;">/<?= htmlspecialchars($plugin['folder']) ?></small>
            </td>
            <td><?= htmlspecialchars($plugin['description']) ?></td>
            <td style="display: flex; gap: 5px;">
                <form method="POST" action="admin/plugins/toggle" style="margin:0;">
                    <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                    
                    <?php if ($plugin['is_core']): ?>
                        <button type="button" class="btn btn-core" disabled>Core (Bloqueado)</button>
                    <?php elseif ($plugin['is_active']): ?>
                        <input type="hidden" name="action" value="disable">
                        <button type="submit" class="btn btn-deactivate">Desativar</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="enable">
                        <button type="submit" class="btn btn-activate">Ativar</button>
                    <?php endif; ?>
                </form>

                <?php if (!$plugin['is_core'] && !$plugin['is_active']): ?>
                <form method="POST" action="admin/plugins/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir o plugin <?= htmlspecialchars($plugin['name']) ?>? Isso apagará a pasta dele.');">
                    <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                    <input type="hidden" name="plugin_folder" value="<?= htmlspecialchars($plugin['folder']) ?>">
                    <button type="submit" class="btn btn-deactivate" style="border-color: #d63638; color: #d63638; background: transparent;">Excluir</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
