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
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="file" name="plugin_zip" accept=".zip" required style="margin-bottom: 15px;">
        <div id="upload-buttons" style="display: block;">
            <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 14px;">Instalar Agora</button>
            <button type="button" class="btn" style="margin-left: 5px; color: #d63638; border-color: #d63638;" onclick="document.getElementById('upload-form').style.display='none'">Cancelar</button>
        </div>
    </form>
</div>

<style>
    .plugin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
    .plugin-table th, .plugin-table td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: top; }
    .plugin-table th { background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; }
    .plugin-table tr:last-child td { border-bottom: none; }
    .plugin-row-active td { background-color: rgba(88,166,255,0.05); }
    .plugin-row-active td:first-child { border-left: 4px solid var(--accent-blue); }
    .plugin-row-disarmed td { background-color: rgba(245,110,40,0.05); }
</style>

<table class="plugin-table">
    <thead>
        <tr>
            <th style="width: 25%;">Plugin / Módulo</th>
            <th style="width: 10%;">Versão</th>
            <th style="width: 45%;">Descrição</th>
            <th style="width: 20%;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plugins as $plugin): ?>
        <tr class="<?= $plugin['is_active'] ? 'plugin-row-active' : ($plugin['is_disarmed'] ? 'plugin-row-disarmed' : '') ?>">
            <td style="<?= $plugin['is_disarmed'] ? 'border-left: 4px solid var(--accent-orange);' : '' ?>">
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($plugin['name']) ?></strong> 
                <br>
                <small style="color: var(--text-muted); display: inline-block; margin-top: 5px;">Pasta: /<?= htmlspecialchars($plugin['folder']) ?></small>
            </td>
            <td>
                <span class="badge" style="background: rgba(88,166,255,0.1); border: 1px solid var(--accent-blue); color: var(--accent-blue); padding: 4px 8px;">v<?= htmlspecialchars($plugin['version']) ?></span>
            </td>
            <td style="color: var(--text-main); line-height: 1.5;">
                <?= htmlspecialchars($plugin['description']) ?>
                
                <?php if (!empty($plugin['subplugins'])): ?>
                <div style="margin-top: 15px;">
                    <a href="#" onclick="event.preventDefault(); var el = document.getElementById('subplugins-<?= $plugin['folder'] ?>'); el.style.display = (el.style.display === 'none') ? 'block' : 'none';" style="text-decoration: none; color: var(--accent-green); font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        Exibir Componentes/Módulos (<?= count($plugin['subplugins']) ?>) ▾
                    </a>
                    <div id="subplugins-<?= $plugin['folder'] ?>" style="display: none; margin-top: 10px; padding: 15px; background: rgba(0,210,132,0.05); border-left: 3px solid var(--accent-green); border-radius: 0 6px 6px 0;">
                        <ul style="margin: 0; padding-left: 20px; color: var(--text-main);">
                        <?php foreach($plugin['subplugins'] as $sub): ?>
                            <li style="margin-bottom: 8px;">
                                <strong style="color: var(--text-main);"><?= htmlspecialchars($sub['name']) ?></strong> 
                                <span class="badge" style="background: rgba(88,166,255,0.1); border: 1px solid var(--accent-blue); color: var(--accent-blue); padding: 2px 6px; font-size: 10px; margin-left: 5px;">v<?= htmlspecialchars($sub['version']) ?></span>
                                <br><span style="font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($sub['description']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($plugin['is_disarmed']): ?>
                <div style="margin-top: 10px; padding: 10px; background: rgba(245,110,40,0.1); color: var(--text-main); border-radius: 4px; border: 1px dashed var(--accent-orange); font-size: 13px;">
                    <strong style="color: var(--accent-orange);">⚠️ Plugin Danificado:</strong> Este plugin causou um erro fatal e foi desconectado pelo Disjuntor.<br>
                    <a href="admin/monitor" style="color: var(--accent-orange); font-weight: bold; text-decoration: underline;">Verificar Monitor de Erros</a>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <form method="POST" action="admin/plugins/toggle" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        
                        <?php if ($plugin['is_core']): ?>
                            <button type="button" class="btn btn-core" disabled style="opacity: 0.5;">Núcleo</button>
                        <?php elseif ($plugin['is_active']): ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn">Desativar</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-activate">Ativar</button>
                        <?php endif; ?>
                    </form>

                    <?php if (!$plugin['is_core'] && !$plugin['is_active']): ?>
                    <form method="POST" action="admin/plugins/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir o plugin <?= htmlspecialchars($plugin['name']) ?>? Isso apagará a pasta dele.');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        <input type="hidden" name="plugin_folder" value="<?= htmlspecialchars($plugin['folder']) ?>">
                        <button type="submit" class="btn btn-deactivate">Excluir</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($plugin['folder'] === 'clinic_pack' && $plugin['is_active']): ?>
                        <a href="<?= \BASE_URL ?>/admin/clinic/shortcodes" class="btn btn-activate" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-puzzle-piece"></i> Shortcodes
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


