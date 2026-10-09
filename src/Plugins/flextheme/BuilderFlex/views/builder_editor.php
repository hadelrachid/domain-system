<?php
/**
 * View do Builder Flex — somente marcação.
 * CSS e JS são módulos em ../assets, empacotados pelo AssetBundle
 * e entregues pelas rotas /admin/builder/assets/builder.(css|js).
 */
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder Flex Engine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/admin/builder/assets/builder.css" rel="stylesheet">
</head>
<body>

    <div class="top-navbar">
        <div class="brand">
            <i class="fas fa-paint-brush" style="color: var(--accent-neon); font-size: 20px;"></i>
            <h2>Builder Flex <small>by FlexTheme</small></h2>
        </div>
        <div class="actions">
            <a href="<?= $baseUrl ?>/admin" class="btn btn-exit"><i class="fas fa-sign-out-alt"></i> Sair do Editor</a>
            <button type="button" class="btn btn-activate" data-action="export-json"><i class="fas fa-code"></i> JSON</button>
            <?php if (!empty($page_id)): ?>
            <button type="button" class="btn btn-activate" id="btn-save-page" style="background: #00e5ff; color: #000;"><i class="fas fa-save"></i> Salvar Página</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="builder-layout">
        <!-- PALETTE (preenchida pelo PalettePanel a partir do WidgetRegistry) -->
        <div class="builder-palette" id="builder-palette">
            <div class="palette-header" style="display: flex; flex-direction: column; padding: 10px;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <button id="toggle-palette" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; margin-right: 10px;"><i class="fas fa-chevron-left"></i></button>
                    <span class="palette-title-text" style="font-weight: bold;"><i class="fas fa-cubes"></i> VCL</span>
                </div>
                <div class="palette-tabs" style="display: flex; gap: 5px;">
                    <button class="btn-tab active" data-target="palette-list">Componentes</button>
                    <button class="btn-tab" data-target="layer-list">Camadas</button>
                </div>
            </div>
            <div class="palette-list" id="palette-list"></div>
            <div class="layer-list" id="layer-list" hidden></div>
        </div>

        <!-- CANVAS -->
        <div class="builder-canvas-wrapper">
            <div class="canvas-toolbar">
                <button class="btn btn-sm" data-action="view-desktop"><i class="fas fa-desktop"></i> Desktop</button>
                <button class="btn btn-sm" data-action="view-mobile"><i class="fas fa-mobile-alt"></i> Mobile</button>
                <div class="spacer">
                    <span class="snap-info"><i class="fas fa-th"></i> Snap: 10px</span>
                    <button class="btn btn-sm btn-clear" data-action="clear-canvas">Limpar Form</button>
                </div>
            </div>

            <div id="canvas-root"></div>
            <div id="drag-overlay"></div>
        </div>

        <!-- OBJECT INSPECTOR -->
        <div class="builder-inspector" id="builder-inspector">
            <div class="inspector-header">
                <i class="fas fa-sliders-h"></i> Object Inspector
                <button id="toggle-inspector" style="float:right; background:none; border:none; color:var(--text-secondary); cursor:pointer;"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="inspector-body" id="inspector-body"></div>
        </div>
    </div>

    <!-- Modal de exportação -->
    <div id="modalExport" class="modal-backdrop">
        <div class="modal-box">
            <h3>Código JSON Exportado</h3>
            <textarea id="exportJsonOutput" readonly></textarea>
            <div class="modal-footer">
                <button type="button" class="btn" data-action="close-export">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="context-menu" class="context-menu" style="display: none;">
        <div class="context-menu-item" data-action="ctx-bring-front"><i class="fas fa-arrow-up"></i> Trazer para Frente</div>
        <div class="context-menu-item" data-action="ctx-send-back"><i class="fas fa-arrow-down"></i> Enviar para Trás</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" data-action="ctx-duplicate"><i class="fas fa-copy"></i> Duplicar</div>
        <div class="context-menu-item ctx-danger" data-action="ctx-delete"><i class="fas fa-trash"></i> Excluir</div>
    </div>

    <script src="<?= $baseUrl ?>/admin/builder/assets/builder.js?v=<?= time() ?>"><?php if (!empty($page_id)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        // Load existing content into the editor tree
        const existingContent = <?= json_encode($content ?: '') ?>;
        if (existingContent && existingContent.startsWith('[')) {
            try {
                const parsed = JSON.parse(existingContent);
                if (BuilderFlex && BuilderFlex.editor) {
                    BuilderFlex.editor.state.tree = parsed;
                    BuilderFlex.editor.canvas.render();
                    BuilderFlex.editor.layerNavigator.render();
                } else {
                    // Espera carregar e depois tenta novamente
                    setTimeout(() => {
                        BuilderFlex.editor.state.tree = parsed;
                        BuilderFlex.editor.canvas.render();
                        BuilderFlex.editor.layerNavigator.render();
                    }, 500);
                }
            } catch (e) {
                console.error("Erro ao carregar layout salvo", e);
            }
        }
        
        // Save button
        const saveBtn = document.getElementById('btn-save-page');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                const jsonStr = JSON.stringify(BuilderFlex.editor.state.tree);
                
                fetch('<?= $baseUrl ?>/admin/builder/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        page_id: <?= $page_id ?>,
                        content: jsonStr
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        saveBtn.innerHTML = '<i class="fas fa-check"></i> Salvo!';
                        setTimeout(() => saveBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Página', 2000);
                    } else {
                        alert("Erro ao salvar: " + res.error);
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Página';
                    }
                });
            });
        }
    });
<?php endif; ?>
</script>
</body>
</html>
