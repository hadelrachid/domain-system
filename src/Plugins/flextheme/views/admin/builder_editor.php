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
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 120px;
            position: relative;
        }
        .canvas-column.drag-over { border-color: var(--primary); background: rgba(56, 189, 248, 0.05); }

        .canvas-column-header {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .canvas-block-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .canvas-block-card:hover { border-color: rgba(56, 189, 248, 0.5); }
        .canvas-block-card.selected { border-color: var(--primary); box-shadow: 0 0 12px rgba(56, 189, 248, 0.25); }

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
                   style="background: transparent; border: 1px solid transparent; color: #fff; font-size: 15px; font-weight: 700; padding: 4px 8px; border-radius: 4px; width: 220px;"
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

        <!-- AÇÕES -->
        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="header-btn" onclick="toggleLivePreviewModal()" title="Ver código / Preview">
                👁️ Preview Real
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
                <div class="sidebar-tab" id="tab_btn_blocks" onclick="switchSidebarTab('blocks')">+ Blocos</div>
                <div class="sidebar-tab" id="tab_btn_inspector" onclick="switchSidebarTab('inspector')">Inspetor</div>
                <div class="sidebar-tab" id="tab_btn_settings" onclick="switchSidebarTab('settings')">Config</div>
            </div>

            <div class="sidebar-content" id="sidebar_tab_layout">
                <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 10px;">Selecione a Grade de Divisórias</div>
                <div id="presets_list"></div>

                <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 12px;">Dimensões do Container</div>
                    
                    <div class="inspector-field">
                        <label class="inspector-label">Largura Máxima (Container)</label>
                        <div class="dimension-row">
                            <input type="text" id="setting_max_width" class="inspector-input" value="1280px" oninput="updateContainerSetting('max_width', this.value)">
                            <select class="inspector-select" onchange="changeUnit('setting_max_width', this.value)">
                                <option value="px">px</option>
                                <option value="%">%</option>
                                <option value="vw">vw</option>
                            </select>
                        </div>
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Espaçamento Entre Colunas (Gap)</label>
                        <div class="dimension-row">
                            <input type="text" id="setting_gap" class="inspector-input" value="24px" oninput="updateContainerSetting('gap', this.value)">
                            <select class="inspector-select" onchange="changeUnit('setting_gap', this.value)">
                                <option value="px">px</option>
                                <option value="rem">rem</option>
                            </select>
                        </div>
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Preenchimento Interno (Padding)</label>
                        <input type="text" id="setting_padding" class="inspector-input" value="20px 24px" oninput="updateContainerSetting('padding', this.value)">
                    </div>

                    <div class="inspector-field">
                        <label class="inspector-label">Alinhamento Vertical</label>
                        <select id="setting_align_items" class="inspector-select" onchange="updateContainerSetting('align_items', this.value)">
                            <option value="center">Centralizado (center)</option>
                            <option value="flex-start">Topo (flex-start)</option>
                            <option value="flex-end">Base (flex-end)</option>
                            <option value="stretch">Esticado (stretch)</option>
                        </select>
                    </div>
                </div>
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
                    <select id="setting_mobile_toggle" class="inspector-select" onchange="state.mobile_toggle_style = this.value; renderCanvas();">
                        <option value="classic" <?= $part->getMobileToggleStyle() === 'classic' ? 'selected' : '' ?>>Clássico ☰ (3 Barras)</option>
                        <option value="pill" <?= $part->getMobileToggleStyle() === 'pill' ? 'selected' : '' ?>>Pílula MENU ☰</option>
                        <option value="minimal" <?= $part->getMobileToggleStyle() === 'minimal' ? 'selected' : '' ?>>Minimalista ✕ (2 Barras Modernas)</option>
                        <option value="dots" <?= $part->getMobileToggleStyle() === 'dots' ? 'selected' : '' ?>>Pontos ⋮ (3 Pontinhos Verticais)</option>
                    </select>
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
                    <span>Simulador Responsivo: <strong id="cur_device_name">Desktop (100%)</strong></span>
                    <span id="cur_device_px">100%</span>
                </div>
                <div id="canvas-content">
                    <div id="canvas_grid" class="canvas-grid-row"></div>
                </div>
            </div>
        </section>

    </main>

    <!-- MODAL DE PREVIEW REAL -->
    <div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(5px); z-index: 9999; padding: 30px;">
        <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; max-width: 1200px; height: 90vh; margin: 0 auto; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 12px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <strong style="color: #fff; font-size: 15px;">Pré-Visualização Real em HTML & CSS Tokens</strong>
                <button type="button" onclick="toggleLivePreviewModal()" style="background: transparent; border: none; color: #fff; font-size: 18px; cursor: pointer;">✕</button>
            </div>
            <iframe id="preview_iframe" style="flex-grow: 1; border: none; background: #06090e; width: 100%;"></iframe>
        </div>
    </div>

    <div id="toast"></div>

    <script>
    // ESTADO DO CONSTRUTOR
    var state = <?= $initialJson ?>;
    var presets = <?= $presetsJson ?>;
    var shortcodesList = <?= $shortcodesJson ?>;
    var menusList = <?= $menusJson ?>;
    var selectedBlockCoord = null; // { col: 0, blockIdx: 0 }

    if (!state.columns_data || Array.isArray(state.columns_data) && state.columns_data.length === 0) {
        state.columns_data = { 0: [] };
    }

    var blocksCatalog = [
        { type: 'heading', name: 'Título', icon: '🔤', desc: 'Títulos H1 até H6 com suporte a fontes e cores.' },
        { type: 'paragraph', name: 'Parágrafo', icon: '📄', desc: 'Texto corrido rico e tipografia escalável.' },
        { type: 'image', name: 'Imagem', icon: '🖼️', desc: 'Imagem com dimensões dinâmicas e link.' },
        { type: 'video', name: 'Vídeo', icon: '🎥', desc: 'YouTube, Vimeo ou MP4 direto responsivo.' },
        { type: 'nav_menu', name: 'Menu de Navegação', icon: '🧭', desc: 'Menu dinâmico com 4 tipos de toggle hamburguer.' },
        { type: 'logo', name: 'Logotipo', icon: '🛡️', desc: 'Logo em imagem ou texto estilizado.' },
        { type: 'cta_button', name: 'Botão CTA', icon: '🚀', desc: 'Botão de ação com variantes primária e outline.' },
        { type: 'shortcode', name: 'Shortcode Pronto', icon: '⚡', desc: 'Insere qualquer shortcode ativo do sistema.' },
        { type: 'divider', name: 'Divisória / Espaçador', icon: '➖', desc: 'Linha sólida, tracejada ou espaçador transparente.' },
        { type: 'social_icons', name: 'Redes Sociais', icon: '🌐', desc: 'Ícones de redes com links dinâmicos.' },
        { type: 'copyright', name: 'Copyright & LGPD', icon: '©', desc: 'Ano automático {year} e links de termos.' },
        { type: 'custom_code', name: 'Código Customizado', icon: '💻', desc: 'Editor HTML, CSS e JavaScript livre.' }
    ];

    function initStudio() {
        renderPresetsList();
        renderBlocksCatalog();
        renderCanvas();
    }

    function switchSidebarTab(tab) {
        ['layout', 'blocks', 'inspector', 'settings'].forEach(function(t) {
            document.getElementById('sidebar_tab_' + t).style.display = (t === tab) ? 'block' : 'none';
            document.getElementById('tab_btn_' + t).classList.toggle('active', t === tab);
        });
    }

    function setDevice(width) {
        var viewport = document.getElementById('device-viewport');
        viewport.style.maxWidth = width;
        document.getElementById('cur_device_px').textContent = width;
        
        ['desktop', 'laptop', 'tablet', 'mobile'].forEach(function(d) {
            document.getElementById('btn_dev_' + d).classList.remove('active');
        });

        if (width === '100%') {
            document.getElementById('btn_dev_desktop').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Desktop (100%)';
        } else if (width === '1024px') {
            document.getElementById('btn_dev_laptop').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Laptop / Tablet Grande (1024px)';
        } else if (width === '768px') {
            document.getElementById('btn_dev_tablet').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Tablet (768px)';
        } else if (width === '375px') {
            document.getElementById('btn_dev_mobile').classList.add('active');
            document.getElementById('cur_device_name').textContent = 'Mobile (375px)';
        }
    }

    function renderPresetsList() {
        var container = document.getElementById('presets_list');
        container.innerHTML = '';
        
        for (var key in presets) {
            var p = presets[key];
            var card = document.createElement('div');
            card.className = 'preset-card' + (state.layout_preset === key ? ' active' : '');
            card.onclick = (function(presetKey) {
                return function() { selectPreset(presetKey); };
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
    }

    function selectPreset(presetKey) {
        state.layout_preset = presetKey;
        var p = presets[presetKey];
        if (!state.columns_data) state.columns_data = {};
        for (var i = 0; i < p.columns; i++) {
            if (!state.columns_data[i]) state.columns_data[i] = [];
        }
        renderPresetsList();
        renderCanvas();
    }

    function renderBlocksCatalog() {
        var container = document.getElementById('blocks_catalog');
        container.innerHTML = '';

        blocksCatalog.forEach(function(b) {
            var item = document.createElement('div');
            item.className = 'block-palette-item';
            item.onclick = function() { addBlockToActiveColumn(b.type); };
            item.innerHTML = '<span class="block-palette-icon">' + b.icon + '</span>' +
                             '<div><strong style="display:block; font-size:13px; color:#fff;">' + b.name + '</strong>' +
                             '<span style="font-size:11px; color:var(--text-muted);">' + b.desc + '</span></div>';
            container.appendChild(item);
        });
    }

    function renderCanvas() {
        var grid = document.getElementById('canvas_grid');
        grid.innerHTML = '';

        var currentPreset = presets[state.layout_preset] || presets['1_col'];
        grid.style.gridTemplateColumns = currentPreset.template || '1fr';

        for (var colIdx = 0; colIdx < currentPreset.columns; colIdx++) {
            var colEl = document.createElement('div');
            colEl.className = 'canvas-column';
            colEl.dataset.col = colIdx;

            var colHeader = document.createElement('div');
            colHeader.className = 'canvas-column-header';
            colHeader.innerHTML = '<span>Coluna ' + (colIdx + 1) + '</span>' +
                                  '<button type="button" class="block-act-btn" onclick="openAddBlockForCol(' + colIdx + ')" title="Adicionar Bloco">+ Bloco</button>';
            colEl.appendChild(colHeader);

            var blocksInCol = (state.columns_data && state.columns_data[colIdx]) ? state.columns_data[colIdx] : [];
            
            blocksInCol.forEach(function(block, blockIdx) {
                var card = document.createElement('div');
                var isSelected = selectedBlockCoord && selectedBlockCoord.col === colIdx && selectedBlockCoord.blockIdx === blockIdx;
                card.className = 'canvas-block-card' + (isSelected ? ' selected' : '');
                card.onclick = (function(cIdx, bIdx) {
                    return function(e) {
                        e.stopPropagation();
                        selectBlock(cIdx, bIdx);
                    };
                })(colIdx, blockIdx);

                var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type, icon: '📦' };

                var bHeader = document.createElement('div');
                bHeader.className = 'canvas-block-header';
                bHeader.innerHTML = '<span>' + bMeta.icon + ' ' + bMeta.name + '</span>' +
                    '<div class="canvas-block-actions">' +
                    '<button type="button" class="block-act-btn" onclick="moveBlock(' + colIdx + ',' + blockIdx + ',-1,event)" title="Subir">▲</button>' +
                    '<button type="button" class="block-act-btn" onclick="moveBlock(' + colIdx + ',' + blockIdx + ',1,event)" title="Descer">▼</button>' +
                    '<button type="button" class="block-act-btn" onclick="duplicateBlock(' + colIdx + ',' + blockIdx + ',event)" title="Duplicar">⎘</button>' +
                    '<button type="button" class="block-act-btn del" onclick="deleteBlock(' + colIdx + ',' + blockIdx + ',event)" title="Excluir">✕</button>' +
                    '</div>';

                var bPreview = document.createElement('div');
                bPreview.style.fontSize = '12px';
                bPreview.style.color = 'var(--text-muted)';
                bPreview.style.overflow = 'hidden';
                bPreview.style.textOverflow = 'ellipsis';
                bPreview.style.whiteSpace = 'nowrap';
                bPreview.textContent = getBlockSummary(block);

                card.appendChild(bHeader);
                card.appendChild(bPreview);
                colEl.appendChild(card);
            });

            grid.appendChild(colEl);
        }
    }

    function getBlockSummary(block) {
        var d = block.data || {};
        if (block.type === 'heading') return d.text || 'Título';
        if (block.type === 'paragraph') return d.text ? d.text.replace(/<[^>]+>/g, '').substr(0, 40) + '...' : 'Parágrafo de texto';
        if (block.type === 'image') return d.url || 'URL da imagem';
        if (block.type === 'video') return d.url || 'Vídeo';
        if (block.type === 'cta_button') return d.label ? d.label + ' (' + (d.url || '#') + ')' : 'Botão';
        if (block.type === 'shortcode') return d.tag || '[shortcode]';
        if (block.type === 'logo') return d.text || 'Logotipo';
        if (block.type === 'nav_menu') return 'Menu: ' + (d.menu_id || 'Principal');
        if (block.type === 'divider') return 'Estilo: ' + (d.style || 'solid');
        return block.type;
    }

    function selectBlock(colIdx, blockIdx) {
        selectedBlockCoord = { col: colIdx, blockIdx: blockIdx };
        renderCanvas();
        renderInspector();
        switchSidebarTab('inspector');
    }

    function renderInspector() {
        var container = document.getElementById('inspector_container');
        if (!selectedBlockCoord) {
            container.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding:40px 10px;">Selecione um bloco para inspecionar.</div>';
            return;
        }

        var block = state.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block) return;
        if (!block.data) block.data = {};

        var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type, icon: '📦' };

        var html = '<div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:10px;">' +
                   '<span style="font-size:20px;">' + bMeta.icon + '</span>' +
                   '<strong style="font-size:15px; color:#fff;">' + bMeta.name + '</strong>' +
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
            html += inspectorTextInput('Texto da Logo (suporta HTML)', 'text', block.data.text || 'DomainSystem');
            html += inspectorTextInput('URL da Imagem (opcional)', 'image_url', block.data.image_url || '');
            html += inspectorDimension('Altura da Logo', 'height', block.data.height || '36px');
            html += inspectorTextInput('Link de Destino', 'url', block.data.url || '/');
        } else if (block.type === 'image') {
            html += inspectorTextInput('URL da Imagem', 'url', block.data.url || '');
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
        var block = state.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data[key] = val;
        renderCanvas();
    }

    function applyUnitToBlockProp(key, newUnit) {
        if (!selectedBlockCoord) return;
        var block = state.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        var curVal = (block.data && block.data[key]) ? String(block.data[key]) : '16';
        var num = curVal.replace(/[^0-9.]/g, '') || '16';
        updateBlockProp(key, num + newUnit);
        renderInspector();
    }

    function changeUnit(inputId, unit) {
        var el = document.getElementById(inputId);
        if (!el) return;
        var num = el.value.replace(/[^0-9.]/g, '') || '10';
        el.value = num + unit;
        el.dispatchEvent(new Event('input'));
    }

    function updateContainerSetting(key, val) {
        if (!state.container_settings) state.container_settings = {};
        state.container_settings[key] = val;
    }

    function addBlockToActiveColumn(type) {
        var targetCol = selectedBlockCoord ? selectedBlockCoord.col : 0;
        if (!state.columns_data[targetCol]) state.columns_data[targetCol] = [];
        
        var defaultData = {};
        if (type === 'heading') defaultData = { text: 'Novo Título', level: 'h2', font: 'secondary' };
        if (type === 'paragraph') defaultData = { text: 'Insira seu texto explicativo aqui.', font_size: '1rem' };
        if (type === 'cta_button') defaultData = { label: 'Saiba Mais', url: '#', variant: 'primary' };
        if (type === 'logo') defaultData = { text: 'DomainSystem', height: '32px' };
        if (type === 'divider') defaultData = { style: 'solid', height: '24px' };
        if (type === 'copyright') defaultData = { site_name: 'Domain-System' };

        state.columns_data[targetCol].push({ type: type, data: defaultData });
        selectBlock(targetCol, state.columns_data[targetCol].length - 1);
        showToast('Bloco adicionado à coluna ' + (targetCol + 1));
    }

    function openAddBlockForCol(colIdx) {
        selectedBlockCoord = { col: colIdx, blockIdx: 0 };
        switchSidebarTab('blocks');
    }

    function moveBlock(colIdx, blockIdx, dir, e) {
        e.stopPropagation();
        var targetIdx = blockIdx + dir;
        var list = state.columns_data[colIdx];
        if (targetIdx >= 0 && targetIdx < list.length) {
            var item = list.splice(blockIdx, 1)[0];
            list.splice(targetIdx, 0, item);
            selectedBlockCoord = { col: colIdx, blockIdx: targetIdx };
            renderCanvas();
            renderInspector();
        }
    }

    function duplicateBlock(colIdx, blockIdx, e) {
        e.stopPropagation();
        var item = JSON.parse(JSON.stringify(state.columns_data[colIdx][blockIdx]));
        state.columns_data[colIdx].splice(blockIdx + 1, 0, item);
        renderCanvas();
        showToast('Bloco duplicado com sucesso!');
    }

    function deleteBlock(colIdx, blockIdx, e) {
        e.stopPropagation();
        if (confirm('Deseja excluir este bloco?')) {
            state.columns_data[colIdx].splice(blockIdx, 1);
            selectedBlockCoord = null;
            renderCanvas();
            renderInspector();
            showToast('Bloco removido.');
        }
    }

    function savePart() {
        var btn = document.getElementById('btn_save');
        btn.textContent = 'Salvando...';
        btn.disabled = true;

        state.title = document.getElementById('part_title_input').value || state.title;
        var assignedTheme = document.getElementById('setting_assigned_theme').value;

        var payload = {
            id: state.id,
            type: state.type,
            title: state.title,
            layout_preset: state.layout_preset,
            container_settings: state.container_settings || {},
            columns_data: state.columns_data || {},
            mobile_toggle_style: state.mobile_toggle_style || 'classic',
            is_default: !!state.is_default,
            assigned_theme: assignedTheme
        };

        fetch('<?= $baseUrl ?>/admin/builder/save-part', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
            fetch('<?= $baseUrl ?>/admin/builder/preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(state)
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
