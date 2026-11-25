<h1>
    Plugins
    <button type="button" class="page-title-action" onclick="document.getElementById('upload-form').style.display='block'">Adicionar Novo</button>
    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/check-updates" style="display: inline-block; margin-left: 10px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <button type="submit" class="page-title-action" style="background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
            Verificar Atualizações
        </button>
    </form>
</h1>

<script>
window.confirmPluginAction = function(action, form, name, version) {
    if (!form) return true;
    if (form.dataset && form.dataset.confirmed === '1') {
        return true;
    }

    var isReinstall = action === 'reinstall';
    var title = isReinstall ? '🔄 Sincronizar / Reinstalar Plugin' : (action === 'delete' ? '⚠️ Excluir Plugin' : '🚀 Atualizar Plugin');
    var confirmText = isReinstall ? 'Sincronizar Agora' : (action === 'delete' ? 'Excluir' : 'Atualizar Agora');
    var confirmBg = action === 'delete' ? '#ef4444' : '#2271b1';
    
    var msg = '';
    if (action === 'delete') {
        msg = 'Deseja realmente excluir permanentemente o plugin "' + name + '"? Esta ação removerá a pasta do plugin e não poderá ser desfeita.';
    } else if (isReinstall) {
        msg = 'O plugin "' + name + '" já está na versão v' + version + '. Deseja baixar novamente o pacote oficial e sincronizar os arquivos?';
    } else {
        msg = 'Deseja atualizar o plugin "' + name + '" para a versão v' + version + '? O processo baixará e substituirá os arquivos pelo pacote estável oficial do repositório.';
    }

    if (window.OS && typeof window.OS.confirm === 'function') {
        window.OS.confirm(msg, function() {
            form.dataset.confirmed = '1';
            form.submit();
        }, {
            title: title,
            confirmText: confirmText,
            confirmBg: confirmBg
        });
        return false;
    }

    return confirm(msg);
};
</script>

<?php if (!empty($crashes)): ?>
    <?php foreach ($crashes as $crash): ?>
        <div class="alert alert-error" style="padding: 15px; margin: 20px 0; border-radius: 4px; border-left: 4px solid #d63638; background: #fff; color: #d63638; font-weight: bold;">
            ❌ O plugin "<?php echo htmlspecialchars($crash['plugin']); ?>" falhou fatalmente ao ser carregado e foi DESATIVADO automaticamente por segurança!<br>
            <span style="font-size: 12px; font-weight: normal; color: #555;">Erro: <?php echo htmlspecialchars($crash['error']); ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div id="upload-form" class="upload-box" style="display: none; background: var(--card); border: 1px solid var(--border); margin-bottom: 20px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px;">
    <h3 style="margin-top: 0;">Fazer Upload de Plugin</h3>
    <p style="color: var(--text-muted);">Se você possui um plugin em formato .zip, você pode instalá-lo fazendo o upload do arquivo aqui.</p>
    
    <div id="upload-progress" style="display: none; margin: 15px 0;">
        <div style="font-size: 14px; margin-bottom: 5px; color: var(--text);">Descompactando e ligando módulos...</div>
        <div style="width: 100%; background: var(--bg); border-radius: 4px; overflow: hidden; border: 1px solid #c3c4c7;">
            <div id="progress-bar-fill" style="width: 0%; height: 20px; background: #2271b1; transition: width 0.5s ease;"></div>
        </div>
    </div>

    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/upload" enctype="multipart/form-data" id="form-upload-plugin" onsubmit="
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
    .plugin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; margin-bottom: 30px; }
    .plugin-table th, .plugin-table td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: top; }
    .plugin-table th { background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; }
    .plugin-table tr:last-child td { border-bottom: none; }
    .plugin-row-active td { background-color: rgba(88,166,255,0.05); }
    .plugin-row-active td:first-child { border-left: 4px solid var(--accent-blue); }
    .plugin-row-disarmed td { background-color: rgba(245,110,40,0.05); }
    
    .core-toggle-btn { background: transparent; border: 1px dashed var(--border); color: var(--text-muted); padding: 10px 20px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-bottom: 20px; transition: all 0.3s; }
    .core-toggle-btn:hover { background: rgba(0,0,0,0.2); color: #fff; }
</style>

<?php 
    $appPlugins = array_filter($plugins, fn($p) => !$p['is_core']);
    $corePlugins = array_filter($plugins, fn($p) => $p['is_core']);
?>

<?php if (!empty($pendingUpdatesCount) && $pendingUpdatesCount > 0): ?>
    <div style="padding: 14px 18px; margin-bottom: 25px; border-radius: 6px; border-left: 4px solid var(--accent-blue); background: rgba(88, 166, 255, 0.08); display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px; color: var(--text-main);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span><strong><?= $pendingUpdatesCount ?> atualização(ões) de extensão disponível(is)!</strong> Você pode atualizar cada plugin individualmente com um clique abaixo diretamente do repositório oficial.</span>
        </div>
    </div>
<?php endif; ?>

<h2 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Aplicativos e Extensões</h2>
<p style="color: var(--text-muted); margin-bottom: 20px;">Plugins públicos e integrações de terceiros.</p>

<table class="plugin-table">
    <thead>
        <tr>
            <th style="width: 25%;">Aplicativo / Módulo</th>
            <th style="width: 10%;">Versão</th>
            <th style="width: 45%;">Descrição</th>
            <th style="width: 20%;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($appPlugins)): ?>
            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">Nenhum aplicativo instalado no momento.</td></tr>
        <?php else: ?>
        <?php foreach ($appPlugins as $plugin): ?>
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

                <?php if (!empty($plugin['has_update'])): ?>
                <div style="margin-top: 10px; padding: 10px 14px; background: rgba(88, 166, 255, 0.08); border-left: 3px solid var(--accent-blue); border-radius: 0 4px 4px 0; font-size: 13px; color: var(--text-main);">
                    <strong>🚀 Nova versão disponível:</strong> A versão <span class="badge" style="background: rgba(88,166,255,0.2); border: 1px solid var(--accent-blue); color: var(--accent-blue); padding: 2px 6px; font-weight: bold;">v<?= htmlspecialchars($plugin['remote_version']) ?></span> está disponível para atualização.
                </div>
                <?php endif; ?>
                
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
                    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/toggle" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        
                        <?php if ($plugin['is_core']): ?>
                            <?php if ($plugin['is_disarmed']): ?>
                                <?php if ($isDevMode ?? false): ?>
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" class="btn btn-activate" style="background-color: var(--accent-orange); border-color: var(--accent-orange);">Reativar (Dev Mode)</button>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/admin/dev-mode?redirect=<?= urlencode(BASE_URL . '/admin/plugins') ?>" class="btn" style="color: var(--accent-orange); border-color: var(--accent-orange); text-decoration: none; display: inline-block; padding: 6px 12px;">Habilitar Dev Mode</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-core" disabled style="opacity: 0.5;">Núcleo</button>
                            <?php endif; ?>
                        <?php elseif ($plugin['is_active']): ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn">Desativar</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-activate">Ativar</button>
                        <?php endif; ?>
                    </form>

                    <?php if (!$plugin['is_core']): ?>
                        <?php if (!empty($plugin['has_update'])): ?>
                        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/update-remote" style="margin:0;" onsubmit="return confirmPluginAction('update', this, '<?= htmlspecialchars($plugin['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($plugin['remote_version'], ENT_QUOTES) ?>')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars($plugin['folder']) ?>">
                            <button type="submit" class="btn" style="background: #2271b1; color: #fff; border-color: #2271b1; display: inline-flex; align-items: center; gap: 5px; font-weight: 600; cursor: pointer;" title="Nova versão disponível no repositório">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Atualizar para v<?= htmlspecialchars($plugin['remote_version']) ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/update-remote" style="margin:0; display:inline;" onsubmit="return confirmPluginAction('reinstall', this, '<?= htmlspecialchars($plugin['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($plugin['version'], ENT_QUOTES) ?>')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars($plugin['folder']) ?>">
                            <button type="submit" class="btn" style="opacity: 0.85; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" title="O plugin já está na versão v<?= htmlspecialchars($plugin['version']) ?>. Clique para sincronizar ou reinstalar do repositório oficial.">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Atualizado (v<?= htmlspecialchars($plugin['version']) ?>)
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$plugin['is_core'] && !$plugin['is_active']): ?>
                    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/delete" style="margin:0;" onsubmit="return confirmPluginAction('delete', this, '<?= htmlspecialchars($plugin['name'], ENT_QUOTES) ?>')">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        <input type="hidden" name="plugin_folder" value="<?= htmlspecialchars($plugin['folder']) ?>">
                        <button type="submit" class="btn btn-deactivate" style="cursor: pointer;">Excluir</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($plugin['folder'] === 'clinic_pack' && $plugin['is_active']): ?>
                        <a href="<?= \BASE_URL ?>/admin/clinic/shortcodes" class="btn btn-activate" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-puzzle-piece"></i> Shortcodes
                        </a>
                    <?php endif; ?>

                    <?php if (($plugin['folder'] === 'domain_drop' || ($plugin['name'] ?? '') === 'domain_drop' || ($plugin['name'] ?? '') === 'DomainDrop') && $plugin['is_active']): ?>
                        <a href="<?= \BASE_URL ?>/admin/dropship" class="btn btn-activate" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); border-color: #ff4747;">
                            <i class="fas fa-satellite-dish"></i> Painel Dropship
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
                <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<button class="core-toggle-btn" onclick="document.getElementById('core-plugins-container').style.display = (document.getElementById('core-plugins-container').style.display === 'none') ? 'block' : 'none';">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
    Exibir/Ocultar Módulos Administrativos (Core)
</button>

<div id="core-plugins-container" style="display: none;">
    <h2 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Infraestrutura e Painéis (Core)</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Serviços privados e administrativos. A desativação comprometerá o sistema.</p>

    <table class="plugin-table" style="opacity: 0.85;">
        <thead>
            <tr>
                <th style="width: 25%;">Componente Core</th>
                <th style="width: 10%;">Versão</th>
                <th style="width: 45%;">Descrição</th>
                <th style="width: 20%;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($corePlugins as $plugin): ?>
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
                    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/toggle" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        
                        <?php if ($plugin['is_core']): ?>
                            <?php if ($plugin['is_disarmed']): ?>
                                <?php if ($isDevMode ?? false): ?>
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" class="btn btn-activate" style="background-color: var(--accent-orange); border-color: var(--accent-orange);">Reativar (Dev Mode)</button>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/admin/dev-mode?redirect=<?= urlencode(BASE_URL . '/admin/plugins') ?>" class="btn" style="color: var(--accent-orange); border-color: var(--accent-orange); text-decoration: none; display: inline-block; padding: 6px 12px;">Habilitar Dev Mode</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-core" disabled style="opacity: 0.5;">Núcleo</button>
                            <?php endif; ?>
                        <?php elseif ($plugin['is_active']): ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn">Desativar</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-activate">Ativar</button>
                        <?php endif; ?>
                    </form>



                    <?php if (!$plugin['is_core'] && !$plugin['is_active']): ?>
                    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/plugins/delete" style="margin:0;" onsubmit="return confirmPluginAction('delete', this, '<?= htmlspecialchars($plugin['name'], ENT_QUOTES) ?>')">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                        <input type="hidden" name="plugin_folder" value="<?= htmlspecialchars($plugin['folder']) ?>">
                        <button type="submit" class="btn btn-deactivate" style="cursor: pointer;">Excluir</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($plugin['folder'] === 'clinic_pack' && $plugin['is_active']): ?>
                        <a href="<?= \BASE_URL ?>/admin/clinic/shortcodes" class="btn btn-activate" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-puzzle-piece"></i> Shortcodes
                        </a>
                    <?php endif; ?>

                    <?php if (($plugin['folder'] === 'domain_drop' || ($plugin['name'] ?? '') === 'domain_drop' || ($plugin['name'] ?? '') === 'DomainDrop') && $plugin['is_active']): ?>
                        <a href="<?= \BASE_URL ?>/admin/dropship" class="btn btn-activate" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); border-color: #ff4747;">
                            <i class="fas fa-satellite-dish"></i> Painel Dropship
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        
        <?php endforeach; ?>
    </tbody>
</table>
</div>



