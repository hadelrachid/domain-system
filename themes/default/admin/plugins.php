<?php ob_start(); ?>

<h1>
    Plugins
    <button type="button" class="page-title-action" onclick="document.getElementById('upload-form').style.display='block'">Adicionar Novo</button>
</h1>

<div id="upload-form" class="upload-box" style="display: none;">
    <h3>Fazer Upload de Plugin</h3>
    <p>Se você possui um plugin em formato .zip, você pode instalá-lo fazendo o upload do arquivo aqui.</p>
    <form method="POST" action="/admin/plugins/upload" enctype="multipart/form-data">
        <input type="file" name="plugin_zip" accept=".zip" required>
        <button type="submit" class="btn btn-activate" style="margin-left: 10px;">Instalar Agora</button>
        <button type="button" class="btn" style="margin-left: 5px; color: #d63638;" onclick="document.getElementById('upload-form').style.display='none'">Cancelar</button>
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
        <tr style="background: <?= $plugin['is_active'] ? '#f6fcf8' : '#fff' ?>">
            <td>
                <strong><?= htmlspecialchars($plugin['name']) ?></strong> 
                <span class="badge">v<?= htmlspecialchars($plugin['version']) ?></span>
                <br>
                <small style="color:#666;">/<?= htmlspecialchars($plugin['folder']) ?></small>
            </td>
            <td><?= htmlspecialchars($plugin['description']) ?></td>
            <td style="display: flex; gap: 5px;">
                <form method="POST" action="/admin/plugins/toggle" style="margin:0;">
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
                <form method="POST" action="/admin/plugins/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir o plugin <?= htmlspecialchars($plugin['name']) ?>? Isso apagará a pasta dele.');">
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
