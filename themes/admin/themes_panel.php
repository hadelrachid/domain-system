<div class="wrap">
    <h1 class="wp-heading-inline">Painel de Temas</h1>
    <a href="#" class="page-title-action" onclick="document.getElementById('createThemeModal').style.display='block'; return false;">Criar Novo Tema</a>
    <a href="#" class="page-title-action" onclick="document.getElementById('uploadThemeModal').style.display='block'; return false;">Instalar Tema (.zip)</a>
    <p>Aqui você pode ver e criar interfaces dinâmicas (CockPITs) instaladas no sistema. Cada tema isola a interface de um perfil de usuário.</p>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="background: <?= $_SESSION['flash_message']['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 12px; margin-bottom: 20px;">
            <p style="margin: 0; color: #1d2327; font-weight: 600;"><?= htmlspecialchars($_SESSION['flash_message']['msg']) ?></p>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <?php foreach ($themes as $t): ?>
            <?php if (isset($t['is_add_new']) && $t['is_add_new']): ?>
                <!-- Card de Adicionar Novo -->
                <div style="background: #f0f6fc; border: 2px dashed #2271b1; width: 300px; padding: 20px; border-radius: 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-align: center; transition: all 0.2s;" onclick="document.getElementById('createThemeModal').style.display='block'; return false;" onmouseover="this.style.background='#e6f0f9'" onmouseout="this.style.background='#f0f6fc'">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <h3 style="margin: 0 0 10px; font-size: 18px; color: #2271b1;">Criar Novo Tema</h3>
                    <p style="margin: 0; font-size: 13px; color: #3c434a;">Iniciar um layout totalmente em branco para o projeto.</p>
                </div>
            <?php else: ?>
                <div style="background: #fff; border: 1px solid #c3c4c7; width: 300px; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); position: relative;">
                    
                    <?php if (!empty($t['screenshot'])): ?>
                        <div style="width: 100%; height: 150px; background: url('<?= BASE_URL ?>/themes/<?= htmlspecialchars($t['folder']) ?>/<?= htmlspecialchars($t['screenshot']) ?>') center/cover; border-radius: 4px; margin-bottom: 15px; border: 1px solid #ddd;"></div>
                    <?php else: ?>
                        <div style="width: 100%; height: 150px; background: #f0f0f1; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1px solid #ddd;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin: 0 0 5px; font-size: 16px; color: #1d2327;"><?= htmlspecialchars($t['name']) ?> <span style="font-size: 11px; font-weight: normal; color: #646970;">v<?= htmlspecialchars($t['version']) ?></span></h3>
                    
                    <?php if (!empty($t['author'])): ?>
                        <p style="margin: 0 0 10px; font-size: 12px; color: #646970;">Por <?= htmlspecialchars($t['author']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($t['description'])): ?>
                        <p style="margin: 0 0 15px; font-size: 13px; color: #3c434a; line-height: 1.4;"><?= htmlspecialchars($t['description']) ?></p>
                    <?php endif; ?>

                    <p style="margin: 0 0 15px; font-size: 12px; color: #646970;">Dir: <code><?= $t['is_bundled'] ? '/src/Plugins/'.$t['plugin'].'/themes/' : '/themes/' ?><?= htmlspecialchars($t['folder']) ?></code></p>
                    
                    <div style="border-top: 1px solid #f0f0f1; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; font-weight: 600; color: <?= $t['is_core'] ? '#d63638' : ($t['is_bundled'] ? '#00a32a' : '#2271b1') ?>;">
                            <?= $t['is_core'] ? 'SYSTEM CORE' : ($t['is_bundled'] ? 'EMPACOTADO (PLUGIN)' : 'ATIVO E ISOLADO') ?>
                        </span>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <?php if ($t['is_bundled'] && !empty($t['preview_url'])): ?>
                                <a href="<?= htmlspecialchars($t['preview_url']) ?>" target="_blank" title="Visualizar Cockpit" style="color: #2271b1; text-decoration: none; display: flex; align-items: center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!$t['is_core'] && !$t['is_bundled']): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin/themes/delete" onsubmit="return confirm('Tem certeza que deseja EXCLUIR este tema? Esta ação apagará a pasta permanentemente do disco!');" style="margin: 0;">
                                    <input type="hidden" name="theme_folder" value="<?= htmlspecialchars($t['folder']) ?>">
                                    <button type="submit" class="btn btn-deactivate" style="border: none; background: transparent; cursor: pointer; color: #d63638; text-decoration: underline; font-size: 12px; padding: 0;">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para Criar Tema -->
<div id="createThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999;">
    <div style="background: #fff; width: 400px; margin: 100px auto; padding: 20px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h2 style="margin-top: 0;">Criar Novo Tema</h2>
        <form method="POST" action="<?= BASE_URL ?>/admin/themes/create">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nome do Tema <span style="color:#d63638;">*</span></label>
                <input type="text" name="theme_name" required style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 3px; box-sizing: border-box;">
                <p style="font-size: 11px; color: #646970; margin-top: 4px;">Isso vai gerar a pasta e o nome de exibição.</p>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Descrição</label>
                <textarea name="theme_description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 3px; box-sizing: border-box;"></textarea>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Autor</label>
                <input type="text" name="theme_author" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 3px; box-sizing: border-box;">
            </div>
            <div style="display: flex; justify-content: space-between;">
                <button type="button" class="btn" onclick="document.getElementById('createThemeModal').style.display='none';">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="background: #2271b1; color: #fff;">Gerar Scaffold</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Instalar Tema via ZIP -->
<div id="uploadThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999;">
    <div style="background: #fff; width: 400px; margin: 100px auto; padding: 20px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h2 style="margin-top: 0;">Fazer Upload de Tema</h2>
        <p style="font-size: 13px; color: #646970;">Se você tem um tema em um formato .zip, você pode instalá-lo fazendo o upload aqui.</p>
        
        <form method="POST" action="<?= BASE_URL ?>/admin/themes/upload" enctype="multipart/form-data">
            <div style="margin-bottom: 20px; padding: 20px; border: 2px dashed #c3c4c7; text-align: center; border-radius: 4px;">
                <input type="file" name="theme_zip" accept=".zip" required id="theme_zip_input">
            </div>
            
            <div style="display: flex; justify-content: space-between;">
                <button type="button" class="btn" onclick="document.getElementById('uploadThemeModal').style.display='none';">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="background: #2271b1; color: #fff;">Instalar Agora</button>
            </div>
        </form>
    </div>
</div>
