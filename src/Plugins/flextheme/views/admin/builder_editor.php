<?php
/**
 * View: Studio do Novo Flex Builder (Elementor em Blocos FSE com Simulador Multi-Dispositivo)
 * 
 * @var \DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface $part
 * @var bool $isNew
 * @var array $presets
 * @var array $blocks
 * @var array $shortcodes
 * @var array $menus
 * @var array $tokens
 * @var array $themes
 * @var string $activeTheme
 * @var string $baseUrl
 * @var ?\DomainSystem\Core\Contracts\SessionManagerInterface $session
 */

$partData = $part->toArray();
$initialJson = json_encode($partData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$presetsJson = json_encode($presets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$shortcodesJson = json_encode(array_keys($shortcodes), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$menusJson = json_encode($menus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title>Flex Builder Studio - <?= htmlspecialchars($part->getTitle()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #0b0f17;
            --bg-panel: #131924;
            --bg-card: #1c2433;
            --border: #263346;
            --border-focus: #3b82f6;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --primary: #38bdf8;
            --accent-green: #00d284;
            --accent-purple: #a855f7;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-deep);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* TOP BAR */
        #studio-header {
            height: 56px;
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            z-index: 100;
            gap: 12px;
        }

        .header-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .header-btn:hover { background: rgba(255,255,255,0.12); border-color: var(--primary); }
        .header-btn.active { background: rgba(56, 189, 248, 0.15); border-color: var(--primary); color: var(--primary); }

        /* STUDIO MAIN WORKSPACE */
        #studio-workspace {
            flex-grow: 1;
            display: grid;
            grid-template-columns: 360px 1fr;
            height: calc(100vh - 56px);
            overflow: hidden;
            background: #06090e;
        }

        /* LEFT SIDEBAR */
        #studio-sidebar {
            background: var(--bg-panel);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .sidebar-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,0.2);
        }
        .sidebar-tab {
            flex: 1;
            padding: 12px 6px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .sidebar-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: rgba(56, 189, 248, 0.05);
        }

        .sidebar-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 16px;
        }

        /* PRESET GRID BUTTONS */
        .preset-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .preset-card:hover { border-color: var(--primary); transform: translateY(-1px); }
        .preset-card.active { border-color: var(--primary); background: rgba(56, 189, 248, 0.1); }
        .preset-grid-bars { display: flex; gap: 4px; height: 18px; width: 100%; }
        .preset-grid-col { background: rgba(255,255,255,0.2); border-radius: 2px; }

        /* BLOCKS PALETTE */
        .block-palette-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .block-palette-item:hover { border-color: var(--primary); transform: translateX(3px); background: rgba(56, 189, 248, 0.08); }
        .block-palette-icon { font-size: 18px; width: 28px; text-align: center; }

        /* CENTER CANVAS & SIMULATOR */
        #simulator-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 24px;
            overflow-y: auto;
            height: 100%;
        }

        #device-viewport {
            width: 100%;
            max-width: 100%;
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            transition: max-width 0.3s ease;
            min-height: 480px;
            display: flex;
            flex-direction: column;
        }

        #device-screen-bar {
            background: #0f141d;
            border-bottom: 1px solid var(--border);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-muted);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        #canvas-content {
            padding: 16px;
            flex-grow: 1;
        }

        /* COLUMNS AND BLOCKS IN CANVAS */
        .canvas-grid-row {
            display: grid;
            gap: 16px;
            min-height: 180px;
        }

        .canvas-column {
            background: rgba(255,255,255,0.02);
            border: 2px dashed rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 140px;
            position: relative;
            transition: all 0.2s ease;
        }
        .canvas-column.active-col {
            border: 2px solid var(--primary);
            background: rgba(56, 189, 248, 0.04);
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.15);
        }
        .canvas-column.drag-target-over {
            border-color: var(--accent-green) !important;
            background: rgba(0, 210, 132, 0.08) !important;
            box-shadow: 0 0 16px rgba(0, 210, 132, 0.25) !important;
        }

        .canvas-column-header {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding-bottom: 8px;
            margin-bottom: 4px;
        }

        .column-dropzone {
            margin-top: auto;
            border: 1px dashed rgba(56, 189, 248, 0.35);
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(56, 189, 248, 0.03);
        }
        .column-dropzone:hover {
            background: rgba(56, 189, 248, 0.12);
            border-color: var(--primary);
        }

        .canvas-block-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px;
            cursor: grab;
            transition: all 0.2s;
            position: relative;
            user-select: none;
        }
        .canvas-block-card:hover { border-color: rgba(56, 189, 248, 0.5); }
        .canvas-block-card.selected { border-color: var(--primary); box-shadow: 0 0 12px rgba(56, 189, 248, 0.25); }
        .canvas-block-card.dragging { opacity: 0.35; transform: scale(0.98); }
        .canvas-block-card.drag-insert-before { border-top: 3px solid var(--accent-green) !important; }
        .canvas-block-card.drag-insert-after { border-bottom: 3px solid var(--accent-green) !important; }

        .canvas-block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .canvas-block-actions {
            display: flex;
            gap: 4px;
        }
        .block-act-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px 4px;
            font-size: 11px;
            border-radius: 3px;
        }
        .block-act-btn:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .block-act-btn.del:hover { color: var(--danger); }

        .col-selector-bar {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
            background: rgba(0,0,0,0.3);
            padding: 6px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }
        .col-sel-btn {
            flex: 1;
            padding: 6px 4px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            background: rgba(255,255,255,0.05);
            border: 1px solid transparent;
            color: var(--text-muted);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .col-sel-btn.active {
            background: rgba(56, 189, 248, 0.15);
            border-color: var(--primary);
            color: var(--primary);
        }
        .toggle-preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            margin-top: 4px;
        }

        /* ELEMENTOR SECTIONS IN CANVAS */
        .elementor-section-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.2s ease;
        }
        .elementor-section-card.selected-section-active {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px var(--primary), 0 0 24px rgba(56, 189, 248, 0.25) !important;
        }
        .elementor-section-card.selected-section-active .elementor-section-bar {
            background: rgba(56, 189, 248, 0.15) !important;
            border-bottom-color: var(--primary) !important;
        }

        /* LAYERS / CAMADAS PANEL */
        .layer-tree-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .layer-node {
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .layer-root {
            background: rgba(255,255,255,0.04);
            padding: 8px 12px;
            border: 1px solid var(--border);
            font-size: 12px;
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .layer-section {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            margin-bottom: 6px;
            overflow: hidden;
        }
        .layer-section.active-layer {
            border-color: var(--primary);
            background: rgba(56, 189, 248, 0.08);
        }
        .layer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .layer-row:hover {
            background: rgba(255,255,255,0.06);
        }
        .layer-row.active-layer {
            background: rgba(56, 189, 248, 0.18) !important;
            color: #fff;
            font-weight: 700;
        }
        .layer-row-sec {
            background: #0f1520;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-weight: 700;
        }
        .layer-row-col {
            padding-left: 20px;
            color: var(--text-muted);
            font-size: 11px;
            border-bottom: 1px dashed rgba(255,255,255,0.04);
        }
        .layer-row-block {
            padding-left: 34px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .layer-title-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex-grow: 1;
        }
        .layer-actions {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .layer-act-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px 4px;
            font-size: 10px;
            border-radius: 3px;
        }
        .layer-act-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.15);
        }
        .layer-act-btn.del:hover {
            color: var(--danger);
        }
        .layer-badge {
            font-size: 9px;
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary);
            padding: 1px 6px;
            border-radius: 8px;
            font-weight: 700;
        }

        /* TOGGLE CARDS SELECTOR */
        .toggle-cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        .toggle-card {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 10px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .toggle-card:hover {
            border-color: rgba(56, 189, 248, 0.5);
            background: rgba(56, 189, 248, 0.05);
        }
        .toggle-card.active {
            border-color: var(--primary);
            background: rgba(56, 189, 248, 0.15);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
        }
        .toggle-card-preview {
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
            font-size: 18px;
            color: var(--primary);
        }
        .toggle-card-name {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }
        .toggle-card-desc {
            font-size: 9px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* MOBILE OVERFLOW PREVENTION (375px) */
        #device-viewport.ds-device-375,
        #device-viewport.ds-mobile-mode {
            max-width: 375px !important;
            width: 375px !important;
            overflow-x: hidden !important;
        }
        #device-viewport.ds-device-375 .canvas-grid-row,
        #device-viewport.ds-mobile-mode .canvas-grid-row {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        #device-viewport.ds-device-375 .canvas-column,
        #device-viewport.ds-mobile-mode .canvas-column {
            min-width: 0 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        #device-viewport.ds-device-375 .canvas-block-card,
        #device-viewport.ds-mobile-mode .canvas-block-card {
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            word-break: break-word !important;
        }
        #device-viewport.ds-device-375 .elementor-section-card,
        #device-viewport.ds-mobile-mode .elementor-section-card {
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        .elementor-section-bar {
            background: #0f1520;
            border-bottom: 1px solid var(--border);
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .elementor-sec-title {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .elementor-sec-badge {
            font-size: 10px;
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 700;
        }
        .elementor-sec-actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .sec-act-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sec-act-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.12);
            border-color: var(--primary);
        }
        .sec-act-btn.del:hover {
            color: var(--danger);
            border-color: var(--danger);
        }

        .elementor-add-section-area {
            border: 2px dashed rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 20px 16px;
            text-align: center;
            margin-top: 16px;
            background: rgba(255,255,255,0.01);
            transition: all 0.2s;
        }
        .elementor-add-section-area:hover {
            border-color: var(--primary);
            background: rgba(56, 189, 248, 0.03);
        }
        .elementor-add-section-circle {
            width: 44px;
            height: 44px;
            background: #e11d48;
            color: #fff;
            border: none;
            border-radius: 50%;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 300;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);
            transition: transform 0.2s, background 0.2s;
        }
        .elementor-add-section-circle:hover {
            transform: scale(1.08);
            background: #f43f5e;
        }
        .elementor-add-section-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
        }
        .elementor-structure-picker {
            margin-top: 14px;
            padding: 14px;
            background: #111722;
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        .elementor-structure-grid {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .structure-item-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            cursor: pointer;
            width: 135px;
            text-align: center;
            transition: all 0.2s;
        }
        .structure-item-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            background: rgba(56, 189, 248, 0.08);
        }
        .structure-bars {
            display: flex;
            gap: 3px;
            height: 26px;
            margin-bottom: 6px;
        }
        .structure-col-bar {
            background: rgba(255,255,255,0.25);
            border-radius: 3px;
        }

        /* FORM CONTROLS IN INSPECTOR */
        .inspector-field {
            margin-bottom: 14px;
        }
        .inspector-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .inspector-input, .inspector-select, .inspector-textarea {
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: #fff;
            padding: 7px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }
        .inspector-input:focus, .inspector-select:focus, .inspector-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .dimension-row {
            display: flex;
            gap: 6px;
        }
        .dimension-row input { flex: 2; }
        .dimension-row select { flex: 1; }

        /* TOAST */
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #10b981;
            color: #000;
            font-weight: 700;
            font-size: 13px;
            padding: 10px 18px;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <header id="studio-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="<?= $baseUrl ?>/admin/builder" class="header-btn" title="Voltar ao Painel">
                ← Flex Builder
            </a>
            <input type="text" id="part_title_input" value="<?= htmlspecialchars($part->getTitle()) ?>" 
                   style="background: transparent; border: 1px solid transparent; color: #fff; font-size: 15px; font-weight: 700; padding: 4px 8px; border-radius: 4px; width: 200px;"
                   onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='transparent'">
            <span style="background: rgba(56, 189, 248, 0.15); color: var(--primary); font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 12px; text-transform: uppercase;">
                <?= htmlspecialchars(strtoupper($part->getType())) ?>
            </span>
        </div>

        <!-- SIMULADOR MULTI-DISPOSITIVO -->
        <div style="display: flex; align-items: center; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 2px;">
            <button type="button" class="header-btn active" id="btn_dev_desktop" onclick="setDevice('100%')" title="Desktop (100%)">
                💻 Desktop
            </button>
            <button type="button" class="header-btn" id="btn_dev_laptop" onclick="setDevice('1024px')" title="Laptop (1024px)">
                💻 1024px
            </button>
            <button type="button" class="header-btn" id="btn_dev_tablet" onclick="setDevice('768px')" title="Tablet (768px)">
                📱 768px
            </button>
            <button type="button" class="header-btn" id="btn_dev_mobile" onclick="setDevice('375px')" title="Mobile (375px)">
                📱 375px
            </button>
        </div>

        <!-- MODO DE VISUALIZAÇÃO: ESTRUTURA VS AO VIVO -->
        <div style="display: flex; align-items: center; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 2px;">
            <button type="button" class="header-btn active" id="btn_mode_structure" onclick="setStudioMode('structure')" title="Modo Construtor de Estrutura e Blocos">
                🧱 Estrutura
            </button>
            <button type="button" class="header-btn" id="btn_mode_live" onclick="setStudioMode('live')" title="Modo Visual Interativo com clique real no Hamburguer">
                👁️ Ao Vivo
            </button>
        </div>

        <!-- AÇÕES -->
        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="header-btn" onclick="toggleLivePreviewModal()" title="Ver código / Preview Modal">
                🔍 Modal
            </button>
            <button type="button" id="btn_save" class="header-btn" onclick="savePart()" style="background: var(--accent-green); color: #000; border-color: var(--accent-green); font-weight: 700;">
                💾 Salvar
            </button>
        </div>
    </header>

    <!-- MAIN WORKSPACE -->
    <main id="studio-workspace">
        
        <!-- SIDEBAR -->
        <aside id="studio-sidebar">
            <div class="sidebar-tabs">
                <div class="sidebar-tab active" id="tab_btn_layout" onclick="switchSidebarTab('layout')">Divisórias</div>
                <div class="sidebar-tab" id="tab_btn_layers" onclick="switchSidebarTab('layers')">Camadas</div>
                <div class="sidebar-tab" id="tab_btn_blocks" onclick="switchSidebarTab('blocks')">+ Blocos</div>
                <div class="sidebar-tab" id="tab_btn_inspector" onclick="switchSidebarTab('inspector')">Inspetor</div>
                <div class="sidebar-tab" id="tab_btn_settings" onclick="switchSidebarTab('settings')">Config</div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_layout">
                <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 8px;">Seção Sendo Configurada</div>
                <div id="sidebar_sec_selector_bar" style="display:flex; gap:6px; margin-bottom:14px; flex-wrap:wrap;"></div>

                <div class="inspector-field">
                    <label class="inspector-label">Nome de Identificação da Seção</label>
                    <input type="text" id="sec_title_input" class="inspector-input" oninput="updateActiveSecTitle(this.value)" placeholder="ex: Topbar, Header Principal, Sub-header">
                </div>

                <div style="font-size: 12px; font-weight: 700; color: #fff; margin-top: 14px; margin-bottom: 8px;">Estrutura de Divisórias Desta Seção</div>
                <div id="presets_list"></div>

                <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 12px;">Espaçamentos e Dimensões da Seção</div>
                    
                    <div class="inspector-field">
                        <label class="inspector-label">Margem Externa (Margin - Afastamento Entre Seções)</label>
                        <input type="text" id="setting_margin" class="inspector-input" value="0px 0px 16px 0px" oninput="updateActiveSecSetting('margin', this.value)" placeholder="ex: 0px 0px 20px 0px">
                        <span style="font-size:10px; color:var(--text-muted); display:block; margin-top:3px;">Afasta esta seção das outras seções acima/abaixo.</span>
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Preenchimento Interno (Padding)</label>
                        <input type="text" id="setting_padding" class="inspector-input" value="16px 24px" oninput="updateActiveSecSetting('padding', this.value)" placeholder="ex: 20px 24px">
                        <span style="font-size:10px; color:var(--text-muted); display:block; margin-top:3px;">Espaço interno antes das colunas/células.</span>
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Espaçamento Entre Células (Gap)</label>
                        <input type="text" id="setting_gap" class="inspector-input" value="24px" oninput="updateActiveSecSetting('gap', this.value)" placeholder="ex: 24px">
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Largura Máxima do Container (Boxed)</label>
                        <input type="text" id="setting_max_width" class="inspector-input" value="1280px" oninput="updateActiveSecSetting('max_width', this.value)" placeholder="ex: 1280px ou 100%">
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Cor de Fundo da Seção (Background)</label>
                        <input type="text" id="setting_background" class="inspector-input" value="transparent" oninput="updateActiveSecSetting('background', this.value)" placeholder="ex: transparent, #0f1520">
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Alinhamento Vertical das Colunas</label>
                        <select id="setting_align_items" class="inspector-select" onchange="updateActiveSecSetting('align_items', this.value)">
                            <option value="center">Centralizado (center)</option>
                            <option value="flex-start">Topo (flex-start)</option>
                            <option value="flex-end">Base (flex-end)</option>
                            <option value="stretch">Esticado (stretch)</option>
                        </select>
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Alinhamento Horizontal das Divisórias (Header)</label>
                        <select id="setting_justify_content" class="inspector-select" onchange="updateActiveSecSetting('justify_content', this.value)">
                            <option value="space-between">Espalhado / Distribuído (Space-Between) - Padrão Header</option>
                            <option value="center">Centralizado no Header (Center)</option>
                            <option value="flex-start">Alinhado à Esquerda (Flex-Start)</option>
                            <option value="flex-end">Alinhado à Direita (Flex-End)</option>
                            <option value="space-around">Espaço por Igual nas Pontas (Space-Around)</option>
                        </select>
                        <span style="font-size:10px; color:var(--text-muted); display:block; margin-top:3px;">Controla como as colunas do header são distribuídas na largura total.</span>
                    </div>
                </div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_layers" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="font-size: 12px; font-weight: 700; color: #fff;">Árvore de Camadas (Navigator)</div>
                    <span style="font-size: 10px; background: rgba(56,189,248,0.15); color: var(--primary); padding: 2px 6px; border-radius: 8px; font-weight: 700;">HIERARQUIA</span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 12px;">Visualize, ordene e selecione qualquer seção, célula ou bloco da estrutura.</p>
                <div id="layers_tree_container" class="layer-tree-container"></div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_blocks" style="display: none;">
                <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 12px;">Catálogo de 12 Blocos Nativos</div>
                <div id="blocks_catalog"></div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_inspector" style="display: none;">
                <div id="inspector_container">
                    <div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 40px 10px;">
                        👈 Clique em qualquer bloco no canvas para inspecionar e editar suas propriedades aqui.
                    </div>
                </div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_settings" style="display: none;">
                <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 14px;">Configurações da Parte</div>
                
                <div class="inspector-field">
                    <label class="inspector-label">Tipo da Parte</label>
                    <input type="text" class="inspector-input" value="<?= htmlspecialchars($part->getType()) ?>" disabled style="opacity: 0.6;">
                </div>

                <div class="inspector-field">
                    <label class="inspector-label">Estilo do Toggle Hamburguer Mobile</label>
                    <div id="settings_toggle_cards" class="toggle-cards-grid">
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'classic' ? 'active' : '' ?>" id="toggle_card_classic" onclick="selectGlobalToggleStyle('classic')">
                            <div class="toggle-card-preview">☰</div>
                            <div class="toggle-card-name">Clássico</div>
                            <div class="toggle-card-desc">3 Barras</div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'pill' ? 'active' : '' ?>" id="toggle_card_pill" onclick="selectGlobalToggleStyle('pill')">
                            <div class="toggle-card-preview" style="font-size:11px; font-weight:800; border:1px solid currentColor; border-radius:12px; padding:2px 8px; width:fit-content; margin:0 auto 4px;">MENU ☰</div>
                            <div class="toggle-card-name">Pílula</div>
                            <div class="toggle-card-desc">Texto + Ícone</div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'minimal' ? 'active' : '' ?>" id="toggle_card_minimal" onclick="selectGlobalToggleStyle('minimal')">
                            <div class="toggle-card-preview">═</div>
                            <div class="toggle-card-name">Minimalista</div>
                            <div class="toggle-card-desc">2 Linhas</div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'dots' ? 'active' : '' ?>" id="toggle_card_dots" onclick="selectGlobalToggleStyle('dots')">
                            <div class="toggle-card-preview">⋮</div>
                            <div class="toggle-card-name">Pontos</div>
                            <div class="toggle-card-desc">3 Pontos</div>
                        </div>
                    </div>
                    <input type="hidden" id="setting_mobile_toggle" value="<?= htmlspecialchars($part->getMobileToggleStyle()) ?>">
                </div>

                <div class="inspector-field">
                    <label class="inspector-label">Vincular Diretamente ao Tema</label>
                    <select id="setting_assigned_theme" class="inspector-select">
                        <option value="">Nenhum (usar padrão ou vincular depois)</option>
                        <option value="<?= htmlspecialchars($activeTheme) ?>" selected>Tema Ativo Atual (<?= htmlspecialchars($activeTheme) ?>)</option>
                        <?php foreach ($themes as $th): ?>
                            <?php if ($th['folder'] !== $activeTheme): ?>
                                <option value="<?= htmlspecialchars($th['folder']) ?>"><?= htmlspecialchars($th['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inspector-field" style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #fff; cursor: pointer;">
                        <input type="checkbox" id="setting_is_default" <?= $part->isDefault() ? 'checked' : '' ?> onchange="state.is_default = this.checked;">
                        Definir como Padrão Global do Sistema
                    </label>
                </div>
            </div>
        </aside>

        <!-- CANVAS & SIMULADOR -->
        <section id="simulator-container">
            <div id="device-viewport">
                <div id="device-screen-bar">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>Simulador: <strong id="cur_device_name">Desktop (100%)</strong></span>
                        <span id="cur_device_px" style="background: rgba(255,255,255,0.06); padding: 1px 6px; border-radius: 4px; font-size: 10px;">100%</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span id="active_sections_count_badge" style="font-size: 11px; color: var(--primary); font-weight: 700;">1 Seção</span>
                    </div>
                </div>
                <div id="canvas-content">
                    <div id="sections_stage"></div>
                    <div id="elementor_add_section_wrapper"></div>
                </div>
                <div id="live-stage-container" style="display: none; flex-grow: 1; width: 100%; min-height: 480px;">
                    <iframe id="live_stage_iframe" style="width: 100%; height: 100%; border: none; min-height: 480px; background: #06090e;"></iframe>
                </div>
            </div>
        </section>

    </main>

    <!-- MODAL DE PREVIEW REAL -->
    <div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(5px); z-index: 9999; padding: 24px;">
        <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; max-width: 1200px; height: 90vh; margin: 0 auto; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 12px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <strong style="color: #fff; font-size: 15px;">Pré-Visualização Real em HTML & CSS Tokens</strong>
                    <span id="preview_device_badge" style="background: rgba(56, 189, 248, 0.15); color: var(--primary); font-size: 11px; padding: 2px 8px; border-radius: 10px;">100% Desktop</span>
                </div>
                <div style="display: flex; align-items: center; gap: 4px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 2px;">
                    <button type="button" class="header-btn" onclick="setPreviewModalDevice('100%')" title="Desktop">💻 100%</button>
                    <button type="button" class="header-btn" onclick="setPreviewModalDevice('1024px')" title="Laptop">💻 1024px</button>
                    <button type="button" class="header-btn" onclick="setPreviewModalDevice('768px')" title="Tablet">📱 768px</button>
                    <button type="button" class="header-btn" onclick="setPreviewModalDevice('375px')" title="Mobile">📱 375px</button>
                </div>
                <div style="display: flex; align-items: center; gap: 4px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 2px;">
                    <span style="font-size:10px; color:var(--text-muted); padding:0 4px;">Alinhamento:</span>
                    <button type="button" class="header-btn" onclick="setPreviewHeaderAlign('space-between')" title="Espalhado">↔️ Espalhado</button>
                    <button type="button" class="header-btn" onclick="setPreviewHeaderAlign('center')" title="Centralizado">🎯 Centro</button>
                    <button type="button" class="header-btn" onclick="setPreviewHeaderAlign('flex-start')" title="Esquerda">⬅️ Esquerda</button>
                    <button type="button" class="header-btn" onclick="setPreviewHeaderAlign('flex-end')" title="Direita">➡️ Direita</button>
                </div>
                <button type="button" onclick="toggleLivePreviewModal()" style="background: transparent; border: none; color: #fff; font-size: 18px; cursor: pointer;">✕</button>
            </div>
            <div style="flex-grow: 1; display: flex; justify-content: center; background: #06090e; overflow-y: auto;">
                <iframe id="preview_iframe" style="width: 100%; height: 100%; border: none; background: #06090e; transition: max-width 0.3s ease;"></iframe>
            </div>
        </div>
    </div>

    <!-- INPUT GLOBAL PARA UPLOAD DE IMAGENS E LOGOS -->
    <input type="file" id="global_image_uploader" accept=".png,.svg,.jpg,.jpeg,.webp,.gif" style="display: none;" onchange="handleFileUpload(this)">

    <div id="toast"></div>

    <script>
    // ESTADO DO CONSTRUTOR MULTI-SEÇÃO
    var csrfToken = '<?= htmlspecialchars($csrfToken ?? '') ?>';
    var state = <?= $initialJson ?>;
    var presets = <?= $presetsJson ?>;
    var shortcodesList = <?= $shortcodesJson ?>;
    var menusList = <?= $menusJson ?>;
    
    // Normalização defensiva de seções (Garante que sempre exista state.sections)
    if (!state.sections || !Array.isArray(state.sections) || state.sections.length === 0) {
        var initialPreset = state.layout_preset || '3_cols_header';
        var initialCols = state.columns_data || { 0: [], 1: [], 2: [] };
        var initialSettings = state.container_settings || {
            max_width: '1280px',
            gap: '24px',
            padding: '20px 24px',
            margin: '0px 0px 16px 0px',
            align_items: 'center',
            background: 'transparent'
        };
        if (!initialSettings.margin) initialSettings.margin = '0px 0px 16px 0px';
        state.sections = [
            {
                id: 'sec_' + Math.random().toString(36).substr(2, 8),
                title: 'Seção 1',
                layout_preset: initialPreset,
                container_settings: initialSettings,
                columns_data: initialCols
            }
        ];
    }

    var activeSecIdx = 0; // Seção ativa no momento
    var activeColIdx = 0; // Coluna/Célula ativa na seção atual
    var selectedBlockCoord = null; // { sec: 0, col: 0, blockIdx: 0 }
    var currentDevice = '100%';
    var studioMode = 'structure'; // 'structure' ou 'live'
    var showStructurePicker = false;
    var currentUploadTargetKey = null;

    var blocksCatalog = [
        { type: 'heading', name: 'Título', icon: '🔤', desc: 'Títulos H1 até H6 com suporte a fontes e cores.' },
        { type: 'paragraph', name: 'Parágrafo', icon: '📄', desc: 'Texto corrido rico e tipografia escalável.' },
        { type: 'image', name: 'Imagem', icon: '🖼️', desc: 'Imagem com dimensões dinâmicas, upload e link.' },
        { type: 'video', name: 'Vídeo', icon: '🎥', desc: 'YouTube, Vimeo ou MP4 direto responsivo.' },
        { type: 'nav_menu', name: 'Menu de Navegação', icon: '🧭', desc: 'Menu dinâmico com 4 tipos de toggle hamburguer.' },
        { type: 'logo', name: 'Logotipo', icon: '🛡️', desc: 'Logo com upload de imagem ou texto estilizado.' },
        { type: 'cta_button', name: 'Botão CTA', icon: '🚀', desc: 'Botão de ação com variantes primária e outline.' },
        { type: 'shortcode', name: 'Shortcode Pronto', icon: '⚡', desc: 'Insere qualquer shortcode ativo do sistema.' },
        { type: 'divider', name: 'Divisória / Espaçador', icon: '➖', desc: 'Linha sólida, tracejada ou espaçador transparente.' },
        { type: 'social_icons', name: 'Redes Sociais', icon: '🌐', desc: 'Ícones de redes com links dinâmicos.' },
        { type: 'copyright', name: 'Copyright & LGPD', icon: '©', desc: 'Ano automático {year} e links de termos.' },
        { type: 'custom_code', name: 'Código Customizado', icon: '💻', desc: 'Editor HTML, CSS e JavaScript livre.' }
    ];

    function getMaxSectionsForType(type) {
        if (type === 'header') return 3; // Topbar, Principal, Sub-header
        if (type === 'footer') return 3; // Newsletter, Principal, Copyright
        return 6; // Seções centrais livres
    }

    var selectionType = 'none'; // 'none' | 'section' | 'block' | 'column'
    var selectedSecIdx = 0;

    function initStudio() {
        renderSidebarLayoutTab();
        renderBlocksCatalog();
        renderLayersTree();
        renderCanvas();
    }

    function switchSidebarTab(tab) {
        ['layout', 'layers', 'blocks', 'inspector', 'settings'].forEach(function(t) {
            var el = document.getElementById('sidebar_tab_' + t);
            if (el) el.style.display = (t === tab) ? 'block' : 'none';
            var btn = document.getElementById('tab_btn_' + t);
            if (btn) btn.classList.toggle('active', t === tab);
        });
        if (tab === 'layers') {
            renderLayersTree();
        }
    }

    function setStudioMode(mode) {
        studioMode = mode;
        document.getElementById('btn_mode_structure').classList.toggle('active', mode === 'structure');
        document.getElementById('btn_mode_live').classList.toggle('active', mode === 'live');

        var canvasContent = document.getElementById('canvas-content');
        var liveStage = document.getElementById('live-stage-container');

        if (mode === 'live') {
            canvasContent.style.display = 'none';
            liveStage.style.display = 'flex';
            loadLiveStageContent();
        } else {
            canvasContent.style.display = 'block';
            liveStage.style.display = 'none';
            renderCanvas();
        }
    }

    function loadLiveStageContent() {
        var iframe = document.getElementById('live_stage_iframe');
        var payload = JSON.parse(JSON.stringify(state));
        payload.csrf_token = csrfToken;

        fetch('<?= $baseUrl ?>/admin/builder/preview', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            iframe.srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{margin:0;padding:0;background:#06090e;color:#fff;font-family:sans-serif;}</style></head><body>' + html + '</body></html>';
        });
    }

    function setDevice(width) {
        currentDevice = width;
        var viewport = document.getElementById('device-viewport');
        viewport.style.maxWidth = width;
        document.getElementById('cur_device_px').textContent = width;
        
        ['desktop', 'laptop', 'tablet', 'mobile'].forEach(function(d) {
            document.getElementById('btn_dev_' + d).classList.remove('active');
        });

        viewport.classList.remove('ds-mobile-mode', 'ds-tablet-mode', 'ds-device-375', 'ds-device-768');

        if (width === '100%') {
            document.getElementById('btn_dev_desktop').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Desktop (100%)';
        } else if (width === '1024px') {
            document.getElementById('btn_dev_laptop').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Laptop (1024px)';
        } else if (width === '768px') {
            document.getElementById('btn_dev_tablet').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Tablet (768px) - 📱 Hamburguer Ativo';
            viewport.classList.add('ds-tablet-mode', 'ds-device-768');
        } else if (width === '375px') {
            document.getElementById('btn_dev_mobile').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Mobile (375px) - 📱 Hamburguer Ativo';
            viewport.classList.add('ds-mobile-mode', 'ds-device-375');
        }

        if (studioMode === 'live') {
            loadLiveStageContent();
        } else {
            renderCanvas();
        }
    }

    function setPreviewModalDevice(w) {
        var iframe = document.getElementById('preview_iframe');
        iframe.style.maxWidth = w;
        var badge = document.getElementById('preview_device_badge');
        if (badge) badge.textContent = w + (w === '375px' ? ' Mobile' : (w === '768px' ? ' Tablet' : ' Desktop'));
    }

    function updateSectionsCountBadge() {
        var el = document.getElementById('active_sections_count_badge');
        if (el) {
            var count = state.sections.length;
            var max = getMaxSectionsForType(state.type);
            el.textContent = count + ' de ' + max + ' Seções';
        }
    }

    // GESTÃO DE SEÇÕES (ELEMENTOR STYLE)
    function toggleAddSectionPicker() {
        showStructurePicker = !showStructurePicker;
        renderCanvas();
    }

    function addSection(presetKey) {
        var maxSec = getMaxSectionsForType(state.type);
        if (state.sections.length >= maxSec) {
            alert('Limite máximo de ' + maxSec + ' seções atingido para ' + state.type + '.');
            return;
        }

        var p = presets[presetKey] || presets['1_col'];
        var cols = {};
        for (var i = 0; i < p.columns; i++) {
            cols[i] = [];
        }

        var newSec = {
            id: 'sec_' + Math.random().toString(36).substr(2, 8),
            title: 'Seção ' + (state.sections.length + 1),
            layout_preset: presetKey,
            container_settings: {
                max_width: '1280px',
                gap: '24px',
                padding: '16px 24px',
                margin: '0px 0px 16px 0px',
                align_items: 'center',
                background: 'transparent'
            },
            columns_data: cols
        };

        state.sections.push(newSec);
        activeSecIdx = state.sections.length - 1;
        activeColIdx = 0;
        selectedBlockCoord = null;
        showStructurePicker = false;
        
        renderCanvas();
        renderSidebarLayoutTab();
        renderBlocksCatalog();
        showToast('Nova Seção com ' + p.columns + ' divisões criada!');
    }

    function moveSection(secIdx, dir, e) {
        if (e) e.stopPropagation();
        var targetIdx = secIdx + dir;
        if (targetIdx >= 0 && targetIdx < state.sections.length) {
            var sec = state.sections.splice(secIdx, 1)[0];
            state.sections.splice(targetIdx, 0, sec);
            activeSecIdx = targetIdx;
            renderCanvas();
            renderSidebarLayoutTab();
            showToast('Seção reordenada!');
        }
    }

    function duplicateSection(secIdx, e) {
        if (e) e.stopPropagation();
        var maxSec = getMaxSectionsForType(state.type);
        if (state.sections.length >= maxSec) {
            alert('Não é possível duplicar: limite de ' + maxSec + ' seções atingido.');
            return;
        }

        var copy = JSON.parse(JSON.stringify(state.sections[secIdx]));
        copy.id = 'sec_' + Math.random().toString(36).substr(2, 8);
        copy.title = (copy.title || 'Seção') + ' (Cópia)';
        state.sections.splice(secIdx + 1, 0, copy);
        activeSecIdx = secIdx + 1;
        renderCanvas();
        renderSidebarLayoutTab();
        showToast('Seção duplicada!');
    }

    function deleteSection(secIdx, e) {
        if (e) {
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
            if (typeof e.preventDefault === 'function') e.preventDefault();
        }

        var sec = state.sections[secIdx];
        if (!sec) return;

        // Verifica se a seção tem blocos reais dentro dela
        var hasBlocks = false;
        if (sec.columns_data) {
            for (var colKey in sec.columns_data) {
                if (Array.isArray(sec.columns_data[colKey]) && sec.columns_data[colKey].length > 0) {
                    hasBlocks = true;
                    break;
                }
            }
        }

        if (hasBlocks) {
            if (!confirm('Esta seção contém blocos adicionados. Deseja realmente excluir esta seção e todos os seus blocos?')) {
                return;
            }
        }

        state.sections.splice(secIdx, 1);

        if (state.sections.length === 0) {
            activeSecIdx = 0;
            activeColIdx = 0;
            selectedBlockCoord = null;
            selectionType = 'none';
            showStructurePicker = true; // Abre automaticamente as divisórias para adicionar uma nova
            showToast('Seção excluída com sucesso! Selecione uma nova estrutura abaixo.');
        } else {
            if (activeSecIdx >= state.sections.length) {
                activeSecIdx = Math.max(0, state.sections.length - 1);
            }
            activeColIdx = 0;
            selectedBlockCoord = null;
            selectionType = 'none';
            showToast('Seção excluída com sucesso.');
        }

        renderCanvas();
        renderSidebarLayoutTab();
        renderLayersTree();
    }

    function selectSectionForConfig(secIdx, e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        selectionType = 'section';
        selectedSecIdx = secIdx;
        activeSecIdx = secIdx;
        activeColIdx = 0;
        selectedBlockCoord = null;
        renderCanvas();
        renderSidebarLayoutTab();
        renderLayersTree();
        switchSidebarTab('layout');
    }

    function changeSectionPreset(presetKey) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        curSec.layout_preset = presetKey;
        var p = presets[presetKey];
        if (!curSec.columns_data) curSec.columns_data = {};
        for (var i = 0; i < p.columns; i++) {
            if (!curSec.columns_data[i]) curSec.columns_data[i] = [];
        }
        if (activeColIdx >= p.columns) {
            activeColIdx = 0;
        }
        renderCanvas();
        renderSidebarLayoutTab();
        renderBlocksCatalog();
    }

    function updateActiveSecTitle(val) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        curSec.title = val;
        renderCanvas();
    }

    function updateActiveSecSetting(key, val) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        if (!curSec.container_settings) curSec.container_settings = {};
        curSec.container_settings[key] = val;
        renderCanvas();
    }

    function renderSidebarLayoutTab() {
        var bar = document.getElementById('sidebar_sec_selector_bar');
        if (bar) bar.innerHTML = '';
        var maxSec = getMaxSectionsForType(state.type);

        if (!state.sections || state.sections.length === 0) {
            var titleInput = document.getElementById('sec_title_input');
            if (titleInput) titleInput.value = '';
            var container = document.getElementById('presets_list');
            if (container) container.innerHTML = '<div style="color:var(--text-muted); font-size:12px; padding:20px; text-align:center;">Nenhuma seção criada. Clique em "+ Nova Seção" para adicionar.</div>';
            return;
        }

        var curSec = state.sections[activeSecIdx] || state.sections[0];
        if (!curSec) return;

        // Seletor de pílulas das seções

        state.sections.forEach(function(s, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'col-sel-btn' + (activeSecIdx === idx ? ' active' : '');
            btn.textContent = s.title || ('Seção ' + (idx + 1));
            btn.onclick = function() {
                activeSecIdx = idx;
                activeColIdx = 0;
                renderCanvas();
                renderSidebarLayoutTab();
                renderBlocksCatalog();
            };
            bar.appendChild(btn);
        });

        if (state.sections.length < maxSec) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'header-btn';
            addBtn.style.fontSize = '11px';
            addBtn.style.padding = '4px 8px';
            addBtn.textContent = '+ Nova Seção';
            addBtn.onclick = function() {
                showStructurePicker = true;
                renderCanvas();
                var el = document.getElementById('elementor-structure-picker');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            };
            bar.appendChild(addBtn);
        }

        // Título da seção
        var titleInput = document.getElementById('sec_title_input');
        if (titleInput) titleInput.value = curSec.title || ('Seção ' + (activeSecIdx + 1));

        // Lista de presets para a seção ativa
        var container = document.getElementById('presets_list');
        container.innerHTML = '';
        for (var key in presets) {
            var p = presets[key];
            var card = document.createElement('div');
            card.className = 'preset-card' + (curSec.layout_preset === key ? ' active' : '');
            card.onclick = (function(presetKey) {
                return function() { changeSectionPreset(presetKey); };
            })(key);

            var title = document.createElement('div');
            title.style.fontSize = '12px';
            title.style.fontWeight = '600';
            title.textContent = p.name;

            var bars = document.createElement('div');
            bars.className = 'preset-grid-bars';
            for (var c = 0; c < p.columns; c++) {
                var col = document.createElement('div');
                col.className = 'preset-grid-col';
                col.style.flex = '1';
                bars.appendChild(col);
            }

            card.appendChild(title);
            card.appendChild(bars);
            container.appendChild(card);
        }

        // Espaçamentos e dimensões da seção
        var cs = curSec.container_settings || {};
        var marginEl = document.getElementById('setting_margin');
        if (marginEl) marginEl.value = cs.margin || '0px 0px 16px 0px';
        var paddingEl = document.getElementById('setting_padding');
        if (paddingEl) paddingEl.value = cs.padding || '16px 24px';
        var gapEl = document.getElementById('setting_gap');
        if (gapEl) gapEl.value = cs.gap || '24px';
        var maxWidthEl = document.getElementById('setting_max_width');
        if (maxWidthEl) maxWidthEl.value = cs.max_width || '1280px';
        var bgEl = document.getElementById('setting_background');
        if (bgEl) bgEl.value = cs.background || 'transparent';
        var alignEl = document.getElementById('setting_align_items');
        if (alignEl) alignEl.value = cs.align_items || 'center';
        var justifyEl = document.getElementById('setting_justify_content');
        if (justifyEl) justifyEl.value = cs.justify_content || 'space-between';
    }

    function renderBlocksCatalog() {
        var container = document.getElementById('blocks_catalog');
        container.innerHTML = '';

        var curSec = state.sections[activeSecIdx] || state.sections[0];
        var curPreset = presets[curSec ? curSec.layout_preset : '1_col'] || presets['1_col'];

        var secInfo = document.createElement('div');
        secInfo.style.cssText = 'font-size:11px; color:#38bdf8; font-weight:700; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;';
        secInfo.innerHTML = '<span>Inserindo em: <strong>' + escapeHtml(curSec ? curSec.title : 'Seção 1') + '</strong></span>' +
                            '<span style="background:rgba(56,189,248,0.15); padding:2px 6px; border-radius:4px;">Célula ' + (activeColIdx + 1) + '</span>';
        container.appendChild(secInfo);

        // Seletor de Células da Seção Atual
        var colSelector = document.createElement('div');
        colSelector.className = 'col-selector-bar';
        for (var c = 0; c < curPreset.columns; c++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'col-sel-btn' + (activeColIdx === c ? ' active' : '');
            btn.textContent = 'Célula ' + (c + 1);
            btn.onclick = (function(targetC) {
                return function() {
                    activeColIdx = targetC;
                    renderBlocksCatalog();
                    renderCanvas();
                };
            })(c);
            colSelector.appendChild(btn);
        }
        container.appendChild(colSelector);

        var hint = document.createElement('div');
        hint.style.fontSize = '11px';
        hint.style.color = 'var(--text-muted)';
        hint.style.marginBottom = '12px';
        hint.innerHTML = '✨ <em>Clique no bloco para adicionar na <strong>Célula ' + (activeColIdx + 1) + '</strong> ou arraste para qualquer célula do stage.</em>';
        container.appendChild(hint);

        blocksCatalog.forEach(function(b) {
            var item = document.createElement('div');
            item.className = 'block-palette-item';
            item.setAttribute('draggable', 'true');
            item.ondragstart = function(e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({ source: 'catalog', type: b.type }));
                e.dataTransfer.effectAllowed = 'copy';
            };
            item.onclick = function() { addBlockToCell(activeSecIdx, activeColIdx, b.type); };
            item.innerHTML = '<span class="block-palette-icon">' + b.icon + '</span>' +
                             '<div><strong style="display:block; font-size:13px; color:#fff;">' + b.name + '</strong>' +
                             '<span style="font-size:11px; color:var(--text-muted);">' + b.desc + '</span></div>';
            container.appendChild(item);
        });
    }

    function getColumnTitle(presetKey, colIdx, totalCols) {
        if (presetKey === '3_cols_header') {
            if (colIdx === 0) return 'Célula 1 (25% - Logo)';
            if (colIdx === 1) return 'Célula 2 (50% - Menu)';
            if (colIdx === 2) return 'Célula 3 (25% - CTA / Ação)';
        }
        if (presetKey === '2_cols_split') {
            if (colIdx === 0) return 'Célula 1 (30%)';
            if (colIdx === 1) return 'Célula 2 (70%)';
        }
        if (presetKey === '2_cols_equal') {
            return 'Célula ' + (colIdx + 1) + ' (50%)';
        }
        if (presetKey === '4_cols_footer') {
            return 'Célula ' + (colIdx + 1) + ' (25%)';
        }
        return 'Célula ' + (colIdx + 1);
    }

    // RENDERIZADOR DO STAGE (CANVAS COM SEÇÕES EMPILHADAS E BOTÃO ELEMENTOR +)
    function renderCanvas() {
        var stage = document.getElementById('sections_stage');
        if (!stage) return;
        stage.innerHTML = '';

        var maxSections = getMaxSectionsForType(state.type);

        if (state.sections.length === 0) {
            var emptyNotice = document.createElement('div');
            emptyNotice.style.cssText = 'text-align:center; padding:48px 20px; border:2px dashed rgba(255,255,255,0.12); border-radius:12px; margin-bottom:20px; background:rgba(255,255,255,0.01);';
            emptyNotice.innerHTML = '<div style="font-size:36px; margin-bottom:10px;">🔲</div>' +
                '<h4 style="color:#fff; margin:0 0 6px 0; font-size:15px;">Nenhuma Seção Ativa</h4>' +
                '<p style="color:var(--text-muted); font-size:12px; margin:0 0 16px 0;">Escolha abaixo uma estrutura de divisórias para criar sua primeira seção.</p>' +
                '<button type="button" class="header-btn" onclick="toggleAddSectionPicker()" style="padding:6px 16px; background:var(--primary); color:#000; font-weight:700;">+ Adicionar Seção</button>';
            stage.appendChild(emptyNotice);
        }

        state.sections.forEach(function(sec, secIdx) {
            var secCard = document.createElement('div');
            var isSecSelected = (selectionType === 'section' && selectedSecIdx === secIdx);
            secCard.className = 'elementor-section-card' + (isSecSelected ? ' selected-section-active' : '');
            secCard.dataset.sec = secIdx;
            
            // Aplica margem e fundo da seção
            if (sec.container_settings) {
                if (sec.container_settings.margin) secCard.style.margin = sec.container_settings.margin;
                if (sec.container_settings.background) secCard.style.background = sec.container_settings.background;
            }

            // Barra superior da seção (Handle Elementor)
            var secHeader = document.createElement('div');
            secHeader.className = 'elementor-section-bar';
            
            var presetInfo = presets[sec.layout_preset] || presets['1_col'];
            var secTitleText = sec.title || ('Seção ' + (secIdx + 1));
            var secSelectedBadge = isSecSelected ? '<span style="background:var(--primary); color:#000; font-size:9px; padding:2px 8px; border-radius:10px; font-weight:800;">SEÇÃO SELECIONADA</span>' : '';

            secHeader.innerHTML = 
                '<div class="elementor-sec-title" onclick="selectSectionForConfig(' + secIdx + ', event)">' +
                    '<span style="font-size: 13px;">🔲</span>' +
                    '<strong>' + escapeHtml(secTitleText) + '</strong>' +
                    '<span class="elementor-sec-badge">' + presetInfo.columns + ' Células (' + presetInfo.name + ')</span>' +
                    secSelectedBadge +
                '</div>' +
                '<div class="elementor-sec-actions">' +
                    '<button type="button" class="sec-act-btn" onclick="selectSectionForConfig(' + secIdx + ', event)" title="Configurar Divisórias & Espaçamento">⚙️ Divisórias & Espaçamento</button>' +
                    '<button type="button" class="sec-act-btn" onclick="moveSection(' + secIdx + ', -1, event)" title="Subir Seção">▲</button>' +
                    '<button type="button" class="sec-act-btn" onclick="moveSection(' + secIdx + ', 1, event)" title="Descer Seção">▼</button>' +
                    '<button type="button" class="sec-act-btn" onclick="duplicateSection(' + secIdx + ', event)" title="Duplicar Seção">⎘</button>' +
                    '<button type="button" class="sec-act-btn del" onclick="deleteSection(' + secIdx + ', event)" title="Excluir Seção">✕</button>' +
                '</div>';
            secCard.appendChild(secHeader);

            // Grade de Colunas / Células da Seção (Responsiva para 375px sem vazamento)
            var gridRow = document.createElement('div');
            gridRow.className = 'canvas-grid-row';
            
            var isMobileStage = (currentDevice === '375px');
            var isTabletStage = (currentDevice === '768px');
            var templateCols = presetInfo.template || '1fr';
            if (isMobileStage) {
                templateCols = presetInfo.mobile || '1fr';
            } else if (isTabletStage) {
                templateCols = presetInfo.tablet || '1fr 1fr';
            }
            gridRow.style.gridTemplateColumns = templateCols;

            if (sec.container_settings) {
                if (sec.container_settings.gap) gridRow.style.gap = isMobileStage ? '12px' : sec.container_settings.gap;
                if (sec.container_settings.padding) gridRow.style.padding = isMobileStage ? '12px 8px' : sec.container_settings.padding;
                if (sec.container_settings.align_items) gridRow.style.alignItems = sec.container_settings.align_items;
                if (sec.container_settings.justify_content) gridRow.style.justifyContent = sec.container_settings.justify_content;
            }

            for (var colIdx = 0; colIdx < presetInfo.columns; colIdx++) {
                var colEl = document.createElement('div');
                var isColActive = (activeSecIdx === secIdx && activeColIdx === colIdx);
                colEl.className = 'canvas-column' + (isColActive ? ' active-col' : '');
                colEl.dataset.sec = secIdx;
                colEl.dataset.col = colIdx;

                colEl.onclick = (function(s, c) {
                    return function(e) {
                        activeSecIdx = s;
                        activeColIdx = c;
                        renderBlocksCatalog();
                        renderCanvas();
                    };
                })(secIdx, colIdx);

                // Drag and drop sobre a célula
                setupCellDropHandlers(colEl, secIdx, colIdx);

                var colHeader = document.createElement('div');
                colHeader.className = 'canvas-column-header';
                var colTitle = getColumnTitle(sec.layout_preset, colIdx, presetInfo.columns);
                colHeader.innerHTML = '<div style="display:flex; align-items:center; gap:6px;">' +
                                      '<span>' + colTitle + '</span>' +
                                      (isColActive ? '<span style="background:rgba(56,189,248,0.2); color:var(--primary); font-size:9px; padding:1px 6px; border-radius:10px; font-weight:800;">ATIVA</span>' : '') +
                                      '</div>' +
                                      '<button type="button" class="block-act-btn" onclick="event.stopPropagation(); openAddBlockForCell(' + secIdx + ',' + colIdx + ')" title="Adicionar Bloco">+ Bloco</button>';
                colEl.appendChild(colHeader);

                // Blocos dentro da célula
                var blocksInCol = (sec.columns_data && sec.columns_data[colIdx]) ? sec.columns_data[colIdx] : [];
                blocksInCol.forEach(function(block, blockIdx) {
                    var card = createBlockCard(block, secIdx, colIdx, blockIdx);
                    colEl.appendChild(card);
                });

                // Dropzone no rodapé da célula
                var dropzone = document.createElement('div');
                dropzone.className = 'column-dropzone';
                dropzone.innerHTML = '+ Adicionar Bloco nesta Divisão';
                dropzone.onclick = (function(s, c) {
                    return function(e) {
                        e.stopPropagation();
                        openAddBlockForCell(s, c);
                    };
                })(secIdx, colIdx);
                colEl.appendChild(dropzone);

                gridRow.appendChild(colEl);
            }

            secCard.appendChild(gridRow);
            stage.appendChild(secCard);
        });

        renderAddSectionArea(maxSections);
        updateSectionsCountBadge();
    }

    function renderAddSectionArea(maxSections) {
        var wrapper = document.getElementById('elementor_add_section_wrapper');
        if (!wrapper) return;
        wrapper.innerHTML = '';

        if (state.sections.length >= maxSections) {
            var limitMsg = document.createElement('div');
            limitMsg.style.cssText = 'padding:14px; text-align:center; font-size:12px; color:var(--text-muted); border:1px dashed rgba(255,255,255,0.1); border-radius:8px; margin-top:16px; background:rgba(0,0,0,0.2);';
            limitMsg.innerHTML = '🔒 <strong>Limite de ' + maxSections + ' seções atingido para ' + (state.type === 'header' ? 'o Header (Topbar, Principal e Sub-header)' : (state.type === 'footer' ? 'o Footer (Pré, Principal e Copyright)' : 'esta parte')) + '.</strong><br><span style="font-size:11px; opacity:0.7;">Para mais divisões, divida as seções existentes horizontalmente em colunas/células.</span>';
            wrapper.appendChild(limitMsg);
            return;
        }

        var addBox = document.createElement('div');
        addBox.className = 'elementor-add-section-area';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'elementor-add-section-circle';
        btn.innerHTML = '+';
        btn.title = 'Adicionar Nova Seção';
        btn.onclick = function() {
            toggleAddSectionPicker();
        };

        var label = document.createElement('div');
        label.className = 'elementor-add-section-label';
        label.textContent = 'Adicionar Nova Seção (' + (state.sections.length + 1) + ' de ' + maxSections + ')';

        var picker = document.createElement('div');
        picker.id = 'elementor-structure-picker';
        picker.className = 'elementor-structure-picker';
        picker.style.display = showStructurePicker ? 'block' : 'none';

        var pickerTitle = document.createElement('div');
        pickerTitle.style.cssText = 'font-size:13px; font-weight:700; color:#fff; margin-bottom:12px;';
        pickerTitle.textContent = 'Selecione a Estrutura de Divisórias para a Nova Seção:';
        picker.appendChild(pickerTitle);

        var grid = document.createElement('div');
        grid.className = 'elementor-structure-grid';

        for (var key in presets) {
            var p = presets[key];
            var item = document.createElement('div');
            item.className = 'structure-item-card';
            item.onclick = (function(presetKey) {
                return function() {
                    addSection(presetKey);
                };
            })(key);

            var bars = document.createElement('div');
            bars.className = 'structure-bars';
            for (var c = 0; c < p.columns; c++) {
                var b = document.createElement('div');
                b.className = 'structure-col-bar';
                b.style.flex = '1';
                bars.appendChild(b);
            }

            var t = document.createElement('div');
            t.style.cssText = 'font-size:11px; font-weight:700; color:#fff;';
            t.textContent = p.name;

            item.appendChild(bars);
            item.appendChild(t);
            grid.appendChild(item);
        }

        picker.appendChild(grid);

        addBox.appendChild(btn);
        addBox.appendChild(label);
        addBox.appendChild(picker);
        wrapper.appendChild(addBox);
    }

    function createBlockCard(block, secIdx, colIdx, blockIdx) {
        var card = document.createElement('div');
        var isSelected = (selectionType === 'block' && selectedBlockCoord && selectedBlockCoord.sec === secIdx && selectedBlockCoord.col === colIdx && selectedBlockCoord.blockIdx === blockIdx);
        card.className = 'canvas-block-card' + (isSelected ? ' selected' : '');
        
        // Drag-and-drop do bloco entre células e seções
        card.setAttribute('draggable', 'true');
        card.ondragstart = function(e) {
            e.stopPropagation();
            card.classList.add('dragging');
            e.dataTransfer.setData('text/plain', JSON.stringify({ source: 'card', fromSec: secIdx, fromCol: colIdx, fromIdx: blockIdx }));
            e.dataTransfer.effectAllowed = 'move';
        };

        card.ondragend = function(e) {
            card.classList.remove('dragging');
        };

        card.ondragover = function(e) {
            e.preventDefault();
            e.stopPropagation();
            card.classList.add('drag-insert-before');
        };
        card.ondragleave = function(e) {
            e.stopPropagation();
            card.classList.remove('drag-insert-before');
        };
        card.ondrop = function(e) {
            e.preventDefault();
            e.stopPropagation();
            card.classList.remove('drag-insert-before');
            var raw = e.dataTransfer.getData('text/plain');
            if (!raw) return;
            try {
                var payload = JSON.parse(raw);
                if (payload.source === 'catalog') {
                    insertBlockAtCell(secIdx, colIdx, blockIdx, payload.type);
                } else if (payload.source === 'card') {
                    moveBlockAcrossSecs(payload.fromSec, payload.fromCol, payload.fromIdx, secIdx, colIdx, blockIdx);
                }
            } catch (err) {}
        };

        card.onclick = function(e) {
            e.stopPropagation();
            activeSecIdx = secIdx;
            activeColIdx = colIdx;
            selectBlock(secIdx, colIdx, blockIdx);
        };

        var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type, icon: '📦' };

        var bHeader = document.createElement('div');
        bHeader.className = 'canvas-block-header';
        bHeader.innerHTML = '<span>' + bMeta.icon + ' ' + bMeta.name + '</span>' +
            '<div class="canvas-block-actions">' +
            '<button type="button" class="block-act-btn" onclick="moveBlockInCell(' + secIdx + ',' + colIdx + ',' + blockIdx + ',-1,event)" title="Subir">▲</button>' +
            '<button type="button" class="block-act-btn" onclick="moveBlockInCell(' + secIdx + ',' + colIdx + ',' + blockIdx + ',1,event)" title="Descer">▼</button>' +
            '<button type="button" class="block-act-btn" onclick="duplicateBlockInCell(' + secIdx + ',' + colIdx + ',' + blockIdx + ',event)" title="Duplicar">⎘</button>' +
            '<button type="button" class="block-act-btn del" onclick="deleteBlockInCell(' + secIdx + ',' + colIdx + ',' + blockIdx + ',event)" title="Excluir">✕</button>' +
            '</div>';

        var bPreview = document.createElement('div');
        bPreview.style.fontSize = '12px';
        bPreview.style.color = 'var(--text-muted)';
        bPreview.style.marginTop = '4px';
        bPreview.innerHTML = getBlockSummary(block);

        card.appendChild(bHeader);
        card.appendChild(bPreview);
        return card;
    }

    function setupCellDropHandlers(colEl, secIdx, colIdx) {
        colEl.ondragover = function(e) {
            e.preventDefault();
            e.stopPropagation();
            colEl.classList.add('drag-target-over');
        };
        colEl.ondragleave = function(e) {
            e.stopPropagation();
            colEl.classList.remove('drag-target-over');
        };
        colEl.ondrop = function(e) {
            e.preventDefault();
            e.stopPropagation();
            colEl.classList.remove('drag-target-over');
            var raw = e.dataTransfer.getData('text/plain');
            if (!raw) return;
            try {
                var payload = JSON.parse(raw);
                if (payload.source === 'catalog') {
                    addBlockToCell(secIdx, colIdx, payload.type);
                } else if (payload.source === 'card') {
                    moveBlockAcrossSecs(payload.fromSec, payload.fromCol, payload.fromIdx, secIdx, colIdx, null);
                }
            } catch (err) {}
        };
    }

    function getBlockSummary(block) {
        var d = block.data || {};
        if (block.type === 'heading') {
            return '<span style="font-weight:700; color:#fff;">' + escapeHtml(d.text || 'Título') + '</span> <span style="font-size:10px; opacity:0.6;">(' + (d.level || 'h2').toUpperCase() + ')</span>';
        }
        if (block.type === 'paragraph') {
            return escapeHtml(d.text ? d.text.replace(/<[^>]+>/g, '').substr(0, 45) + '...' : 'Parágrafo de texto');
        }
        if (block.type === 'image') {
            if (d.url) {
                return '<div style="display:flex; align-items:center; gap:8px;">' +
                       '<img src="' + escapeAttr(d.url) + '" style="height:22px; max-width:40px; object-fit:cover; border-radius:3px;">' +
                       '<span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;">' + escapeHtml(d.url) + '</span>' +
                       '</div>';
            }
            return '🖼️ Imagem (nenhum arquivo selecionado)';
        }
        if (block.type === 'video') return '🎥 ' + (d.url || 'Vídeo');
        if (block.type === 'cta_button') {
            return '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:var(--primary); color:#000;">' + 
                   escapeHtml(d.label || 'Botão CTA') + '</span>';
        }
        if (block.type === 'shortcode') return '<code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:#38bdf8;">' + escapeHtml(d.tag || '[shortcode]') + '</code>';
        if (block.type === 'logo') {
            if (d.image_url) {
                return '<div style="display:flex; align-items:center; gap:8px;">' +
                       '<img src="' + escapeAttr(d.image_url) + '" style="height:22px; max-width:80px; object-fit:contain; border-radius:2px; background:rgba(255,255,255,0.05);">' +
                       '<span style="color:#fff; font-weight:600;">' + escapeHtml(d.text || 'Logo') + '</span>' +
                       '</div>';
            }
            return '<strong style="color:#fff;">' + escapeHtml(d.text || 'DomainSystem') + '</strong>';
        }
        if (block.type === 'nav_menu') {
            var curStyle = d.toggle_style || state.mobile_toggle_style || 'classic';
            var isMobile = (currentDevice === '375px' || currentDevice === '768px');
            var toggleLabel = '☰';
            if (curStyle === 'pill') toggleLabel = 'MENU ☰';
            if (curStyle === 'minimal') toggleLabel = '═';
            if (curStyle === 'dots') toggleLabel = '⋮';

            if (isMobile) {
                return '<div style="display:flex; align-items:center; gap:8px;">' +
                       '<button type="button" class="header-btn" style="padding:2px 8px; font-size:12px; background:rgba(56,189,248,0.2); border-color:var(--primary); color:var(--primary);" onclick="event.stopPropagation(); testMobileDrawer(\'' + curStyle + '\')">' + toggleLabel + '</button>' +
                       '<span style="font-size:11px; color:#38bdf8;">📱 Mobile Hamburguer Dinâmico (' + curStyle + ')</span>' +
                       '</div>';
            } else {
                return '🧭 Menu: <strong>' + escapeHtml(d.menu_id || 'Principal') + '</strong> (Início · Cursos · Sobre · Contato)';
            }
        }
        if (block.type === 'divider') return 'Estilo: ' + (d.style || 'solid') + ' (' + (d.height || '20px') + ')';
        if (block.type === 'copyright') return '© ' + new Date().getFullYear() + ' ' + escapeHtml(d.site_name || 'Domain-System');
        return block.type;
    }

    function testMobileDrawer(style) {
        showToast('📱 Botão Toggle Mobile (' + style + ') clicado! No modo Ao Vivo você pode testar a gaveta 100% interativa.');
    }

    function selectBlock(secIdx, colIdx, blockIdx) {
        selectionType = 'block';
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        selectedBlockCoord = { sec: secIdx, col: colIdx, blockIdx: blockIdx };
        renderCanvas();
        renderInspector();
        renderLayersTree();
        switchSidebarTab('inspector');
    }

    function selectColFromLayers(secIdx, colIdx, e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        selectionType = 'column';
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        selectedBlockCoord = null;
        renderCanvas();
        renderBlocksCatalog();
        renderLayersTree();
        switchSidebarTab('blocks');
        showToast('Célula ' + (colIdx + 1) + ' selecionada. Escolha um bloco para adicionar.');
    }

    function renderLayersTree() {
        var container = document.getElementById('layers_tree_container');
        if (!container) return;
        container.innerHTML = '';

        if (!state.sections || state.sections.length === 0) {
            container.innerHTML = '<div style="color:var(--text-muted); font-size:12px; text-align:center; padding:30px 10px;">Nenhuma seção ativa na hierarquia.<br><button type="button" class="header-btn" onclick="toggleAddSectionPicker()" style="margin-top:10px; font-size:11px;">+ Adicionar Seção</button></div>';
            return;
        }

        var rootNode = document.createElement('div');
        rootNode.className = 'layer-node layer-root';
        rootNode.innerHTML = '<div class="layer-item-title" style="display:flex; align-items:center; gap:8px; width:100%;">' +
            '<span>📑</span> <strong style="color:#fff;">' + escapeHtml(state.title || 'Parte') + '</strong>' +
            '<span style="font-size:10px; color:var(--text-muted); margin-left:auto;">' + state.sections.length + ' seções</span>' +
            '</div>';
        container.appendChild(rootNode);

        var treeBody = document.createElement('div');
        treeBody.className = 'layer-tree-body';

        state.sections.forEach(function(sec, sIdx) {
            var secNode = document.createElement('div');
            var isSecActive = (selectionType === 'section' && selectedSecIdx === sIdx);
            secNode.className = 'layer-node layer-section' + (isSecActive ? ' active-layer' : '');

            var presetInfo = presets[sec.layout_preset] || presets['1_col'];
            var secTitle = sec.title || ('Seção ' + (sIdx + 1));

            var secHeader = document.createElement('div');
            secHeader.className = 'layer-row layer-row-sec' + (isSecActive ? ' active-layer' : '');
            secHeader.innerHTML = 
                '<div class="layer-title-wrap" onclick="selectSectionForConfig(' + sIdx + ', event)">' +
                    '<span class="layer-icon">🔲</span>' +
                    '<span class="layer-text">' + escapeHtml(secTitle) + '</span>' +
                    '<span class="layer-badge">' + presetInfo.columns + ' cols</span>' +
                '</div>' +
                '<div class="layer-actions">' +
                    '<button type="button" class="layer-act-btn" onclick="moveSection(' + sIdx + ', -1, event)" title="Subir">▲</button>' +
                    '<button type="button" class="layer-act-btn" onclick="moveSection(' + sIdx + ', 1, event)" title="Descer">▼</button>' +
                    '<button type="button" class="layer-act-btn" onclick="duplicateSection(' + sIdx + ', event)" title="Duplicar">⎘</button>' +
                    '<button type="button" class="layer-act-btn del" onclick="deleteSection(' + sIdx + ', event)" title="Excluir">✕</button>' +
                '</div>';
            secNode.appendChild(secHeader);

            var colsWrapper = document.createElement('div');
            colsWrapper.className = 'layer-cols-wrapper';

            for (var cIdx = 0; cIdx < presetInfo.columns; cIdx++) {
                var colNode = document.createElement('div');
                colNode.className = 'layer-node layer-col';

                var colTitle = getColumnTitle(sec.layout_preset, cIdx, presetInfo.columns);
                var blocksInCol = (sec.columns_data && sec.columns_data[cIdx]) ? sec.columns_data[cIdx] : [];

                var colHeader = document.createElement('div');
                colHeader.className = 'layer-row layer-row-col';
                colHeader.innerHTML = 
                    '<div class="layer-title-wrap" onclick="selectColFromLayers(' + sIdx + ', ' + cIdx + ', event)">' +
                        '<span class="layer-icon">▦</span>' +
                        '<span class="layer-text">' + escapeHtml(colTitle) + '</span>' +
                        '<span style="font-size:10px; color:var(--text-muted); margin-left:auto;">' + blocksInCol.length + ' blocos</span>' +
                    '</div>' +
                    '<div class="layer-actions">' +
                        '<button type="button" class="layer-act-btn" onclick="event.stopPropagation(); openAddBlockForCell(' + sIdx + ', ' + cIdx + ')" title="+ Bloco">+</button>' +
                    '</div>';
                colNode.appendChild(colHeader);

                var blocksWrapper = document.createElement('div');
                blocksWrapper.className = 'layer-blocks-wrapper';

                blocksInCol.forEach(function(block, bIdx) {
                    var isBlockActive = (selectionType === 'block' && selectedBlockCoord && selectedBlockCoord.sec === sIdx && selectedBlockCoord.col === cIdx && selectedBlockCoord.blockIdx === bIdx);
                    var bNode = document.createElement('div');
                    bNode.className = 'layer-row layer-row-block' + (isBlockActive ? ' active-layer' : '');

                    var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type, icon: '📦' };

                    bNode.innerHTML = 
                        '<div class="layer-title-wrap" onclick="selectBlock(' + sIdx + ', ' + cIdx + ', ' + bIdx + ')">' +
                            '<span class="layer-icon">' + bMeta.icon + '</span>' +
                            '<span class="layer-text">' + escapeHtml(bMeta.name) + '</span>' +
                        '</div>' +
                        '<div class="layer-actions">' +
                            '<button type="button" class="layer-act-btn" onclick="moveBlockInCell(' + sIdx + ', ' + cIdx + ', ' + bIdx + ', -1, event)" title="Subir">▲</button>' +
                            '<button type="button" class="layer-act-btn" onclick="moveBlockInCell(' + sIdx + ', ' + cIdx + ', ' + bIdx + ', 1, event)" title="Descer">▼</button>' +
                            '<button type="button" class="layer-act-btn" onclick="duplicateBlockInCell(' + sIdx + ', ' + cIdx + ', ' + bIdx + ', event)" title="Duplicar">⎘</button>' +
                            '<button type="button" class="layer-act-btn del" onclick="deleteBlockInCell(' + sIdx + ', ' + cIdx + ', ' + bIdx + ', event)" title="Excluir">✕</button>' +
                        '</div>';

                    blocksWrapper.appendChild(bNode);
                });

                colNode.appendChild(blocksWrapper);
                colsWrapper.appendChild(colNode);
            }

            secNode.appendChild(colsWrapper);
            treeBody.appendChild(secNode);
        });

        container.appendChild(treeBody);
    }

    function selectGlobalToggleStyle(val) {
        state.mobile_toggle_style = val;
        var hidden = document.getElementById('setting_mobile_toggle');
        if (hidden) hidden.value = val;
        ['classic', 'pill', 'minimal', 'dots'].forEach(function(s) {
            var el = document.getElementById('toggle_card_' + s);
            if (el) el.classList.toggle('active', s === val);
        });
        if (state.sections) {
            state.sections.forEach(function(sec) {
                if (sec.columns_data) {
                    for (var col in sec.columns_data) {
                        if (Array.isArray(sec.columns_data[col])) {
                            sec.columns_data[col].forEach(function(b) {
                                if (b.type === 'nav_menu') {
                                    b.data = b.data || {};
                                    b.data.toggle_style = val;
                                }
                            });
                        }
                    }
                }
            });
        }
        renderCanvas();
        renderLayersTree();
        showToast('Estilo do Toggle Mobile atualizado para: ' + val);
    }

    function setPreviewHeaderAlign(align) {
        if (state.sections && state.sections.length > 0) {
            var targetSec = state.sections[activeSecIdx] || state.sections[0];
            targetSec.container_settings = targetSec.container_settings || {};
            targetSec.container_settings.justify_content = align;
            var selectEl = document.getElementById('setting_justify_content');
            if (selectEl) selectEl.value = align;
            renderCanvas();
            loadLivePreviewModal();
            showToast('Alinhamento do Header ajustado para: ' + align);
        }
    }

    function renderInspector() {
        var container = document.getElementById('inspector_container');
        if (!selectedBlockCoord) {
            container.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding:40px 10px;">👈 Selecione um bloco no stage para inspecionar e editar suas propriedades aqui.</div>';
            return;
        }

        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block) return;
        if (!block.data) block.data = {};

        var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type, icon: '📦' };

        var html = '<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:10px;">' +
                   '<div style="display:flex; align-items:center; gap:8px;">' +
                   '<span style="font-size:20px;">' + bMeta.icon + '</span>' +
                   '<strong style="font-size:15px; color:#fff;">' + bMeta.name + '</strong>' +
                   '</div>' +
                   '<span style="font-size:10px; background:rgba(56,189,248,0.15); color:var(--primary); padding:2px 8px; border-radius:10px; font-weight:700;">' + (sec.title || 'Seção') + ' / Célula ' + (selectedBlockCoord.col + 1) + '</span>' +
                   '</div>';

        if (block.type === 'heading') {
            html += inspectorTextInput('Texto do Título', 'text', block.data.text || '');
            html += inspectorSelect('Nível Hierárquico', 'level', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], block.data.level || 'h2');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || '2rem');
            html += inspectorSelect('Fonte', 'font', ['primary', 'secondary'], block.data.font || 'secondary');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'left');
            html += inspectorTextInput('Cor do Título (ou token)', 'color', block.data.color || 'var(--color-primary)');
        } else if (block.type === 'paragraph') {
            html += inspectorTextarea('Conteúdo do Texto', 'text', block.data.text || '');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || '1rem');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right', 'justify'], block.data.align || 'left');
            html += inspectorTextInput('Cor do Texto', 'color', block.data.color || 'var(--color-text-main)');
        } else if (block.type === 'cta_button') {
            html += inspectorTextInput('Texto do Botão', 'label', block.data.label || 'Clique Aqui');
            html += inspectorTextInput('Link / URL de Destino', 'url', block.data.url || '#');
            html += inspectorSelect('Variante', 'variant', ['primary', 'secondary', 'outline'], block.data.variant || 'primary');
            html += inspectorDimension('Padding', 'padding', block.data.padding || '12px 24px');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'left');
        } else if (block.type === 'logo') {
            html += inspectorTextInput('Texto do Logotipo', 'text', block.data.text || 'DomainSystem');
            html += '<div class="inspector-field">' +
                    '<label class="inspector-label">Arquivo de Logotipo (PNG, SVG, JPG, WEBP)</label>' +
                    '<div style="display:flex; gap:6px; margin-bottom:8px;">' +
                    '<input type="text" class="inspector-input" value="' + escapeAttr(block.data.image_url || '') + '" oninput="updateBlockProp(\'image_url\', this.value)" placeholder="https://... ou faça upload">' +
                    '<button type="button" class="header-btn" onclick="triggerImageUpload(\'image_url\')" style="white-space:nowrap; background:rgba(56,189,248,0.2); border-color:var(--primary); color:var(--primary);">' +
                    '📁 Upload</button>' +
                    '</div>' +
                    (block.data.image_url ? '<div style="padding:8px; background:rgba(0,0,0,0.3); border-radius:4px; text-align:center;"><img src="' + escapeAttr(block.data.image_url) + '" style="max-height:44px; max-width:100%; object-fit:contain;"></div>' : '') +
                    '</div>';
            html += inspectorDimension('Altura da Logo', 'height', block.data.height || '36px');
            html += inspectorTextInput('Link de Destino', 'url', block.data.url || '/');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'left');
        } else if (block.type === 'image') {
            html += '<div class="inspector-field">' +
                    '<label class="inspector-label">Arquivo de Imagem (PNG, SVG, JPG, WEBP)</label>' +
                    '<div style="display:flex; gap:6px; margin-bottom:8px;">' +
                    '<input type="text" class="inspector-input" value="' + escapeAttr(block.data.url || '') + '" oninput="updateBlockProp(\'url\', this.value)" placeholder="https://... ou faça upload">' +
                    '<button type="button" class="header-btn" onclick="triggerImageUpload(\'url\')" style="white-space:nowrap; background:rgba(56,189,248,0.2); border-color:var(--primary); color:var(--primary);">' +
                    '📁 Upload</button>' +
                    '</div>' +
                    (block.data.url ? '<div style="padding:8px; background:rgba(0,0,0,0.3); border-radius:4px; text-align:center;"><img src="' + escapeAttr(block.data.url) + '" style="max-height:90px; max-width:100%; object-fit:contain; border-radius:4px;"></div>' : '') +
                    '</div>';
            html += inspectorTextInput('Texto Alternativo (Alt)', 'alt', block.data.alt || '');
            html += inspectorDimension('Largura', 'width', block.data.width || '100%');
            html += inspectorDimension('Borda Arredondada', 'border_radius', block.data.border_radius || '8px');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'center');
        } else if (block.type === 'video') {
            html += inspectorTextInput('URL (YouTube, Vimeo ou MP4)', 'url', block.data.url || '');
            html += inspectorSelect('Aspect Ratio', 'aspect_ratio', ['16/9', '4/3', '1/1'], block.data.aspect_ratio || '16/9');
            html += inspectorDimension('Borda Arredondada', 'border_radius', block.data.border_radius || '8px');
        } else if (block.type === 'shortcode') {
            html += inspectorShortcodeSelector('Selecione o Shortcode Pronto', 'tag', block.data.tag || '');
        } else if (block.type === 'nav_menu') {
            html += inspectorMenuSelector('Menu do Sistema', 'menu_id', block.data.menu_id || '');
            var curToggle = block.data.toggle_style || state.mobile_toggle_style || 'classic';
            html += '<div class="inspector-field">' +
                    '<label class="inspector-label">Estilo do Hamburguer Mobile (Toggle)</label>' +
                    '<div class="toggle-cards-grid">' +
                        '<div class="toggle-card ' + (curToggle === 'classic' ? 'active' : '') + '" onclick="updateNavToggleStyle(\'classic\')">' +
                            '<div class="toggle-card-preview">☰</div><div class="toggle-card-name">Clássico</div>' +
                        '</div>' +
                        '<div class="toggle-card ' + (curToggle === 'pill' ? 'active' : '') + '" onclick="updateNavToggleStyle(\'pill\')">' +
                            '<div class="toggle-card-preview" style="font-size:10px; font-weight:800; border:1px solid currentColor; border-radius:10px; padding:1px 6px; width:fit-content; margin:0 auto;">MENU ☰</div><div class="toggle-card-name">Pílula</div>' +
                        '</div>' +
                        '<div class="toggle-card ' + (curToggle === 'minimal' ? 'active' : '') + '" onclick="updateNavToggleStyle(\'minimal\')">' +
                            '<div class="toggle-card-preview">═</div><div class="toggle-card-name">Minimal</div>' +
                        '</div>' +
                        '<div class="toggle-card ' + (curToggle === 'dots' ? 'active' : '') + '" onclick="updateNavToggleStyle(\'dots\')">' +
                            '<div class="toggle-card-preview">⋮</div><div class="toggle-card-name">Pontos</div>' +
                        '</div>' +
                    '</div>' +
                    '</div>';
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'center');
        } else if (block.type === 'divider') {
            html += inspectorSelect('Estilo', 'style', ['solid', 'dashed', 'transparent'], block.data.style || 'solid');
            html += inspectorDimension('Altura / Espaçamento', 'height', block.data.height || '20px');
            html += inspectorTextInput('Cor da Linha', 'color', block.data.color || 'var(--border)');
        } else if (block.type === 'copyright') {
            html += inspectorTextInput('Nome do Site', 'site_name', block.data.site_name || 'Domain-System');
            html += inspectorTextInput('Texto Complementar', 'text', block.data.text || '');
        } else if (block.type === 'custom_code') {
            html += inspectorTextarea('Código HTML / CSS / JS Customizado', 'code', block.data.code || '');
        }

        container.innerHTML = html;
    }

    function updateNavToggleStyle(val) {
        updateBlockProp('toggle_style', val);
        state.mobile_toggle_style = val;
        var partSetting = document.getElementById('setting_mobile_toggle');
        if (partSetting) partSetting.value = val;
        renderCanvas();
    }

    function triggerImageUpload(targetPropKey) {
        currentUploadTargetKey = targetPropKey;
        var fileInput = document.getElementById('global_image_uploader');
        fileInput.value = '';
        fileInput.click();
    }

    function handleFileUpload(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];

        var formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', csrfToken);

        showToast('⏳ Enviando imagem...');

        fetch('<?= $baseUrl ?>/admin/builder/upload-image', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.url) {
                updateBlockProp(currentUploadTargetKey, data.url);
                renderInspector();
                renderCanvas();
                showToast('✓ Imagem carregada com sucesso!');
            } else {
                alert('Erro no upload: ' + (data.message || 'Falha ao salvar imagem.'));
            }
        })
        .catch(function(err) {
            alert('Erro de conexão ao enviar imagem: ' + err);
        });
    }

    function inspectorTextInput(label, key, val) {
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<input type="text" class="inspector-input" value="' + escapeAttr(val) + '" oninput="updateBlockProp(\'' + key + '\', this.value)">' +
               '</div>';
    }

    function inspectorTextarea(label, key, val) {
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<textarea rows="4" class="inspector-textarea" oninput="updateBlockProp(\'' + key + '\', this.value)">' + escapeHtml(val) + '</textarea>' +
               '</div>';
    }

    function inspectorSelect(label, key, options, curVal) {
        var opts = options.map(function(o) {
            return '<option value="' + o + '"' + (o === curVal ? ' selected' : '') + '>' + o + '</option>';
        }).join('');
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value)">' + opts + '</select>' +
               '</div>';
    }

    function inspectorDimension(label, key, val) {
        var num = val.replace(/[^0-9.]/g, '');
        var unit = val.replace(/[0-9.]/g, '') || 'px';
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<div class="dimension-row">' +
               '<input type="text" class="inspector-input" value="' + escapeAttr(val) + '" oninput="updateBlockProp(\'' + key + '\', this.value)">' +
               '<select class="inspector-select" onchange="applyUnitToBlockProp(\'' + key + '\', this.value)">' +
               '<option value="px"' + (unit === 'px' ? ' selected' : '') + '>px</option>' +
               '<option value="%"' + (unit === '%' ? ' selected' : '') + '>%</option>' +
               '<option value="rem"' + (unit === 'rem' ? ' selected' : '') + '>rem</option>' +
               '<option value="em"' + (unit === 'em' ? ' selected' : '') + '>em</option>' +
               '<option value="vw"' + (unit === 'vw' ? ' selected' : '') + '>vw</option>' +
               '<option value="vh"' + (unit === 'vh' ? ' selected' : '') + '>vh</option>' +
               '</select>' +
               '</div>' +
               '</div>';
    }

    function inspectorShortcodeSelector(label, key, curVal) {
        var opts = '<option value="">-- Escolha um Shortcode --</option>';
        shortcodesList.forEach(function(sc) {
            var tag = '[' + sc + ']';
            opts += '<option value="' + tag + '"' + (tag === curVal ? ' selected' : '') + '>' + tag + '</option>';
        });
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value)">' + opts + '</select>' +
               '</div>';
    }

    function inspectorMenuSelector(label, key, curVal) {
        var opts = '<option value="">-- Padrão (Primeiro Menu) --</option>';
        menusList.forEach(function(m) {
            opts += '<option value="' + m.id + '"' + (String(m.id) === String(curVal) ? ' selected' : '') + '>' + m.name + '</option>';
        });
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value)">' + opts + '</select>' +
               '</div>';
    }

    function updateBlockProp(key, val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data[key] = val;
        renderCanvas();
    }

    function applyUnitToBlockProp(key, newUnit) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        var curVal = (block.data && block.data[key]) ? String(block.data[key]) : '16';
        var num = curVal.replace(/[^0-9.]/g, '') || '16';
        updateBlockProp(key, num + newUnit);
        renderInspector();
    }

    function getDefaultBlockData(type) {
        var defaultData = {};
        if (type === 'heading') defaultData = { text: 'Novo Título', level: 'h2', font: 'secondary' };
        if (type === 'paragraph') defaultData = { text: 'Insira seu texto explicativo aqui.', font_size: '1rem' };
        if (type === 'cta_button') defaultData = { label: 'Saiba Mais', url: '#', variant: 'primary' };
        if (type === 'logo') defaultData = { text: 'DomainSystem', height: '36px' };
        if (type === 'divider') defaultData = { style: 'solid', height: '24px' };
        if (type === 'copyright') defaultData = { site_name: 'Domain-System' };
        if (type === 'nav_menu') defaultData = { toggle_style: state.mobile_toggle_style || 'classic', align: 'center' };
        return defaultData;
    }

    function addBlockToCell(secIdx, colIdx, type) {
        var sec = state.sections[secIdx];
        if (!sec) return;
        if (!sec.columns_data[colIdx]) sec.columns_data[colIdx] = [];
        var defaultData = getDefaultBlockData(type);
        sec.columns_data[colIdx].push({ type: type, data: defaultData });
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        selectBlock(secIdx, colIdx, sec.columns_data[colIdx].length - 1);
        renderCanvas();
        renderLayersTree();
        showToast('Bloco adicionado à Célula ' + (colIdx + 1));
    }

    function insertBlockAtCell(secIdx, colIdx, blockIdx, type) {
        var sec = state.sections[secIdx];
        if (!sec) return;
        if (!sec.columns_data[colIdx]) sec.columns_data[colIdx] = [];
        var defaultData = getDefaultBlockData(type);
        sec.columns_data[colIdx].splice(blockIdx, 0, { type: type, data: defaultData });
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        selectBlock(secIdx, colIdx, blockIdx);
        renderCanvas();
        renderLayersTree();
        showToast('Bloco inserido na Célula ' + (colIdx + 1));
    }

    function moveBlockInCell(secIdx, colIdx, blockIdx, dir, e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        var sec = state.sections[secIdx];
        if (!sec || !sec.columns_data[colIdx]) return;
        var list = sec.columns_data[colIdx];
        var targetIdx = blockIdx + dir;
        if (targetIdx >= 0 && targetIdx < list.length) {
            var item = list.splice(blockIdx, 1)[0];
            list.splice(targetIdx, 0, item);
            selectBlock(secIdx, colIdx, targetIdx);
            renderLayersTree();
        }
    }

    function duplicateBlockInCell(secIdx, colIdx, blockIdx, e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        var sec = state.sections[secIdx];
        if (!sec || !sec.columns_data[colIdx]) return;
        var item = JSON.parse(JSON.stringify(sec.columns_data[colIdx][blockIdx]));
        sec.columns_data[colIdx].splice(blockIdx + 1, 0, item);
        renderCanvas();
        renderLayersTree();
        showToast('Bloco duplicado!');
    }

    function deleteBlockInCell(secIdx, colIdx, blockIdx, e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        if (confirm('Deseja excluir este bloco?')) {
            var sec = state.sections[secIdx];
            if (!sec || !sec.columns_data[colIdx]) return;
            sec.columns_data[colIdx].splice(blockIdx, 1);
            selectedBlockCoord = null;
            renderCanvas();
            renderInspector();
            renderLayersTree();
            showToast('Bloco removido.');
        }
    }

    function moveBlockAcrossSecs(fromSec, fromCol, fromIdx, toSec, toCol, toIdx) {
        var sFrom = state.sections[fromSec];
        var sTo = state.sections[toSec];
        if (!sFrom || !sFrom.columns_data[fromCol] || !sFrom.columns_data[fromCol][fromIdx]) return;
        if (!sTo) return;
        if (!sTo.columns_data[toCol]) sTo.columns_data[toCol] = [];

        var item = sFrom.columns_data[fromCol].splice(fromIdx, 1)[0];
        if (toIdx === null || toIdx === undefined || toIdx >= sTo.columns_data[toCol].length) {
            sTo.columns_data[toCol].push(item);
            selectBlock(toSec, toCol, sTo.columns_data[toCol].length - 1);
        } else {
            sTo.columns_data[toCol].splice(toIdx, 0, item);
            selectBlock(toSec, toCol, toIdx);
        }
        activeSecIdx = toSec;
        activeColIdx = toCol;
        renderCanvas();
        renderLayersTree();
        showToast('Bloco reposicionado!');
    }

    function openAddBlockForCell(secIdx, colIdx) {
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        renderBlocksCatalog();
        renderCanvas();
        switchSidebarTab('blocks');
    }

    function savePart() {
        var btn = document.getElementById('btn_save');
        btn.textContent = 'Salvando...';
        btn.disabled = true;

        if (!state.sections || state.sections.length === 0) {
            btn.textContent = '💾 Salvar';
            btn.disabled = false;
            alert('Adicione ao menos uma seção com divisórias antes de salvar.');
            return;
        }

        state.title = document.getElementById('part_title_input').value || state.title;
        var assignedTheme = document.getElementById('setting_assigned_theme').value;

        // Mantém sincronizada a raiz da parte com a primeira seção para retrocompatibilidade
        var firstSec = state.sections[0] || {};
        state.layout_preset = firstSec.layout_preset || '3_cols_header';
        state.container_settings = firstSec.container_settings || {};
        state.columns_data = firstSec.columns_data || {};

        var payload = {
            csrf_token: csrfToken,
            id: state.id,
            type: state.type,
            title: state.title,
            layout_preset: state.layout_preset,
            container_settings: state.container_settings,
            columns_data: state.columns_data,
            sections: state.sections,
            mobile_toggle_style: state.mobile_toggle_style || 'classic',
            is_default: !!state.is_default,
            assigned_theme: assignedTheme
        };

        fetch('<?= $baseUrl ?>/admin/builder/save-part', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btn.textContent = '💾 Salvar';
            btn.disabled = false;
            if (data.success) {
                showToast('✓ ' + data.message);
            } else {
                alert('Erro ao salvar: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(function(err) {
            btn.textContent = '💾 Salvar';
            btn.disabled = false;
            alert('Falha na requisição: ' + err);
        });
    }

    function toggleLivePreviewModal() {
        var modal = document.getElementById('previewModal');
        if (modal.style.display === 'none') {
            modal.style.display = 'block';
            var previewPayload = JSON.parse(JSON.stringify(state));
            previewPayload.csrf_token = csrfToken;
            previewPayload.sections = state.sections;

            fetch('<?= $baseUrl ?>/admin/builder/preview', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(previewPayload)
            })
            .then(function(res) { return res.text(); })
            .then(function(html) {
                var iframe = document.getElementById('preview_iframe');
                iframe.srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{margin:0;padding:20px;background:#06090e;color:#fff;font-family:sans-serif;}</style></head><body>' + html + '</body></html>';
            });
        } else {
            modal.style.display = 'none';
        }
    }

    function showToast(msg) {
        var toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 3000);
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escapeAttr(str) {
        return String(str || '').replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', initStudio);
    </script>
</body>
</html>
