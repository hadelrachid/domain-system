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
            <button type="button" class="btn btn-activate" data-action="export-json"><i class="fas fa-code"></i> Exportar JSON</button>
        </div>
    </div>

    <div class="builder-layout">
        <!-- PALETTE (preenchida pelo PalettePanel a partir do WidgetRegistry) -->
        <div class="builder-palette">
            <div class="palette-header"><i class="fas fa-cubes"></i> VCL Components</div>
            <div class="palette-list" id="palette-list"></div>
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
        <div class="builder-inspector">
            <div class="inspector-header"><i class="fas fa-sliders-h"></i> Object Inspector</div>
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

    <script src="<?= $baseUrl ?>/admin/builder/assets/builder.js"></script>
</body>
</html>
