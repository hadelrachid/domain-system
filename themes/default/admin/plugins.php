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

<div id="upload-form" class="upload-box" style="display: none; background: #fff; border: 1px solid #c3c4c7; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px;">
    <h3 style="margin-top: 0;">Fazer Upload de Plugin</h3>
    <p style="color: #646970;">Se você possui um plugin em formato .zip, você pode instalá-lo fazendo o upload do arquivo aqui.</p>
    
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
        <input type="file" name="plugin_zip" accept=".zip" required style="margin-bottom: 15px;">
        <div id="upload-buttons" style="display: block;">
            <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 14px;">Instalar Agora</button>
            <button type="button" class="btn" style="margin-left: 5px; color: #d63638; border-color: #d63638;" onclick="document.getElementById('upload-form').style.display='none'">Cancelar</button>
        </div>
    </form>
</div>

<style>
    .plugin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
    .plugin-table th, .plugin-table td { padding: 15px; border-bottom: 1px solid #c3c4c7; vertical-align: top; }
    .plugin-table th { background: #f6f7f7; font-weight: 600; text-align: left; }
    .plugin-table tr:last-child td { border-bottom: none; }
    .plugin-row-active td { background-color: #f0f6fc; }
    .plugin-row-active td:first-child { border-left: 4px solid #72aee6; }
    .plugin-row-disarmed td { background-color: #fffafb; }
</style>

<table class="plugin-table">
    <thead>
        <tr>
            <th style="width: 30%;">Plugin</th>
            <th style="width: 50%;">Descrição</th>
            <th style="width: 20%;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plugins as $plugin): ?>
        <tr class="<?= $plugin['is_active'] ? 'plugin-row-active' : ($plugin['is_disarmed'] ? 'plugin-row-disarmed' : '') ?>">
            <td style="<?= $plugin['is_disarmed'] ? 'border-left: 4px solid #d63638;' : '' ?>">
                <strong style="font-size: 14px; color: #1d2327;"><?= htmlspecialchars($plugin['name']) ?></strong> 
                <span class="badge" style="background: #e0e0e0; color: #2c3338; margin-left: 8px;">v<?= htmlspecialchars($plugin['version']) ?></span>
                <br>
                <small style="color: #646970; display: inline-block; margin-top: 5px;">Pasta: /<?= htmlspecialchars($plugin['folder']) ?></small>
            </td>
            <td style="color: #3c434a; line-height: 1.5;">
                <?= htmlspecialchars($plugin['description']) ?>
                
                <?php if ($plugin['is_disarmed']): ?>
                <div style="margin-top: 10px; padding: 10px; background: #fbeaea; color: #d63638; border-radius: 4px; border: 1px solid #ffc9c9; font-size: 13px;">
                    <strong>⚠️ Plugin Danificado:</strong> Este plugin causou um erro fatal e foi desconectado pelo Disjuntor.<br>
                    <a href="admin/monitor" style="color: #d63638; font-weight: bold; text-decoration: underline;">Verificar Monitor de Erros</a>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <form method="POST" action="admin/plugins/toggle" style="margin:0;">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        
                        <?php if ($plugin['is_core']): ?>
                            <button type="button" class="btn btn-core" disabled style="opacity: 0.7;">Núcleo do Sistema</button>
                        <?php elseif ($plugin['is_active']): ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn" style="color: #2271b1; border-color: #2271b1; background: transparent;">Desativar</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-activate">Ativar</button>
                        <?php endif; ?>
                    </form>

                    <?php if (!$plugin['is_core'] && !$plugin['is_active']): ?>
                    <form method="POST" action="admin/plugins/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir o plugin <?= htmlspecialchars($plugin['name']) ?>? Isso apagará a pasta dele.');">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        <input type="hidden" name="plugin_folder" value="<?= htmlspecialchars($plugin['folder']) ?>">
                        <button type="submit" class="btn" style="color: #d63638; border-color: transparent; background: transparent; text-decoration: underline;">Excluir</button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


