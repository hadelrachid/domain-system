<div class="wrap">
    <h1 class="wp-heading-inline">Painel de Temas</h1>
    <a href="#" class="page-title-action" onclick="document.getElementById('createThemeModal').style.display='block'; return false;">Criar Novo Tema</a>
    <p>Aqui você pode ver e criar interfaces dinâmicas (CockPITs) instaladas no sistema. Cada tema isola a interface de um perfil de usuário.</p>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="background: <?= $_SESSION['flash_message']['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $_SESSION['flash_message']['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 12px; margin-bottom: 20px;">
            <p style="margin: 0; color: #1d2327; font-weight: 600;"><?= htmlspecialchars($_SESSION['flash_message']['msg']) ?></p>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <?php foreach ($themes as $t): ?>
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

                <p style="margin: 0 0 15px; font-size: 12px; color: #646970;">Dir: <code>/themes/<?= htmlspecialchars($t['folder']) ?></code></p>
                
                <div style="border-top: 1px solid #f0f0f1; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; font-weight: 600; color: <?= $t['is_core'] ? '#d63638' : '#2271b1' ?>;">
                        <?= $t['is_core'] ? 'SYSTEM CORE' : 'ATIVO E ISOLADO' ?>
                    </span>

                    <?php if (!$t['is_core']): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/themes/delete" onsubmit="return confirm('Tem certeza que deseja EXCLUIR este tema? Esta ação apagará a pasta permanentemente do disco!');" style="margin: 0;">
                            <input type="hidden" name="theme_folder" value="<?= htmlspecialchars($t['folder']) ?>">
                            <button type="submit" class="btn btn-deactivate" style="border: none; background: transparent; cursor: pointer; color: #d63638; text-decoration: underline; font-size: 12px; padding: 0;">Excluir</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
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
