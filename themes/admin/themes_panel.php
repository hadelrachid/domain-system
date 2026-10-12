<div class="wrap">
    <h1 class="wp-heading-inline">Painel de Temas</h1>
    <a href="#" class="page-title-action btn-activate" onclick="document.getElementById('createThemeModal').style.display='block'; return false;">Criar Novo Tema</a>
    <a href="#" class="page-title-action btn-activate" onclick="document.getElementById('uploadThemeModal').style.display='block'; return false;">Instalar Tema (.zip)</a>
    <p style="color: var(--text-muted); margin-bottom: 25px;">Gerencie e ative o tema visual do site público. O tema ativo define a identidade visual e os templates do seu domínio.</p>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <?php foreach ($themes as $t): ?>
            <?php if (isset($t['is_add_new']) && $t['is_add_new']): ?>
                <!-- Card de Adicionar Novo -->
                <div class="card" style="width: 300px; padding: 20px; border: 2px dashed var(--accent-blue) !important; background: rgba(88,166,255,0.05) !important; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-align: center; transition: all 0.3s;" onclick="document.getElementById('createThemeModal').style.display='block'; return false;" onmouseover="this.style.background='rgba(88,166,255,0.1) !important'; this.style.boxShadow='0 0 15px rgba(88,166,255,0.2)';" onmouseout="this.style.background='rgba(88,166,255,0.05) !important'; this.style.boxShadow='none';">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--accent-blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <h3 style="margin: 0 0 10px; font-size: 18px; color: var(--accent-blue) !important;">Criar Novo Tema</h3>
                    <p style="margin: 0; font-size: 13px; color: var(--text-muted) !important;">Iniciar um layout totalmente em branco para o projeto.</p>
                </div>
            <?php else: ?>
                <?php 
                    $isActive = !empty($t['is_active']);
                    $cardBorder = $isActive ? 'border: 2px solid #00a32a !important; box-shadow: 0 0 15px rgba(0,163,42,0.25);' : '';
                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                ?>
                <!-- Card de Tema -->
                <div class="card" style="width: 300px; padding: 20px; display: flex; flex-direction: column; position: relative; <?= $cardBorder ?>">
                    
                    <?php if ($isActive): ?>
                        <span style="position: absolute; top: 12px; right: 12px; background: #00a32a; color: #fff; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); z-index: 2;">✓ Ativo</span>
                    <?php endif; ?>

                    <?php if (!empty($t['screenshot'])): ?>
                        <div style="width: 100%; height: 150px; background: url('<?= $baseUrl ?>/themes/<?= htmlspecialchars($t['folder']) ?>/<?= htmlspecialchars($t['screenshot']) ?>') center/cover; border-radius: 4px; margin-bottom: 15px; border: 1px solid var(--border);"></div>
                    <?php else: ?>
                        <div style="width: 100%; height: 150px; background: var(--bg-deep); border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1px solid var(--border);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin: 0 0 5px; font-size: 16px; color: var(--text-main) !important; border:none !important;"><?= htmlspecialchars($t['name']) ?> <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">v<?= htmlspecialchars($t['version']) ?></span></h3>
                    
                    <?php if (!empty($t['author'])): ?>
                        <p style="margin: 0 0 10px; font-size: 12px; color: var(--accent-blue) !important;">Por <?= htmlspecialchars($t['author']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($t['description'])): ?>
                        <p style="margin: 0 0 15px; font-size: 13px; color: var(--text-muted) !important; line-height: 1.4; flex-grow: 1;"><?= htmlspecialchars($t['description']) ?></p>
                    <?php else: ?>
                        <div style="flex-grow: 1;"></div>
                    <?php endif; ?>

                    <p style="margin: 0 0 15px; font-size: 11px; color: var(--text-muted) !important;">Pasta: <code style="font-size:10px; padding:2px 4px;"><?= htmlspecialchars($t['folder']) ?></code></p>
                    
                    <div style="border-top: 1px solid var(--border); padding-top: 15px; display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                        <span style="font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); color: <?= $isActive ? '#00a32a' : ($t['is_core'] ? 'var(--accent-orange)' : ($t['is_bundled'] ? 'var(--accent-green)' : 'var(--accent-blue)')) ?>;">
                            <?= $isActive ? 'TEMA ATIVO' : ($t['is_core'] ? 'SYSTEM CORE' : ($t['is_bundled'] ? 'PLUGIN BUNDLE' : 'TEMA DISPONÍVEL')) ?>
                        </span>

                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if ($isActive): ?>
                                <a href="<?= $baseUrl !== '' ? $baseUrl : '/' ?>" target="_blank" class="btn" style="background: rgba(0,163,42,0.15); border: 1px solid #00a32a; color: #00a32a; padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 600;" title="Ver Site no Navegador">
                                    Ver Site ↗
                                </a>
                            <?php else: ?>
                                <!-- Botão ATIVAR TEMA (Estilo WordPress) -->
                                <form method="POST" action="<?= $baseUrl ?>/admin/themes/activate" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="theme_folder" value="<?= htmlspecialchars($t['folder']) ?>">
                                    <button type="submit" class="btn button-primary" style="padding: 6px 12px !important; font-size: 12px !important; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;" title="Ativar este tema no site">Ativar</button>
                                </form>

                                <?php if (!empty($t['preview_url'])): ?>
                                    <a href="<?= htmlspecialchars($t['preview_url']) ?>" target="_blank" class="btn btn-activate" style="padding: 6px 10px; font-size: 12px;" title="Visualizar Tema">
                                        Ver
                                    </a>
                                <?php endif; ?>

                                <?php if (!$t['is_core'] && !$t['is_bundled']): ?>
                                    <form method="POST" action="<?= $baseUrl ?>/admin/themes/delete" onsubmit="return confirm('Tem certeza que deseja EXCLUIR este tema? Esta ação apagará a pasta permanentemente do disco!');" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="theme_folder" value="<?= htmlspecialchars($t['folder']) ?>">
                                        <button type="submit" class="btn btn-deactivate" style="padding: 6px 10px !important; font-size: 11px !important;" title="Excluir tema">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para Criar Tema -->
<div id="createThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 9999;">
    <div class="card" style="width: 400px; margin: 100px auto; padding: 25px;">
        <h2 style="margin-top: 0; color: var(--accent-green) !important;">Criar Novo Tema</h2>
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/create">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nome do Tema <span style="color:var(--accent-orange);">*</span></label>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="text" name="theme_name" required style="width: 100%; box-sizing: border-box;">
                <p style="font-size: 11px; margin-top: 4px;">Isso vai gerar a pasta e o nome de exibição.</p>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Descrição</label>
                <textarea name="theme_description" rows="3" style="width: 100%; box-sizing: border-box;"></textarea>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Autor</label>
                <input type="text" name="theme_author" style="width: 100%; box-sizing: border-box;">
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 25px;">
                <button type="button" class="btn" onclick="document.getElementById('createThemeModal').style.display='none';">Cancelar</button>
                <button type="submit" class="btn button-primary">Gerar Scaffold</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Instalar Tema via ZIP -->
<div id="uploadThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 9999;">
    <div class="card" style="width: 400px; margin: 100px auto; padding: 25px;">
        <h2 style="margin-top: 0; color: var(--accent-blue) !important;">Fazer Upload de Tema</h2>
        <p style="font-size: 13px;">Se você tem um tema em formato .zip contendo um arquivo <code>theme.json</code>, você pode instalá-lo aqui.</p>
        
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/upload" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div style="margin: 20px 0;">
                <input type="file" name="theme_zip" accept=".zip" required style="width: 100%;">
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 25px;">
                <button type="button" class="btn" onclick="document.getElementById('uploadThemeModal').style.display='none';">Cancelar</button>
                <button type="submit" class="btn button-primary">Instalar Agora</button>
            </div>
        </form>
    </div>
</div>
