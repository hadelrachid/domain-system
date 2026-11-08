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
$locale = $locale ?? 'pt_BR';
$i18nJson = json_encode($i18n ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$initialJson = json_encode($partData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$presetsJson = json_encode($presets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$shortcodesJson = json_encode(array_keys($shortcodes), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$menusJson = json_encode($menus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$tokensJson = json_encode($tokens ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title>Flex Builder Studio - <?= htmlspecialchars($part->getTitle()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/builder-studio.css?v=<?= file_exists(dirname(__DIR__, 5) . '/public/assets/css/builder-studio.css') ? filemtime(dirname(__DIR__, 5) . '/public/assets/css/builder-studio.css') : '1.0' ?>">
<style>

/* ==========================================================================
   PALETAS DE CORES CIRCULARES DO INSPECTOR (SWATCHES)
   ========================================================================== */
.token-swatches-grid {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    align-items: center !important;
    margin: 8px 0 !important;
    padding: 8px 10px !important;
    background: rgba(0, 0, 0, 0.28) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 8px !important;
}

.token-swatch-item {
    width: 26px !important;
    height: 26px !important;
    min-width: 26px !important;
    min-height: 26px !important;
    max-width: 26px !important;
    max-height: 26px !important;
    border-radius: 50% !important;
    border: 2px solid rgba(255, 255, 255, 0.22) !important;
    cursor: pointer !important;
    padding: 0 !important;
    margin: 0 !important;
    transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.18s ease, box-shadow 0.18s ease !important;
    position: relative !important;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4) !important;
    outline: none !important;
    box-sizing: border-box !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.token-swatch-item:hover {
    transform: scale(1.22) !important;
    border-color: #ffffff !important;
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.65) !important;
    z-index: 5 !important;
}

.token-swatch-item.active {
    transform: scale(1.25) !important;
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.4), 0 2px 8px rgba(0, 0, 0, 0.6) !important;
    z-index: 6 !important;
}

.token-swatch-item.is-transparent {
    background: repeating-conic-gradient(#334155 0% 25%, #1e293b 0% 50%) 50% / 8px 8px !important;
}

.token-swatch-item.is-transparent::after {
    content: "✕" !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #ef4444 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 100% !important;
    line-height: 1 !important;
}

/* Transições e Hover de Botões no Canvas e Preview */
.ds-block-btn, .ds-block-cta {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.ds-block-btn[style*="--btn-hover-bg"]:hover,
.ds-block-cta[style*="--btn-hover-bg"]:hover {
    background: var(--btn-hover-bg) !important;
    background-color: var(--btn-hover-bg) !important;
}
.ds-block-btn[style*="--btn-hover-color"]:hover,
.ds-block-cta[style*="--btn-hover-color"]:hover {
    color: var(--btn-hover-color) !important;
}

/* Micro-animações de Hover no Canvas e Preview */
.ds-btn-grow { transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease !important; }
.ds-btn-grow:hover { transform: scale(1.06) !important; }

.ds-btn-lift { transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease !important; }
.ds-btn-lift:hover { transform: translateY(-3px) !important; box-shadow: 0 10px 22px rgba(0,0,0,0.35) !important; }

.ds-btn-glow { transition: box-shadow 0.25s ease, transform 0.2s ease !important; }
.ds-btn-glow:hover { box-shadow: 0 0 25px var(--color-primary, #38bdf8) !important; }

.ds-btn-bounce:hover { animation: ds-bounce 0.6s ease infinite alternate !important; }

.ds-btn-shimmer { position: relative; background-size: 200% auto !important; transition: background 0.3s ease !important; }
.ds-btn-shimmer:hover { animation: ds-shimmer 1.5s linear infinite !important; }

@keyframes ds-bounce {
    from { transform: translateY(0); }
    to { transform: translateY(-6px); }
}

@keyframes ds-shimmer {
    0% { filter: brightness(1); }
    50% { filter: brightness(1.3) contrast(1.1); }
    100% { filter: brightness(1); }
}

/* ==========================================================================
   MODAL DE SELEÇÃO RÁPIDA DE BLOCOS (+ BLOCO / DROPZONE)
   ========================================================================== */
.quick-cat-btn {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: var(--text-muted, #94a3b8);
    font-size: 11.5px;
    padding: 5px 12px;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    outline: none;
}
.quick-cat-btn:hover {
    background: rgba(56, 189, 248, 0.15);
    color: #fff;
    border-color: rgba(56, 189, 248, 0.4);
}
.quick-cat-btn.active {
    background: var(--primary, #38bdf8);
    color: #0b0c10;
    font-weight: 700;
    border-color: var(--primary, #38bdf8);
}
.quick-block-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
    text-align: left;
}
.quick-block-card:hover {
    background: rgba(56, 189, 248, 0.12);
    border-color: var(--primary, #38bdf8);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
}
.quick-block-icon {
    font-size: 24px;
    margin-bottom: 2px;
}
.quick-block-name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
}
.quick-block-desc {
    font-size: 11px;
    color: var(--text-muted, #94a3b8);
    line-height: 1.4;
}
.column-dropzone {
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    font-weight: 600 !important;
    user-select: none !important;
    border: 1px dashed rgba(56, 189, 248, 0.45) !important;
    border-radius: 6px !important;
    padding: 9px 12px !important;
    margin-top: 10px !important;
    background: rgba(56, 189, 248, 0.04) !important;
    color: #38bdf8 !important;
    font-size: 11.5px !important;
}
.column-dropzone:hover {
    background: rgba(56, 189, 248, 0.18) !important;
    border-color: #38bdf8 !important;
    color: #ffffff !important;
    box-shadow: 0 0 14px rgba(56, 189, 248, 0.3) !important;
}
.column-dropzone.drag-target-over {
    background: rgba(56, 189, 248, 0.3) !important;
    border-color: #fff !important;
    transform: scale(1.02) !important;
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
            <button type="button" class="header-btn" id="btn_toggle_floating_layers" onclick="toggleFloatingLayers()" title="Abrir Navegador Flutuante de Camadas (Elementor Navigator)" style="border-color: rgba(56, 189, 248, 0.4); color: #fff;">
                🗂️ Camadas (Navigator)
            </button>
            <button type="button" class="header-btn" onclick="toggleLivePreviewModal()" title="Ver código / Preview Modal">
                🔍 Modal
            </button>
            <button type="button" class="header-btn" id="btn_copy_studio_shortcode" onclick="copyStudioShortcode(this)" title="Copiar Shortcode desta Parte" style="border-color: rgba(56, 189, 248, 0.4); color: #38bdf8; font-weight: 600;">
                📋 Copiar Shortcode
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
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div style="font-size: 12px; font-weight: 700; color: #fff;">Espaçamentos e Dimensões da Seção</div>
                        <button type="button" class="header-btn" onclick="resetSectionSpacings()" title="Redefinir espaçamentos desta seção para os padrões" style="font-size: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3); padding: 1px 6px;">
                            ↺ Reset
                        </button>
                    </div>
                    
                    <!-- MARGEM EXTERNA (4 LADOS COM STEPPER) -->
                    <div class="spacing-box-control">
                        <div class="spacing-box-header">
                            <span class="spacing-box-title">Margem Externa (Margin)</span>
                            <button type="button" class="spacing-link-btn" id="margin_link_btn" onclick="toggleSpacingLink('margin')" title="Vincular ou Desvincular os 4 Lados">
                                🔓 Separado
                            </button>
                        </div>
                        <div class="spacing-quad-grid">
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Cima</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 0, -2)">-</button>
                                    <input type="number" class="step-input" id="mar_top" value="0" oninput="onQuadInputChange('margin', 0, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 0, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Direita</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 1, -2)">-</button>
                                    <input type="number" class="step-input" id="mar_right" value="0" oninput="onQuadInputChange('margin', 1, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 1, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Baixo</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 2, -2)">-</button>
                                    <input type="number" class="step-input" id="mar_bottom" value="16" oninput="onQuadInputChange('margin', 2, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 2, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Esquerda</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 3, -2)">-</button>
                                    <input type="number" class="step-input" id="mar_left" value="0" oninput="onQuadInputChange('margin', 3, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('margin', 3, 2)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PREENCHIMENTO INTERNO (4 LADOS COM STEPPER) -->
                    <div class="spacing-box-control">
                        <div class="spacing-box-header">
                            <span class="spacing-box-title">Preenchimento Interno (Padding)</span>
                            <button type="button" class="spacing-link-btn" id="padding_link_btn" onclick="toggleSpacingLink('padding')" title="Vincular ou Desvincular os 4 Lados">
                                🔗 Vinculado
                            </button>
                        </div>
                        <div class="spacing-quad-grid">
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Cima</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 0, -2)">-</button>
                                    <input type="number" class="step-input" id="pad_top" value="16" oninput="onQuadInputChange('padding', 0, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 0, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Direita</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 1, -2)">-</button>
                                    <input type="number" class="step-input" id="pad_right" value="24" oninput="onQuadInputChange('padding', 1, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 1, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Baixo</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 2, -2)">-</button>
                                    <input type="number" class="step-input" id="pad_bottom" value="16" oninput="onQuadInputChange('padding', 2, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 2, 2)">+</button>
                                </div>
                            </div>
                            <div class="spacing-side-col">
                                <span class="spacing-side-label">Esquerda</span>
                                <div class="stepper-wrap">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 3, -2)">-</button>
                                    <input type="number" class="step-input" id="pad_left" value="24" oninput="onQuadInputChange('padding', 3, this.value)">
                                    <button type="button" class="step-btn" onclick="stepQuadValue('padding', 3, 2)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ESPAÇAMENTO ENTRE CÉLULAS (GAP COM STEPPER) -->
                    <div class="inspector-field">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <label class="inspector-label" style="margin-bottom:0;">Espaçamento Entre Células (Gap)</label>
                            <span id="gap_display_val" style="font-size:11px; color:var(--primary); font-weight:700;">24px</span>
                        </div>
                        <div class="stepper-single-wrap">
                            <button type="button" class="step-btn" onclick="stepSingleValue('gap', -2, 0, 100)">-</button>
                            <input type="number" id="setting_gap_num" class="step-input-single" value="24" min="0" max="100" oninput="onSingleNumChange('gap', this.value)">
                            <button type="button" class="step-btn" onclick="stepSingleValue('gap', 2, 0, 100)">+</button>
                        </div>
                    </div>

                    <!-- LARGURA MÁXIMA DO CONTAINER (MAX-WIDTH COM STEPPER E PRESETS) -->
                    <div class="inspector-field">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <label class="inspector-label" style="margin-bottom:0;">Largura Máxima do Container (Boxed)</label>
                            <div style="display:flex; gap:4px;">
                                <button type="button" class="pill-btn" onclick="setMaxWidthPreset('100%')" title="Largura Total">100%</button>
                                <button type="button" class="pill-btn" onclick="setMaxWidthPreset('1280px')" title="Padrão">1280px</button>
                                <button type="button" class="pill-btn" onclick="setMaxWidthPreset('1440px')" title="Wide">1440px</button>
                            </div>
                        </div>
                        <div class="stepper-single-wrap">
                            <button type="button" class="step-btn" onclick="stepSingleValue('max_width', -20, 320, 2560)">-</button>
                            <input type="number" id="setting_max_width_num" class="step-input-single" value="1280" min="320" max="2560" oninput="onSingleNumChange('max_width', this.value)">
                            <button type="button" class="step-btn" onclick="stepSingleValue('max_width', 20, 320, 2560)">+</button>
                        </div>
                    </div>

                    <!-- CONTROLES UNIFICADOS DE ESTILO DA SEÇÃO (DESIGN TOKENS, BORDAS, SOMBRAS) -->
                    <div id="sec_style_controls_container"></div>

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
                    <input type="text" class="inspector-input" value="<?= htmlspecialchars(
                        $part->getType() === 'header' ? 'Cabeçalho (Header)' : (
                            $part->getType() === 'footer' ? 'Rodapé (Footer)' : 'Seção / Layout Central'
                        )
                    ) ?>" disabled style="opacity: 0.8; font-weight: 600;">
                </div>

                <?php if ($part->getType() === 'header'): ?>
                <!-- CONFIGURAÇÃO EXCLUSIVA DE CABEÇALHOS (HEADERS): TOGGLE HAMBURGUER MOBILE -->
                <div class="inspector-field">
                    <label class="inspector-label">Estilo do Toggle Hamburguer Mobile</label>
                    <div id="settings_toggle_cards" class="toggle-cards-grid">
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'classic' ? 'active' : '' ?>" id="toggle_card_classic" onclick="selectGlobalToggleStyle('classic')">
                            <div class="toggle-card-preview">☰</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Clássico</div>
                                <div class="toggle-card-desc">3 Barras</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'pill' ? 'active' : '' ?>" id="toggle_card_pill" onclick="selectGlobalToggleStyle('pill')">
                            <div class="toggle-card-preview" style="font-size:10px; font-weight:800;">MENU ☰</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Pílula</div>
                                <div class="toggle-card-desc">Texto + Ícone</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'minimal' ? 'active' : '' ?>" id="toggle_card_minimal" onclick="selectGlobalToggleStyle('minimal')">
                            <div class="toggle-card-preview">═</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Minimalista</div>
                                <div class="toggle-card-desc">2 Linhas</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'dots' ? 'active' : '' ?>" id="toggle_card_dots" onclick="selectGlobalToggleStyle('dots')">
                            <div class="toggle-card-preview">⋮</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Pontos</div>
                                <div class="toggle-card-desc">3 Pontos</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'boxed' ? 'active' : '' ?>" id="toggle_card_boxed" onclick="selectGlobalToggleStyle('boxed')">
                            <div class="toggle-card-preview" style="background:var(--primary); color:#000; border-radius:4px; padding:1px 5px; font-size:12px;">☰</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Sólido</div>
                                <div class="toggle-card-desc">Preenchido</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'grid' ? 'active' : '' ?>" id="toggle_card_grid" onclick="selectGlobalToggleStyle('grid')">
                            <div class="toggle-card-preview">⠿</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Bento Grid</div>
                                <div class="toggle-card-desc">9 Pontos</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'rounded_glow' ? 'active' : '' ?>" id="toggle_card_rounded_glow" onclick="selectGlobalToggleStyle('rounded_glow')">
                            <div class="toggle-card-preview" style="border:1px solid var(--primary); border-radius:999px; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; box-shadow:0 0 8px rgba(56,189,248,0.4);">☰</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Circular Glow</div>
                                <div class="toggle-card-desc">Redondo Neon</div>
                            </div>
                        </div>
                        <div class="toggle-card <?= $part->getMobileToggleStyle() === 'cyber' ? 'active' : '' ?>" id="toggle_card_cyber" onclick="selectGlobalToggleStyle('cyber')">
                            <div class="toggle-card-preview" style="font-family:monospace; font-size:11px; font-weight:800;">[☰]</div>
                            <div class="toggle-card-info">
                                <div class="toggle-card-name">Tech Cyber</div>
                                <div class="toggle-card-desc">Colchetes</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="setting_mobile_toggle" value="<?= htmlspecialchars($part->getMobileToggleStyle()) ?>">
                </div>

                <div class="inspector-field">
                    <label class="inspector-label">Posição do Botão Hamburguer (Mobile)</label>
                    <select id="setting_mobile_toggle_align" class="inspector-select" onchange="selectGlobalToggleAlign(this.value)">
                        <option value="right" <?= $part->getMobileToggleAlign() === 'right' ? 'selected' : '' ?>>➡️ Alinhar à Direita (Padrão)</option>
                        <option value="center" <?= $part->getMobileToggleAlign() === 'center' ? 'selected' : '' ?>>🎯 Centralizar no Topo</option>
                        <option value="left" <?= $part->getMobileToggleAlign() === 'left' ? 'selected' : '' ?>>⬅️ Alinhar à Esquerda</option>
                    </select>
                    <span style="font-size:10px; color:var(--text-muted); display:block; margin-top:3px;">Define o lado em que o botão hamburguer é posicionado na tela do celular.</span>
                </div>
                <?php endif; ?>

                <?php if ($part->getType() !== 'section'): ?>
                <!-- VINCULAR DIRETAMENTE AO TEMA (APENAS HEADERS E FOOTERS) -->
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
                <?php endif; ?>

                <?php if ($part->getType() === 'section'): ?>
                <!-- ATRIBUIR A TEMPLATE (EXCLUSIVO DE SEÇÕES & LAYOUTS CENTRAIS) -->
                <div class="inspector-field" style="margin-top: 14px; background: rgba(0,0,0,0.25); padding: 12px; border-radius: 6px; border: 1px solid var(--border);">
                    <label class="inspector-label" style="color: var(--primary); font-weight: 700;">🎯 Atribuir Diretamente a um Template do Tema</label>
                    <select id="setting_assigned_template" class="inspector-select">
                        <option value="">Nenhum (Usar somente via Shortcode)</option>
                        <?php if (!empty($templates) && is_array($templates)): ?>
                            <?php foreach ($templates as $tSlug => $tFile): ?>
                                <?php $isSelected = (($assignedTemplate ?? '') === $tSlug || str_replace('.php', '', $assignedTemplate ?? '') === str_replace('.php', '', $tSlug)); ?>
                                <option value="<?= htmlspecialchars($tSlug) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tFile) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <span style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 6px; line-height: 1.4;">
                        Ao escolher um template (ex: <code>home.php</code>, <code>sobre.php</code>, <code>blog.php</code>), esta seção será concatenada automaticamente abaixo do conteúdo daquela página.
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($part->getType() !== 'section'): ?>
                <div class="inspector-field" style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #fff; cursor: pointer;">
                        <input type="checkbox" id="setting_is_default" <?= $part->isDefault() ? 'checked' : '' ?> onchange="state.is_default = this.checked;">
                        Definir como Padrão Global do Sistema
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- CANVAS & SIMULADOR -->
        <section id="simulator-container">
            <div id="device-viewport">
                <div id="device-screen-bar">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Simulador:</span>
                        <div style="display: flex; gap: 2px; background: rgba(0,0,0,0.4); border: 1px solid var(--border); border-radius: 4px; padding: 1px;">
                            <button type="button" class="header-btn active" id="bar_dev_desktop" onclick="setDevice('100%')" title="Desktop (100%)" style="padding: 2px 6px; font-size: 10px;">💻 Desktop</button>
                            <button type="button" class="header-btn" id="bar_dev_laptop" onclick="setDevice('1024px')" title="Laptop (1024px)" style="padding: 2px 6px; font-size: 10px;">💻 1024px</button>
                            <button type="button" class="header-btn" id="bar_dev_tablet" onclick="setDevice('768px')" title="Tablet (768px)" style="padding: 2px 6px; font-size: 10px;">📱 768px</button>
                            <button type="button" class="header-btn" id="bar_dev_mobile" onclick="setDevice('375px')" title="Mobile (375px)" style="padding: 2px 6px; font-size: 10px;">📱 375px</button>
                        </div>
                        <span id="cur_device_px" style="background: rgba(255,255,255,0.06); padding: 1px 6px; border-radius: 4px; font-size: 10px; color: var(--primary); font-weight: 700;">100%</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <!-- SELETOR DE ALTURA VERTICAL / CONTEÚDO CENTRAL -->
                        <div id="live_vertical_height_controls" style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 10px; color: var(--text-muted); font-weight: 600;">↕️ Altura:</span>
                            <div style="display: flex; gap: 2px; background: rgba(0,0,0,0.4); border: 1px solid var(--border); border-radius: 4px; padding: 1px;">
                                <button type="button" class="header-btn" id="btn_vh_850" onclick="setLiveViewportHeight('850px')" title="Altura Padrão (850px)" style="padding: 2px 6px; font-size: 10px;">850px</button>
                                <button type="button" class="header-btn" id="btn_vh_1200" onclick="setLiveViewportHeight('1200px')" title="Altura Estendida (1200px)" style="padding: 2px 6px; font-size: 10px;">1200px</button>
                                <button type="button" class="header-btn" id="btn_vh_full" onclick="setLiveViewportHeight('calc(100vh - 160px)')" title="Janela Completa (100vh)" style="padding: 2px 6px; font-size: 10px;">100vh</button>
                                <button type="button" class="header-btn active" id="btn_vh_auto" onclick="setLiveViewportHeight('auto')" title="Auto: Altura Total do Conteúdo" style="padding: 2px 6px; font-size: 10px;">Auto (Total)</button>
                            </div>
                        </div>
                        <button type="button" class="header-btn" id="active_sections_count_badge" onclick="switchSidebarTab('layout')" title="Clique para gerenciar seções e estruturas" style="font-size: 10px; color: var(--primary); font-weight: 700; padding: 2px 8px; border-radius: 4px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.25); cursor: pointer;">1 Seção</button>
                    </div>
                </div>
                <div id="canvas-content">
                    <div id="sections_stage"></div>
                    <div id="elementor_add_section_wrapper"></div>
                </div>
                <div id="live-stage-container" style="display: none; flex-grow: 1; width: 100%; min-height: 850px; transition: min-height 0.25s ease;">
                    <iframe id="live_stage_iframe" style="width: 100%; height: 850px; border: none; min-height: 850px; background: #06090e; transition: height 0.25s ease;"></iframe>
                </div>
            </div>
        </section>

    </main>

    <div id="floating_layers_panel" class="floating-navigator-panel" style="display: none;">
        <div class="floating-navigator-header" id="floating_layers_drag_handle">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 13px;">🗂️</span>
                <strong style="font-size: 12px; color: #fff;">Navegador de Camadas</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <button type="button" class="navigator-ctrl-btn" onclick="toggleFloatingLayersMinimize()" title="Minimizar / Expandir">_</button>
                <button type="button" class="navigator-ctrl-btn" onclick="toggleFloatingLayers(false)" title="Fechar">✕</button>
            </div>
        </div>
        <div id="floating_layers_content" class="floating-navigator-body">
            <div id="floating_layers_tree_container" class="layer-tree-container"></div>
        </div>
    </div>
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
    // INTERNACIONALIZAÇÃO (i18n)
    var currentLocale = '<?= htmlspecialchars($locale) ?>';
    window.DS_I18N = <?= $i18nJson ?>;
    function __(key, defaultText) {
        if (window.DS_I18N && window.DS_I18N[key]) {
            return window.DS_I18N[key];
        }
        return defaultText || key;
    }
    // ESTADO DO CONSTRUTOR MULTI-SEÇÃO
    var csrfToken = '<?= htmlspecialchars($csrfToken ?? '') ?>';
    var state = <?= $initialJson ?>;
    var presets = <?= $presetsJson ?>;
    var shortcodesList = <?= $shortcodesJson ?>;
    var menusList = <?= $menusJson ?>;
    window.DS_DESIGN_TOKENS = <?= $tokensJson ?>;
    
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
    var currentSidebarTab = 'layout';
    var studioMode = 'structure'; // 'structure' ou 'live'
    var showStructurePicker = false;
    var currentUploadTargetKey = null;

    <?php
    $catalogItems = [];
    $staticBlockDescriptions = [
        'heading'         => 'Títulos H1 até H6 com suporte a fontes e cores.',
        'paragraph'       => 'Texto corrido rico e tipografia escalável.',
        'button'          => 'Botão com múltiplas ações (link, whatsapp, modal, scroll), cores dos tokens e hover.',
        'image'           => 'Imagem com dimensões dinâmicas, upload e link.',
        'video'           => 'YouTube, Vimeo ou MP4 direto responsivo.',
        'nav_menu'        => 'Menu de Navegação com 4 tipos de toggle hamburguer.',
        'user_auth'       => 'Botões de Login e Cadastro (quando deslogado) ou Painel do Aluno e Logout (quando logado).',
        'logo'            => 'Logo com upload de imagem ou texto estilizado.',
        'cta_button'      => 'Botão de ação com variantes primária e outline.',
        'shortcode'       => 'Insere qualquer shortcode ativo do sistema.',
        'divider'         => 'Linha sólida, tracejada ou espaçador transparente.',
        'social_icons'    => 'Redes Sociais com links dinâmicos.',
        'post_cards_grid' => 'Cards de posts responsivos em CSS Grid, SEO Schema.org e filtros.',
        'copyright'       => 'Ano automático {year} e links de termos.',
        'custom_code'     => 'Editor HTML, CSS e JavaScript livre.',
    ];
    if (isset($blocks) && is_array($blocks)) {
        foreach ($blocks as $bType => $bObj) {
            $allowed = method_exists($bObj, 'getAllowedPartTypes') ? $bObj->getAllowedPartTypes() : [];
            $name = method_exists($bObj, 'getName') ? $bObj->getName() : ucfirst($bType);
            $icon = method_exists($bObj, 'getIcon') ? $bObj->getIcon() : '📦';
            $desc = $staticBlockDescriptions[$bType] ?? $name;
            $entry = [
                'type' => $bType,
                'name' => $name,
                'icon' => $icon,
                'desc' => $desc,
            ];
            if (!empty($allowed)) {
                $entry['allowed_types'] = $allowed;
            }
            $catalogItems[] = $entry;
        }
    }
    ?>
    var blocksCatalog = <?= json_encode($catalogItems, JSON_UNESCAPED_UNICODE) ?>;

    function getMaxSectionsForType(type) {
        if (type === 'header') return 3; // Topbar, Principal, Sub-header
        if (type === 'footer') return 3; // Newsletter, Principal, Copyright
        return 6; // Seções centrais livres
    }

    var selectionType = 'none'; // 'none' | 'section' | 'block' | 'column'
    var selectedSecIdx = 0;

    function initStudio() {
        initDraggableNavigator();
        if (state.container_settings) {
            delete state.container_settings.hide_desktop;
            delete state.container_settings.hide_laptop;
            delete state.container_settings.hide_tablet;
            delete state.container_settings.hide_mobile;
        }
        if (Array.isArray(state.sections)) {
            state.sections.forEach(function(sec) {
                if (sec && sec.container_settings) {
                    delete sec.container_settings.hide_desktop;
                    delete sec.container_settings.hide_laptop;
                    delete sec.container_settings.hide_tablet;
                    delete sec.container_settings.hide_mobile;
                }
            });
        }
        state.mobile_toggle_style = state.mobile_toggle_style || '<?= $part->getMobileToggleStyle() ?>';
        state.mobile_toggle_align = state.mobile_toggle_align || '<?= $part->getMobileToggleAlign() ?>';
        var alignSel = document.getElementById('setting_mobile_toggle_align');
        if (alignSel && state.mobile_toggle_align) {
            alignSel.value = state.mobile_toggle_align;
        }
        renderSidebarLayoutTab();
        renderBlocksCatalog();
        renderLayersTree();
        renderCanvas();
    }

    function switchSidebarTab(tab) {
        currentSidebarTab = tab;
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

    // NAVEGADOR FLUTUANTE DE CAMADAS (ELEMENTOR-STYLE FLOATING POPUP)
    function toggleFloatingLayers(forceState) {
        var panel = document.getElementById('floating_layers_panel');
        var btn = document.getElementById('btn_toggle_floating_layers');
        if (!panel) return;
        var willOpen = (typeof forceState === 'boolean') ? forceState : (panel.style.display === 'none');
        panel.style.display = willOpen ? 'flex' : 'none';
        if (btn) {
            btn.classList.toggle('active', willOpen);
            btn.style.borderColor = willOpen ? 'var(--primary)' : 'rgba(56, 189, 248, 0.4)';
        }
        if (willOpen) {
            renderLayersTree();
        }
    }

    function toggleFloatingLayersMinimize() {
        var content = document.getElementById('floating_layers_content');
        if (!content) return;
        content.style.display = (content.style.display === 'none') ? 'block' : 'none';
    }

    function initDraggableNavigator() {
        var panel = document.getElementById('floating_layers_panel');
        var handle = document.getElementById('floating_layers_drag_handle');
        if (!panel || !handle) return;

        var isDragging = false;
        var startX, startY, initialLeft, initialTop;

        handle.onmousedown = function(e) {
            if (e.target.tagName === 'BUTTON') return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            
            var rect = panel.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;

            // Transfere o posicionamento de bottom/right para top/left para arraste suave
            panel.style.bottom = 'auto';
            panel.style.right = 'auto';
            panel.style.left = initialLeft + 'px';
            panel.style.top = initialTop + 'px';
            
            document.body.style.userSelect = 'none';
        };

        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;

            var newLeft = Math.max(10, Math.min(window.innerWidth - panel.offsetWidth - 10, initialLeft + dx));
            var newTop = Math.max(10, Math.min(window.innerHeight - panel.offsetHeight - 10, initialTop + dy));

            panel.style.left = newLeft + 'px';
            panel.style.top = newTop + 'px';
        });

        window.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                document.body.style.userSelect = '';
            }
        });
    }

    var currentLiveHeightPreset = 'auto';

    function setLiveViewportHeight(h) {
        currentLiveHeightPreset = h;
        ['850', '1200', 'full', 'auto'].forEach(function(k) {
            var btn = document.getElementById('btn_vh_' + k);
            if (btn) btn.classList.remove('active');
        });

        var activeKey = (h === '850px') ? '850' : ((h === '1200px') ? '1200' : ((h === 'auto') ? 'auto' : 'full'));
        var activeBtn = document.getElementById('btn_vh_' + activeKey);
        if (activeBtn) activeBtn.classList.add('active');

        adjustLiveStageIframeHeight();
    }

    function adjustLiveStageIframeHeight() {
        var iframe = document.getElementById('live_stage_iframe');
        var container = document.getElementById('live-stage-container');
        var viewport = document.getElementById('device-viewport');
        var canvas = document.getElementById('canvas-content');
        if (!viewport) return;

        var isSection = (state.type === 'section');
        var minBase = isSection ? 850 : 480;

        if (currentLiveHeightPreset === 'auto') {
            viewport.style.minHeight = minBase + 'px';
            viewport.style.height = 'auto';
            if (canvas) {
                canvas.style.minHeight = 'auto';
                canvas.style.height = 'auto';
                canvas.style.overflowY = 'visible';
            }
            if (iframe) {
                try {
                    var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                    if (doc && doc.body) {
                        var scrollH = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
                        var finalH = Math.max(minBase, scrollH + 40);
                        iframe.style.height = finalH + 'px';
                        if (container) {
                            container.style.height = finalH + 'px';
                            container.style.minHeight = finalH + 'px';
                        }
                        viewport.style.minHeight = finalH + 'px';
                        return;
                    }
                } catch(e) {}
                iframe.style.height = minBase + 'px';
            }
            if (container) {
                container.style.height = minBase + 'px';
                container.style.minHeight = minBase + 'px';
            }
        } else {
            var targetH = currentLiveHeightPreset;
            viewport.style.minHeight = targetH;
            viewport.style.height = targetH;
            if (canvas) {
                var offset = (targetH === 'calc(100vh - 160px)') ? 'calc(100vh - 210px)' : (parseInt(targetH) - 50) + 'px';
                canvas.style.minHeight = offset;
                canvas.style.height = offset;
                canvas.style.overflowY = 'auto';
            }
            if (iframe) {
                iframe.style.height = targetH;
            }
            if (container) {
                container.style.height = targetH;
                container.style.minHeight = targetH;
            }
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
            setTimeout(adjustLiveStageIframeHeight, 150);
        } else {
            canvasContent.style.display = 'block';
            liveStage.style.display = 'none';
            renderCanvas();
        }
    }

    function getPreviewGuardScript() {
        return '<script>' +
            'document.addEventListener("click", function(e) {' +
                'var link = e.target.closest("a, area");' +
                'if (link) {' +
                    'e.preventDefault();' +
                    'e.stopPropagation();' +
                    'var href = link.getAttribute("href") || "#";' +
                    'try {' +
                        'if (window.parent && typeof window.parent.showToast === "function") {' +
                            'var msg = (window.parent.__ ? window.parent.__("link_disabled_live", "Links estão desativados no modo Ao Vivo para não sair da edição.") : "Links estão desativados no modo Ao Vivo para não sair da edição.");' +
                            'window.parent.showToast("🔗 " + msg + " (" + href + ")");' +
                        '}' +
                    '} catch(err) {}' +
                    'return false;' +
                '}' +
            '}, true);' +
            'document.addEventListener("submit", function(e) {' +
                'e.preventDefault();' +
                'e.stopPropagation();' +
                'try {' +
                    'if (window.parent && typeof window.parent.showToast === "function") {' +
                        'window.parent.showToast("⚠️ Formulários desativados no modo Ao Vivo.");' +
                    '}' +
                '} catch(err) {}' +
                'return false;' +
            '}, true);' +
        '</' + 'script>';
    }

    var liveStageDebounceTimer = null;
    function loadLiveStageContent() {
        if (liveStageDebounceTimer) {
            clearTimeout(liveStageDebounceTimer);
        }
        liveStageDebounceTimer = setTimeout(function() {
            var iframe = document.getElementById('live_stage_iframe');
            if (!iframe) return;
            var payload = JSON.parse(JSON.stringify(state));
            payload.csrf_token = csrfToken;

            if (!payload.mobile_toggle_style && state.mobile_toggle_style) {
                payload.mobile_toggle_style = state.mobile_toggle_style;
            }
            if (!payload.mobile_toggle_align && state.mobile_toggle_align) {
                payload.mobile_toggle_align = state.mobile_toggle_align;
            }

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
                var bodyClass = 'ds-live-preview';
                if (currentDevice === '375px') {
                    bodyClass += ' ds-mobile-mode ds-device-375';
                } else if (currentDevice === '768px') {
                    bodyClass += ' ds-tablet-mode ds-device-768';
                } else if (currentDevice === '1024px') {
                    bodyClass += ' ds-laptop-mode ds-device-1024';
                } else {
                    bodyClass += ' ds-desktop-mode ds-device-desktop ds-device-1440';
                }

                iframe.onload = function() {
                    adjustLiveStageIframeHeight();
                };

                var liveStyles = '<style>' +
                    'body { margin:0; padding:0; background:#06090e; color:#fff; font-family:var(--font-primary, sans-serif); min-height:100vh; box-sizing:border-box; }' +
                    'a { cursor:pointer !important; }' +
                    (state.type === 'section' ? '.ds-part-wrapper, .ds-theme-section { min-height: 80vh; }' : '') +
                    '</style>';

                iframe.srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">' + liveStyles + '</head><body class="' + bodyClass + '">' + html + getPreviewGuardScript() + '</body></html>';

                setTimeout(adjustLiveStageIframeHeight, 150);
            })
            .catch(function(err) {
                console.error('[LiveStage] Erro ao renderizar preview ao vivo:', err);
            });
        }, 50);
    }

    function setDevice(width) {
        currentDevice = width;
        var viewport = document.getElementById('device-viewport');
        viewport.style.maxWidth = width;
        var pxEl = document.getElementById('cur_device_px');
        if (pxEl) pxEl.textContent = width;
        
        ['desktop', 'laptop', 'tablet', 'mobile'].forEach(function(d) {
            var b1 = document.getElementById('btn_dev_' + d);
            if (b1) b1.classList.remove('active');
            var b2 = document.getElementById('bar_dev_' + d);
            if (b2) b2.classList.remove('active');
        });

        viewport.classList.remove('ds-desktop-mode', 'ds-laptop-mode', 'ds-mobile-mode', 'ds-tablet-mode', 'ds-device-desktop', 'ds-device-1024', 'ds-device-375', 'ds-device-768');

        var activeKey = 'desktop';
        var devName = 'Desktop (100%)';
        if (width === '100%') {
            activeKey = 'desktop';
            devName = 'Desktop (100%)';
            viewport.classList.add('ds-desktop-mode', 'ds-device-desktop');
        } else if (width === '1024px') {
            activeKey = 'laptop';
            devName = 'Laptop (1024px)';
            viewport.classList.add('ds-laptop-mode', 'ds-device-1024');
        } else if (width === '768px') {
            activeKey = 'tablet';
            devName = 'Tablet (768px) - 📱 Hamburguer Ativo';
            viewport.classList.add('ds-tablet-mode', 'ds-device-768');
        } else if (width === '375px') {
            activeKey = 'mobile';
            devName = 'Mobile (375px) - 📱 Hamburguer Ativo';
            viewport.classList.add('ds-mobile-mode', 'ds-device-375');
        }

        var b1 = document.getElementById('btn_dev_' + activeKey);
        if (b1) b1.classList.add('active');
        var b2 = document.getElementById('bar_dev_' + activeKey);
        if (b2) b2.classList.add('active');

        var nameEl = document.getElementById('cur_device_name');
        if (nameEl) nameEl.textContent = devName;

        var curSec = getSelectedSection();
        if (curSec) {
            renderSectionStyleControls(curSec);
        }
        if (selectedBlockCoord) {
            renderInspector();
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

    var lastDeletedSectionBackup = null;

    function deleteSection(secIdx, e) {
        if (e) {
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
            if (typeof e.preventDefault === 'function') e.preventDefault();
        }

        var sec = state.sections[secIdx];
        if (!sec) return;

        // Salva backup para permitir desfazer imediatamente
        lastDeletedSectionBackup = {
            sec: JSON.parse(JSON.stringify(sec)),
            index: secIdx
        };

        // Exclui a seção imediatamente sem bloqueio de janela confirm()
        state.sections.splice(secIdx, 1);

        if (state.sections.length === 0) {
            activeSecIdx = 0;
            activeColIdx = 0;
            selectedBlockCoord = null;
            selectionType = 'none';
            showStructurePicker = true;
            showToast('✓ Seção excluída! <button type="button" onclick="undoDeleteSection()" style="margin-left:8px; background:#000; color:#fff; border:none; padding:2px 8px; border-radius:4px; cursor:pointer; font-weight:700;">Desfazer</button>');
        } else {
            if (activeSecIdx >= state.sections.length) {
                activeSecIdx = Math.max(0, state.sections.length - 1);
            }
            activeColIdx = 0;
            selectedBlockCoord = null;
            selectionType = 'none';
            showToast('✓ Seção excluída com sucesso! <button type="button" onclick="undoDeleteSection()" style="margin-left:8px; background:#000; color:#fff; border:none; padding:2px 8px; border-radius:4px; cursor:pointer; font-weight:700;">Desfazer</button>');
        }

        renderCanvas();
        renderSidebarLayoutTab();
        renderLayersTree();
    }

    function undoDeleteSection() {
        if (!lastDeletedSectionBackup) return;
        state.sections.splice(lastDeletedSectionBackup.index, 0, lastDeletedSectionBackup.sec);
        activeSecIdx = lastDeletedSectionBackup.index;
        lastDeletedSectionBackup = null;
        renderCanvas();
        renderSidebarLayoutTab();
        renderLayersTree();
        showToast('✓ Seção restaurada com sucesso!');
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

        var floatingPanel = document.getElementById('floating_layers_panel');
        var isFloatingOpen = floatingPanel && floatingPanel.style.display !== 'none';
        if (isFloatingOpen || currentSidebarTab !== 'layers') {
            switchSidebarTab('layout');
        }
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

        // Espaçamentos e dimensões da seção sincronizados com a UI de Steppers
        var cs = curSec.container_settings || {};
        var marginVals = parse4Values(cs.margin || '0px 0px 16px 0px');
        var paddingVals = parse4Values(cs.padding || '16px 24px');

        ['mar_top', 'mar_right', 'mar_bottom', 'mar_left'].forEach(function(id, idx) {
            var el = document.getElementById(id);
            if (el) el.value = marginVals[idx];
        });

        ['pad_top', 'pad_right', 'pad_bottom', 'pad_left'].forEach(function(id, idx) {
            var el = document.getElementById(id);
            if (el) el.value = paddingVals[idx];
        });

        var gapNum = parseFloat(cs.gap || '24px') || 24;
        var gapEl = document.getElementById('setting_gap_num');
        if (gapEl) gapEl.value = gapNum;
        var gapDisp = document.getElementById('gap_display_val');
        if (gapDisp) gapDisp.textContent = gapNum + 'px';

        var maxWidthStr = cs.max_width || '1280px';
        var maxWidthNum = parseFloat(maxWidthStr) || 1280;
        var maxWidthEl = document.getElementById('setting_max_width_num');
        if (maxWidthEl) maxWidthEl.value = maxWidthNum;

        renderSectionStyleControls(curSec);
        var alignEl = document.getElementById('setting_align_items');
        if (alignEl) alignEl.value = cs.align_items || 'center';
        var justifyEl = document.getElementById('setting_justify_content');
        if (justifyEl) justifyEl.value = cs.justify_content || 'space-between';

        updateSpacingLinkUI('margin');
        updateSpacingLinkUI('padding');
    }

    
    // ==========================================
    // ESTILOS AVANÇADOS DE SEÇÕES (FASE 5 CONTRATOS UNIFICADOS)
    // ==========================================
    function renderSectionStyleControls(curSec) {
        var container = document.getElementById('sec_style_controls_container');
        if (!container) return;
        var cs = curSec.container_settings || {};

        var html = '';

        // 1. Cor de Fundo da Seção com Design Tokens
        html += renderAdvancedBackgroundControl('Cor de Fundo da Seção (Background)', 'background', cs.background || 'transparent', 'transparent', true);

        // 1.5 Cores de Texto e Tipografia da Seção / Container (Cascading via Contrato)
        html += '<div style="margin-top:12px; background:rgba(15,23,42,0.45); padding:10px; border-radius:8px; border:1px solid rgba(56,189,248,0.2);">' +
                '<div style="font-size:11px; font-weight:700; color:var(--primary); margin-bottom:8px;">🔤 Cores de Texto & Tipografia do Container (Seção)</div>' +
                renderSectionTokenColorPicker('Cor do Texto Geral (Text Color)', 'text_color', cs.text_color || cs.color || '', 'text_main') +
                renderSectionTokenColorPicker('Cor dos Títulos Internos (Heading Color)', 'heading_color', cs.heading_color || '', 'primary') +
                renderSectionTokenColorPicker('Cor dos Links Internos (Link Color)', 'link_color', cs.link_color || '', 'primary') +
                renderSectionTokenColorPicker('Cor dos Links no Hover (Link Hover)', 'link_hover_color', cs.link_hover_color || '', 'tertiary') +
                renderSectionFontSelector('Família de Fonte Padrão da Seção', 'font_family', cs.font_family || '') +
                '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                '<div class="inspector-field" style="margin-bottom:0;"><label class="inspector-label">Alinhamento Texto</label>' +
                '<select class="inspector-select" onchange="updateActiveSecSetting(\'text_align\', this.value); renderCanvas(); renderSidebarLayoutTab();">' +
                '<option value=""' + (!cs.text_align ? ' selected' : '') + '>Herdar / Padrão</option>' +
                '<option value="left"' + (cs.text_align === 'left' ? ' selected' : '') + '>Esquerda (left)</option>' +
                '<option value="center"' + (cs.text_align === 'center' ? ' selected' : '') + '>Centro (center)</option>' +
                '<option value="right"' + (cs.text_align === 'right' ? ' selected' : '') + '>Direita (right)</option>' +
                '<option value="justify"' + (cs.text_align === 'justify' ? ' selected' : '') + '>Justificado</option>' +
                '</select></div>' +
                '<div class="inspector-field" style="margin-bottom:0;"><label class="inspector-label">Transformação</label>' +
                '<select class="inspector-select" onchange="updateActiveSecSetting(\'text_transform\', this.value); renderCanvas(); renderSidebarLayoutTab();">' +
                '<option value=""' + (!cs.text_transform ? ' selected' : '') + '>Nenhuma</option>' +
                '<option value="uppercase"' + (cs.text_transform === 'uppercase' ? ' selected' : '') + '>MAIÚSCULAS</option>' +
                '<option value="lowercase"' + (cs.text_transform === 'lowercase' ? ' selected' : '') + '>minúsculas</option>' +
                '<option value="capitalize"' + (cs.text_transform === 'capitalize' ? ' selected' : '') + '>Capitalizar</option>' +
                '</select></div>' +
                '</div>' +
                '</div>';

        // 2. Arredondamento de Borda da Seção (Border Radius)
        var curRadiusNum = parseFloat(cs.border_radius || '0') || 0;
        html += '<div class="inspector-field" style="margin-top:12px;">' +
                '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                '<label class="inspector-label" style="margin-bottom:0;">Arredondamento da Seção (Border Radius)</label>' +
                '<span style="font-size:11px; color:var(--primary); font-weight:700;">' + (cs.border_radius || '0px') + '</span>' +
                '</div>' +
                '<div class="stepper-single-wrap">' +
                '<button type="button" class="step-btn" onclick="stepSecSingleValue(\'border_radius\', -2, 0, 100)">-</button>' +
                '<input type="number" id="sec_border_radius_num" class="step-input-single" value="' + curRadiusNum + '" min="0" max="100" oninput="onSecSingleNumChange(\'border_radius\', this.value)">' +
                '<button type="button" class="step-btn" onclick="stepSecSingleValue(\'border_radius\', 2, 0, 100)">+</button>' +
                '</div>' +
                '</div>';

        // 3. Borda da Seção (Border)
        html += '<div class="inspector-field">' +
                '<label class="inspector-label">Borda da Seção (Border)</label>' +
                '<input type="text" class="inspector-input" value="' + escapeAttr(cs.border || '') + '" oninput="updateActiveSecSetting(\'border\', this.value)" placeholder="ex: 1px solid var(--color-border)">' +
                '</div>';

        // 4. Sombra da Seção (Box Shadow) com Presets
        var shadowOpts = [
            { value: '', label: 'Nenhuma Sombra' },
            { value: '0 2px 4px rgba(0,0,0,0.15)', label: 'Sutil (0 2px 4px)' },
            { value: '0 4px 12px rgba(0,0,0,0.25)', label: 'Média (0 4px 12px)' },
            { value: '0 10px 25px rgba(0,0,0,0.4)', label: 'Elevada (0 10px 25px)' },
            { value: '0 0 20px var(--color-primary, rgba(56,189,248,0.4))', label: 'Glow Neon Primário' }
        ];
        html += '<div class="inspector-field">' +
                '<label class="inspector-label">Sombra da Seção (Box Shadow)</label>' +
                '<select class="inspector-select" onchange="updateActiveSecSetting(\'box_shadow\', this.value)">' +
                shadowOpts.map(function(opt) {
                    return '<option value="' + escapeAttr(opt.value) + '"' + (cs.box_shadow === opt.value ? ' selected' : '') + '>' + escapeHtml(opt.label) + '</option>';
                }).join('') +
                '</select>' +
                '</div>';

        // 5. Opacidade da Seção
        html += '<div class="inspector-field">' +
                '<label class="inspector-label">Opacidade da Seção (0.1 a 1.0)</label>' +
                '<input type="number" step="0.1" min="0.1" max="1.0" class="inspector-input" value="' + (cs.opacity !== undefined ? cs.opacity : '') + '" oninput="updateActiveSecSetting(\'opacity\', this.value)" placeholder="1.0">' +
                '</div>';

        // 6. Visibilidade Responsiva por Elemento da Seção (4 Resoluções: Desktop, 1024px, 768px, 375px)
        var secIdxForVis = state.sections.indexOf(curSec);
        if (secIdxForVis === -1) secIdxForVis = activeSecIdx || 0;
        var elemRowsHtml = '';
        var colsData = curSec.columns_data || {};
        var presetInfoVis = presets[curSec.layout_preset] || presets['1_col'];
        var numColsVis = presetInfoVis ? presetInfoVis.columns : 3;

        for (var cIdx = 0; cIdx < numColsVis; cIdx++) {
            var bList = colsData[cIdx] || [];
            bList.forEach(function(blk, bIdx) {
                if (!blk) return;
                var bMeta = blocksCatalog.find(function(x) { return x.type === blk.type; }) || { name: blk.type, icon: '📦' };
                var bName = blk.custom_name || bMeta.name;
                var d = blk.data || {};
                var isSelBlk = (selectedBlockCoord && selectedBlockCoord.sec === secIdxForVis && selectedBlockCoord.col === cIdx && selectedBlockCoord.blockIdx === bIdx);
                var rowBorder = isSelBlk ? 'border:1px solid var(--primary); background:rgba(56,189,248,0.1);' : 'border:1px solid rgba(255,255,255,0.08); background:rgba(0,0,0,0.25);';

                var resButtons = [
                    { key: 'hide_desktop', label: '🖥️ Desktop', active: !!d.hide_desktop },
                    { key: 'hide_laptop',  label: '💻 1024px',  active: !!(d.hide_laptop || d.hide_1024) },
                    { key: 'hide_tablet',  label: '📱 768px',   active: !!(d.hide_tablet || d.hide_768) },
                    { key: 'hide_mobile',  label: '📲 375px',   active: !!(d.hide_mobile || d.hide_375) }
                ].map(function(r) {
                    var btnStyle = r.active
                        ? 'background:rgba(239,68,68,0.22); border:1px solid #ef4444; color:#fca5a5; font-weight:700; text-decoration:line-through;'
                        : 'background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.35); color:#6ee7b7;';
                    var titleTip = r.active
                        ? 'Oculto em ' + r.label + ' (Clique para exibir)'
                        : 'Visível em ' + r.label + ' (Clique para ocultar apenas este elemento)';
                    return '<button type="button" onclick="toggleElementResolutionVisibility(' + secIdxForVis + ',' + cIdx + ',' + bIdx + ',\'' + r.key + '\',\'' + escapeAttr(r.label) + '\')" title="' + escapeAttr(titleTip) + '" style="font-size:10px; padding:3px 6px; border-radius:4px; cursor:pointer; ' + btnStyle + '">' +
                           (r.active ? '🚫 ' : '👁️ ') + r.label.replace(/^[^\s]+\s/, '') +
                           '</button>';
                }).join('');

                elemRowsHtml += '<div style="padding:7px 8px; border-radius:6px; margin-bottom:6px; ' + rowBorder + '">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">' +
                    '<span style="font-size:11px; font-weight:700; color:#fff; cursor:pointer;" onclick="selectBlock(' + secIdxForVis + ',' + cIdx + ',' + bIdx + ')">' + bMeta.icon + ' ' + escapeHtml(bName) + ' <span style="font-size:9px; color:var(--text-muted); font-weight:400;">(Célula ' + (cIdx + 1) + ')</span></span>' +
                    (isSelBlk ? '<span style="font-size:9px; color:var(--primary); font-weight:800;">SELECIONADO</span>' : '') +
                    '</div>' +
                    '<div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:4px;">' + resButtons + '</div>' +
                    '</div>';
            });
        }

        if (!elemRowsHtml) {
            elemRowsHtml = '<div style="font-size:11px; color:var(--text-muted); text-align:center; padding:8px;">Nenhum elemento nesta seção ainda. Adicione blocos para configurar sua visibilidade por resolução.</div>';
        }

        html += '<div class="inspector-field" style="margin-top:12px; background:rgba(56,189,248,0.05); border:1px solid rgba(56,189,248,0.2); border-radius:6px; padding:10px;">' +
                '<label class="inspector-label" style="color:var(--primary); font-weight:700; margin-bottom:4px;">📱 Visibilidade Responsiva por Elemento (4 Resoluções)</label>' +
                '<div style="font-size:10px; color:var(--text-muted); margin-bottom:8px; line-height:1.35;">Clique na resolução para <strong>ocultar apenas o elemento desejado</strong> (ex: Logo, Título ou Botão), mantendo os demais elementos da seção visíveis:</div>' +
                elemRowsHtml +
                '</div>';

        container.innerHTML = html;
    }

    function toggleElementResolutionVisibility(secIdx, colIdx, blockIdx, resKey, resLabel) {
        var sec = state.sections[secIdx];
        if (!sec || !sec.columns_data || !sec.columns_data[colIdx] || !sec.columns_data[colIdx][blockIdx]) return;
        var blk = sec.columns_data[colIdx][blockIdx];
        if (!blk.data) blk.data = {};
        blk.data[resKey] = !blk.data[resKey];
        var bMeta = blocksCatalog.find(function(x) { return x.type === blk.type; }) || { name: blk.type };
        var bName = blk.custom_name || bMeta.name;
        renderCanvas();
        renderSectionStyleControls(sec);
        if (selectedBlockCoord && selectedBlockCoord.sec === secIdx && selectedBlockCoord.col === colIdx && selectedBlockCoord.blockIdx === blockIdx) {
            renderInspector();
        }
        showToast(blk.data[resKey]
            ? ('🚫 "' + bName + '" ocultado em ' + resLabel + ' (apenas este elemento)')
            : ('👁️ "' + bName + '" visível novamente em ' + resLabel));
    }

    var DS_POPULAR_COLORS = [
        { name: 'Branco Puro', hex: '#ffffff' },
        { name: 'Preto Profundo', hex: '#000000' },
        { name: 'Slate / Chumbo', hex: '#0f172a' },
        { name: 'Cinza Neutro', hex: '#64748b' },
        { name: 'Verde Esmeralda (Sucesso)', hex: '#10b981' },
        { name: 'Verde Floresta', hex: '#059669' },
        { name: 'Ciano Neon', hex: '#06b6d4' },
        { name: 'Azul Céu', hex: '#38bdf8' },
        { name: 'Azul Elétrico', hex: '#3b82f6' },
        { name: 'Azul Escuro Real', hex: '#1d4ed8' },
        { name: 'Índigo Moderno', hex: '#6366f1' },
        { name: 'Púrpura / Roxo', hex: '#8b5cf6' },
        { name: 'Rosa Vibrante', hex: '#ec4899' },
        { name: 'Cereja / Coral', hex: '#f43f5e' },
        { name: 'Vermelho Intenso', hex: '#ef4444' },
        { name: 'Laranja Elétrico', hex: '#f97316' },
        { name: 'Dourado / Âmbar', hex: '#f59e0b' },
        { name: 'Amarelo Vivo', hex: '#eab308' }
    ];

    /* ==========================================================================
       FÁBRICA UNIVERSAL DE PALETAS E SELETOR DE CORES (DESIGN TOKENS)
       ========================================================================== */
    function buildUniversalTokenColorPicker(label, key, curVal, defaultToken, targetType) {
        var dt = window.DS_DESIGN_TOKENS || {};
        var palette = dt.palette || {
            primary: '#38bdf8',
            secondary: '#1f2833',
            tertiary: '#60a5fa',
            background: '#0b0c10',
            surface: 'rgba(31, 40, 51, 0.7)',
            text_main: '#c5c6c7',
            text_muted: '#8b8c8d',
            border: 'rgba(255, 255, 255, 0.1)'
        };

        var tokenList = [
            { id: 'primary', label: 'Cor Primária', var: 'var(--color-primary)', hex: palette.primary || '#38bdf8' },
            { id: 'secondary', label: 'Cor Secundária', var: 'var(--color-secondary)', hex: palette.secondary || '#1f2833' },
            { id: 'tertiary', label: 'Cor Terciária / Destaque', var: 'var(--color-tertiary)', hex: palette.tertiary || '#60a5fa' },
            { id: 'text_main', label: 'Texto Principal', var: 'var(--color-text-main)', hex: palette.text_main || '#c5c6c7' },
            { id: 'text_muted', label: 'Texto Suave (Muted)', var: 'var(--color-text-muted)', hex: palette.text_muted || '#8b8c8d' },
            { id: 'background', label: 'Fundo Global', var: 'var(--color-background)', hex: palette.background || '#0b0c10' },
            { id: 'surface', label: 'Superfície', var: 'var(--color-surface)', hex: palette.surface || '#1f2833' },
            { id: 'border', label: 'Borda', var: 'var(--color-border)', hex: palette.border || '#334155' },
            { id: 'transparent', label: 'Herdar / Padrão', var: '', hex: 'transparent' }
        ];

        var isSec = (targetType === 'section');

        var tokenSwatchesHtml = '';
        tokenList.forEach(function(t) {
            var isAct = (curVal === t.var || (t.hex !== 'transparent' && curVal === t.hex) || (!curVal && (defaultToken === t.id || t.id === 'transparent')));
            var actClass = isAct ? ' active' : '';
            var transClass = (t.id === 'transparent') ? ' is-transparent' : '';
            var bgStyle = (t.id === 'transparent') ? '' : 'background-color:' + t.hex + ';';
            var escapedVar = escapeAttr(t.var);
            var clickCall = isSec
                ? 'updateActiveSecSetting(\'' + key + '\', \'' + escapedVar + '\'); renderCanvas(); renderSidebarLayoutTab();'
                : 'updateBlockProp(\'' + key + '\', \'' + escapedVar + '\'); renderCanvas(); renderInspector();';
            
            tokenSwatchesHtml += '<button type="button" class="token-swatch-item' + actClass + transClass + '" ' +
                            'style="' + bgStyle + '" ' +
                            'title="' + escapeAttr(t.label + ' (' + (t.id === 'transparent' ? 'Padrão' : t.var) + ')') + '" ' +
                            'onclick="' + clickCall + '">' +
                            '</button>';
        });

        var popularSwatchesHtml = '';
        DS_POPULAR_COLORS.forEach(function(c) {
            var isAct = (curVal && curVal.toLowerCase() === c.hex.toLowerCase());
            var actClass = isAct ? ' active' : '';
            var clickCall = isSec
                ? 'updateActiveSecSetting(\'' + key + '\', \'' + c.hex + '\'); renderCanvas(); renderSidebarLayoutTab();'
                : 'updateBlockProp(\'' + key + '\', \'' + c.hex + '\'); renderCanvas(); renderInspector();';

            popularSwatchesHtml += '<button type="button" class="token-swatch-item' + actClass + '" ' +
                            'style="background-color:' + c.hex + ';" ' +
                            'title="' + escapeAttr(c.name + ' (' + c.hex + ')') + '" ' +
                            'onclick="' + clickCall + '">' +
                            '</button>';
        });

        var hexVal = curVal || '';
        if (hexVal.indexOf('var(') === 0) {
            var matched = tokenList.find(function(t) { return t.var === hexVal; });
            if (matched && matched.hex && matched.hex !== 'transparent' && matched.hex.indexOf('#') === 0) {
                hexVal = matched.hex;
            } else {
                hexVal = '#38bdf8';
            }
        }
        if (!hexVal.startsWith('#') || hexVal.length !== 7) {
            hexVal = '#38bdf8';
        }

        var valueBadge = '';
        if (!curVal) {
            valueBadge = '<span style="font-size:10px; color:#94a3b8; background:rgba(255,255,255,0.06); padding:2px 7px; border-radius:4px; font-weight:600;">⚪ Herdado / Padrão</span>';
        } else if (curVal.indexOf('var(') === 0) {
            var tMatch = tokenList.find(function(t) { return t.var === curVal; });
            var tName = tMatch ? tMatch.label : curVal;
            valueBadge = '<span style="font-size:10px; color:#38bdf8; background:rgba(56,189,248,0.14); border:1px solid rgba(56,189,248,0.3); padding:2px 7px; border-radius:4px; font-weight:700;">🌐 ' + escapeHtml(tName) + '</span>';
        } else {
            valueBadge = '<span style="display:inline-flex; align-items:center; gap:5px; font-size:10px; color:#fff; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); padding:2px 7px; border-radius:4px; font-family:monospace;"><span style="width:9px; height:9px; border-radius:50%; background:' + escapeAttr(curVal) + '; border:1px solid #fff; display:inline-block;"></span> ' + escapeHtml(curVal) + '</span>';
        }

        var liveInputCall = isSec
            ? 'updateActiveSecSetting(\'' + key + '\', this.value); renderCanvas();'
            : 'updateBlockProp(\'' + key + '\', this.value); renderCanvas();';
        var changeCall = isSec
            ? 'updateActiveSecSetting(\'' + key + '\', this.value); renderCanvas(); renderSidebarLayoutTab();'
            : 'updateBlockProp(\'' + key + '\', this.value); renderCanvas(); renderInspector();';
        var clearCall = isSec
            ? 'updateActiveSecSetting(\'' + key + '\', \'\'); renderCanvas(); renderSidebarLayoutTab();'
            : 'updateBlockProp(\'' + key + '\', \'\'); renderCanvas(); renderInspector();';

        return '<div class="inspector-field" style="margin-bottom:14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:10px;">' +
               '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">' +
               '<label class="inspector-label" style="margin-bottom:0; font-weight:700;">' + label + '</label>' +
               valueBadge +
               '</div>' +

               '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:3px; text-transform:uppercase; letter-spacing:0.4px;">🌐 Tokens Globais do Tema:</div>' +
               '<div class="token-swatches-grid" style="margin-top:2px; margin-bottom:8px;">' + tokenSwatchesHtml + '</div>' +

               '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:3px; text-transform:uppercase; letter-spacing:0.4px;">✨ Cores Populares &amp; Contrastes:</div>' +
               '<div class="token-swatches-grid" style="margin-top:2px; margin-bottom:8px;">' + popularSwatchesHtml + '</div>' +

               '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.4px;">🎨 Seletor Livre ou Código Hex / CSS:</div>' +
               '<div style="display:flex; gap:8px; align-items:center;">' +
               '<div style="position:relative; width:28px; height:28px; min-width:28px; border-radius:50%; border:2px solid rgba(255,255,255,0.3); overflow:hidden; cursor:pointer; background:' + escapeAttr(hexVal) + '; box-shadow:0 1px 4px rgba(0,0,0,0.5);" title="Clique para abrir o espectro livre de cores">' +
               '<input type="color" value="' + escapeAttr(hexVal) + '" oninput="' + liveInputCall + '" onchange="' + changeCall + '" style="opacity:0; position:absolute; top:0; left:0; width:100%; height:100%; cursor:pointer;">' +
               '</div>' +
               '<input type="text" class="inspector-input" value="' + escapeAttr(curVal || '') + '" oninput="' + liveInputCall + '" placeholder="var(--color-primary), #hex ou rgba" style="margin:0; font-size:11px; font-family:monospace; flex:1;">' +
               '<button type="button" class="header-btn" style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.06); color:var(--text-muted);" onclick="' + clearCall + '" title="Limpar / Restaurar Padrão">✕</button>' +
               '</div>' +

               '</div>';
    }

    function renderSectionTokenColorPicker(label, key, curVal, defaultToken) {
        return buildUniversalTokenColorPicker(label, key, curVal, defaultToken, 'section');
    }

    function inspectorTokenColorPicker(label, key, curVal, defaultToken) {
        return buildUniversalTokenColorPicker(label, key, curVal, defaultToken, 'block');
    }

    function renderSectionFontSelector(label, key, curVal) {
        var dt = window.DS_DESIGN_TOKENS || {};
        var typo = dt.typography || {};
        var fontPrimary = typo.primary_font || 'Inter, sans-serif';
        var fontSecondary = typo.secondary_font || 'Fira Code, monospace';
        var fontTertiary = typo.tertiary_font || 'Montserrat, sans-serif';

        var opts = [
            { value: '', label: 'Herdar Fonte Global' },
            { value: 'var(--font-primary)', label: '⭐ Fonte Primária (' + fontPrimary.split(',')[0].replace(/['"]/g, '') + ')' },
            { value: 'var(--font-secondary)', label: '⭐ Fonte Secundária (' + fontSecondary.split(',')[0].replace(/['"]/g, '') + ')' },
            { value: 'var(--font-tertiary)', label: '⭐ Fonte Terciária (' + fontTertiary.split(',')[0].replace(/['"]/g, '') + ')' },
            { value: 'Inter, sans-serif', label: 'Inter' },
            { value: 'Montserrat, sans-serif', label: 'Montserrat' },
            { value: 'Poppins, sans-serif', label: 'Poppins' },
            { value: 'Roboto, sans-serif', label: 'Roboto' },
            { value: "'Fira Code', monospace", label: 'Fira Code' }
        ];

        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateActiveSecSetting(\'' + key + '\', this.value); renderCanvas(); renderSidebarLayoutTab();">' +
               opts.map(function(o) {
                   return '<option value="' + escapeAttr(o.value) + '"' + (curVal === o.value ? ' selected' : '') + '>' + escapeHtml(o.label) + '</option>';
               }).join('') +
               '</select></div>';
    }

    function stepSecSingleValue(key, delta, minVal, maxVal) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        if (!curSec.container_settings) curSec.container_settings = {};
        var cur = parseFloat(curSec.container_settings[key] || '0');
        if (isNaN(cur)) cur = minVal;
        var next = Math.max(minVal, Math.min(maxVal, cur + delta));
        curSec.container_settings[key] = next + 'px';
        renderCanvas();
        renderSidebarLayoutTab();
    }

    function onSecSingleNumChange(key, rawVal) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        if (!curSec.container_settings) curSec.container_settings = {};
        var num = parseFloat(rawVal);
        if (isNaN(num)) return;
        curSec.container_settings[key] = num + 'px';
        renderCanvas();
    }

// CONTROLES DE ESPAÇAMENTOS E DIMENSÕES DA SEÇÃO (STEPPERS + RESET)
    var spacingLinkState = {
        margin: false, // Separado por padrão para margin externa (normalmente diferente em cima/baixo)
        padding: true  // Vinculado por padrão para padding interno
    };

    function toggleSpacingLink(type) {
        spacingLinkState[type] = !spacingLinkState[type];
        updateSpacingLinkUI(type);
        showToast(type === 'padding' 
            ? (spacingLinkState[type] ? '🔗 Padding: 4 lados vinculados.' : '🔓 Padding: 4 lados separados.')
            : (spacingLinkState[type] ? '🔗 Margem: 4 lados vinculados.' : '🔓 Margem: 4 lados separados.')
        );
    }

    function updateSpacingLinkUI(type) {
        var btn = document.getElementById(type + '_link_btn');
        if (!btn) return;
        var isLinked = spacingLinkState[type];
        btn.classList.toggle('unlinked', !isLinked);
        btn.innerHTML = isLinked ? '🔗 Vinculado' : '🔓 Separado';
    }

    function parse4Values(str) {
        if (!str || typeof str !== 'string') return [0, 0, 0, 0];
        var parts = str.trim().split(/\s+/).map(function(p) {
            var n = parseFloat(p);
            return isNaN(n) ? 0 : n;
        });
        if (parts.length === 0) return [0, 0, 0, 0];
        if (parts.length === 1) return [parts[0], parts[0], parts[0], parts[0]];
        if (parts.length === 2) return [parts[0], parts[1], parts[0], parts[1]];
        if (parts.length === 3) return [parts[0], parts[1], parts[2], parts[1]];
        if (parts.length >= 4) return [parts[0], parts[1], parts[2], parts[3]];
        return [0, 0, 0, 0];
    }

    function getQuadIds(type) {
        if (type === 'padding') return ['pad_top', 'pad_right', 'pad_bottom', 'pad_left'];
        return ['mar_top', 'mar_right', 'mar_bottom', 'mar_left'];
    }

    function stepQuadValue(type, sideIdx, delta) {
        var ids = getQuadIds(type);
        var curVals = ids.map(function(id) {
            var el = document.getElementById(id);
            var n = parseFloat(el ? el.value : 0);
            return isNaN(n) ? 0 : n;
        });

        if (spacingLinkState[type]) {
            var newVal = Math.max(0, curVals[sideIdx] + delta);
            curVals = [newVal, newVal, newVal, newVal];
        } else {
            curVals[sideIdx] = Math.max(0, curVals[sideIdx] + delta);
        }

        applyQuadValues(type, curVals);
    }

    function onQuadInputChange(type, sideIdx, rawVal) {
        var num = parseFloat(rawVal);
        if (isNaN(num) || num < 0) num = 0;
        var ids = getQuadIds(type);
        var curVals = ids.map(function(id) {
            var el = document.getElementById(id);
            var n = parseFloat(el ? el.value : 0);
            return isNaN(n) ? 0 : n;
        });

        if (spacingLinkState[type]) {
            curVals = [num, num, num, num];
        } else {
            curVals[sideIdx] = num;
        }

        applyQuadValues(type, curVals);
    }

    function applyQuadValues(type, vals) {
        var ids = getQuadIds(type);
        ids.forEach(function(id, i) {
            var el = document.getElementById(id);
            if (el) el.value = vals[i];
        });

        var cssVal = vals[0] + 'px ' + vals[1] + 'px ' + vals[2] + 'px ' + vals[3] + 'px';
        updateActiveSecSetting(type, cssVal);
    }

    function stepSingleValue(key, delta, minVal, maxVal) {
        var el = document.getElementById('setting_' + key + '_num');
        if (!el) return;
        var cur = parseFloat(el.value);
        if (isNaN(cur)) cur = minVal;
        var next = Math.max(minVal, Math.min(maxVal, cur + delta));
        el.value = next;
        onSingleNumChange(key, next);
    }

    function onSingleNumChange(key, rawVal) {
        var num = parseFloat(rawVal);
        if (isNaN(num)) return;
        var unit = 'px';
        var cssVal = num + unit;

        var displayEl = document.getElementById(key + '_display_val');
        if (displayEl) displayEl.textContent = cssVal;

        updateActiveSecSetting(key, cssVal);
    }

    function setMaxWidthPreset(val) {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        if (!curSec.container_settings) curSec.container_settings = {};
        curSec.container_settings.max_width = val;

        var numEl = document.getElementById('setting_max_width_num');
        if (numEl) {
            var n = parseFloat(val);
            numEl.value = isNaN(n) ? 1280 : n;
        }
        renderCanvas();
        showToast('Largura Máxima definida para: ' + val);
    }

    function resetSectionSpacings() {
        var curSec = state.sections[activeSecIdx];
        if (!curSec) return;
        if (!curSec.container_settings) curSec.container_settings = {};

        curSec.container_settings.margin = '0px 0px 16px 0px';
        curSec.container_settings.padding = '16px 24px';
        curSec.container_settings.gap = '24px';
        curSec.container_settings.max_width = '1280px';
        curSec.container_settings.background = 'transparent';
        delete curSec.container_settings.border;
        delete curSec.container_settings.border_radius;
        delete curSec.container_settings.box_shadow;
        delete curSec.container_settings.opacity;
        delete curSec.container_settings.text_color;
        delete curSec.container_settings.color;
        delete curSec.container_settings.heading_color;
        delete curSec.container_settings.link_color;
        delete curSec.container_settings.link_hover_color;
        delete curSec.container_settings.font_family;
        delete curSec.container_settings.text_align;
        delete curSec.container_settings.text_transform;

        renderSidebarLayoutTab();
        renderCanvas();
        showToast('↺ Espaçamentos da seção redefinidos para os padrões!');
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

        var curPartType = '<?= $part->getType() ?>';
        blocksCatalog.forEach(function(b) {
            if (b.allowed_types && b.allowed_types.indexOf(curPartType) === -1) return;
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

    function getColumnTitle(presetKey, colIdx, totalCols, sec) {
        if (sec && sec.columns_labels && sec.columns_labels[colIdx]) {
            return sec.columns_labels[colIdx];
        }
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
                if (sec.container_settings.text_color || sec.container_settings.color) secCard.style.color = sec.container_settings.text_color || sec.container_settings.color;
                if (sec.container_settings.border) secCard.style.border = sec.container_settings.border;
                if (sec.container_settings.border_radius) secCard.style.borderRadius = sec.container_settings.border_radius;
                if (sec.container_settings.box_shadow) secCard.style.boxShadow = sec.container_settings.box_shadow;
                if (sec.container_settings.opacity !== undefined && sec.container_settings.opacity !== '') secCard.style.opacity = sec.container_settings.opacity;
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
                                      '<button type="button" class="block-act-btn" onclick="event.stopPropagation(); openAddBlockForCell(' + secIdx + ',' + colIdx + ')" title="Clique para escolher um bloco">+ Bloco</button>';
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
                dropzone.title = 'Clique para escolher um bloco nesta divisão ou arraste da barra lateral';
                dropzone.innerHTML = '➕ Adicionar Bloco nesta Divisão';
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
        if (studioMode === 'live') {
            loadLiveStageContent();
        }
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
        
        // Aplica estilos visuais configurados no elemento diretamente no card para preview instantâneo
        if (block.data) {
            if (block.data.margin) card.style.margin = block.data.margin;
            if (block.data.padding) card.style.padding = block.data.padding;
            if (block.data.background) card.style.background = block.data.background;
            if (block.data.text_color || block.data.color) card.style.color = block.data.text_color || block.data.color;
            if (block.data.border_radius) card.style.borderRadius = block.data.border_radius;
            if (block.data.border) card.style.border = block.data.border;
            if (block.data.box_shadow) card.style.boxShadow = block.data.box_shadow;
            if (block.data.opacity !== undefined && block.data.opacity !== '' && block.data.opacity !== '1') card.style.opacity = block.data.opacity;
        }

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
        var blockDisplayName = block.custom_name || bMeta.name;
        var bData = block.data || {};
        var hiddenBadges = [];
        if (bData.hide_desktop) hiddenBadges.push('Desktop');
        if (bData.hide_laptop || bData.hide_1024) hiddenBadges.push('1024px');
        if (bData.hide_tablet || bData.hide_768) hiddenBadges.push('768px');
        if (bData.hide_mobile || bData.hide_375) hiddenBadges.push('375px');

        var isHiddenOnCurrentDevice =
            (currentDevice === '100%' && !!bData.hide_desktop) ||
            (currentDevice === '1024px' && !!(bData.hide_laptop || bData.hide_1024)) ||
            (currentDevice === '768px' && !!(bData.hide_tablet || bData.hide_768)) ||
            (currentDevice === '375px' && !!(bData.hide_mobile || bData.hide_375));

        if (isHiddenOnCurrentDevice) {
            card.style.opacity = '0.45';
            card.style.border = '1px dashed #ef4444';
        }

        var visBadgeHtml = '';
        if (hiddenBadges.length > 0) {
            visBadgeHtml = '<span style="font-size:9px; padding:1px 5px; border-radius:4px; background:rgba(239,68,68,0.18); border:1px solid rgba(239,68,68,0.4); color:#fca5a5; font-weight:700; margin-left:4px;" title="Elemento oculto em: ' + escapeAttr(hiddenBadges.join(', ')) + '">🚫 ' + escapeHtml(hiddenBadges.join(' · ')) + '</span>';
        }

        var bHeader = document.createElement('div');
        bHeader.className = 'canvas-block-header';
        bHeader.innerHTML = '<span ondblclick="startInlineRenameBlock(' + secIdx + ',' + colIdx + ',' + blockIdx + ', this, event)" title="Duplo clique para renomear" style="cursor:pointer; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">' +
            '<span>' + bMeta.icon + '</span> <strong>' + escapeHtml(blockDisplayName) + '</strong>' + visBadgeHtml +
            '</span>' +
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
        if (block.type === 'button') {
            var bg = d.bg_color || 'var(--color-primary)';
            var col = d.text_color || '#000';
            var hBg = d.hover_bg_color || '';
            var hCol = d.hover_text_color || '';
            var anim = d.hover_animation || 'lift';
            var animClass = (anim && anim !== 'none') ? ' ds-btn-' + anim : ' ds-btn-static';
            var hoverStyle = '';
            if (hBg) hoverStyle += ' --btn-hover-bg:' + hBg + ';';
            if (hCol) hoverStyle += ' --btn-hover-color:' + hCol + ';';
            var bRadius = d.border_radius || '8px';
            return '<div style="display:inline-flex; align-items:center; gap:8px; margin:2px 0;">' +
                   '<span class="ds-block-btn ' + animClass + '" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:' + escapeAttr(bRadius) + '; font-size:12px; font-weight:700; background:' + escapeAttr(bg) + '; color:' + escapeAttr(col) + ';' + hoverStyle + ' cursor:pointer; text-decoration:none; box-shadow:0 2px 8px rgba(0,0,0,0.2);">' + 
                   (d.icon ? escapeHtml(d.icon) + ' ' : '🔘 ') +
                   escapeHtml(d.label || 'Botão') + '</span>' +
                   '<span style="font-size:10px; opacity:0.6;">[' + (d.action_type || 'link') + ']</span>' +
                   (hBg ? '<span style="font-size:9px; padding:2px 6px; border-radius:4px; background:rgba(56,189,248,0.12); color:#38bdf8;" title="Hover: ' + escapeAttr(hBg) + '">🎨 Hover ativo</span>' : '') +
                   '</div>';
        }
        if (block.type === 'cta_button') {
            var bg = d.bg_color || 'var(--primary)';
            var col = d.text_color || '#000';
            var hBg = d.hover_bg_color || '';
            var hCol = d.hover_text_color || '';
            var anim = d.hover_animation || 'pulse';
            var animClass = (anim && anim !== 'none') ? ' ds-btn-' + anim : ' ds-btn-static';
            var hoverStyle = '';
            if (hBg) hoverStyle += ' --btn-hover-bg:' + hBg + ';';
            if (hCol) hoverStyle += ' --btn-hover-color:' + hCol + ';';
            return '<div style="display:inline-flex; align-items:center; gap:8px; margin:2px 0;">' +
                   '<span class="ds-block-cta ' + animClass + '" style="display:inline-flex; align-items:center; gap:6px; padding:6px 16px; border-radius:6px; font-size:12px; font-weight:800; background:' + escapeAttr(bg) + '; color:' + escapeAttr(col) + ';' + hoverStyle + ' cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.3);">' + 
                   '🚀 ' + escapeHtml(d.label || 'Botão CTA') + '</span>' +
                   (hBg ? '<span style="font-size:9px; padding:2px 6px; border-radius:4px; background:rgba(56,189,248,0.12); color:#38bdf8;" title="Hover: ' + escapeAttr(hBg) + '">🎨 Hover ativo</span>' : '') +
                   '</div>';
        }
        if (block.type === 'user_auth') {
            return '<span style="display:inline-flex; align-items:center; gap:6px; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:rgba(69,243,255,0.15); border:1px solid rgba(69,243,255,0.3); color:#45f3ff;">👤 ' + 
                   escapeHtml(d.login_text || 'Entrar') + ' / ' + escapeHtml(d.register_text || 'Cadastrar') + '</span>';
        }
        if (block.type === 'shortcode') return '<code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:#38bdf8;">' + escapeHtml(d.code || d.tag || '[shortcode]') + '</code>';
        if (block.type === 'logo') {
            var logoTxt = d.text || d.brand_text || 'DomainSystem';
            if (d.image_url) {
                return '<div style="display:flex; align-items:center; gap:8px;">' +
                       '<img src="' + escapeAttr(d.image_url) + '" style="height:22px; max-width:80px; object-fit:contain; border-radius:2px; background:rgba(255,255,255,0.05);">' +
                       '<span style="color:#fff; font-weight:600;">' + escapeHtml(logoTxt) + '</span>' +
                       '</div>';
            }
            return '<strong style="color:#fff;">' + escapeHtml(logoTxt) + '</strong>';
        }
        if (block.type === 'nav_menu') {
            var curStyle = d.toggle_style || state.mobile_toggle_style || 'classic';
            var curAlign = d.toggle_align || d.align || state.mobile_toggle_align || 'right';
            var isMobile = (currentDevice === '375px' || currentDevice === '768px');
            var toggleLabel = '☰';
            if (curStyle === 'pill') toggleLabel = (d.toggle_label || 'MENU') + ' ☰';
            if (curStyle === 'minimal') toggleLabel = '═';
            if (curStyle === 'dots') toggleLabel = '⋮';
            if (curStyle === 'boxed') toggleLabel = '▣ ☰';
            if (curStyle === 'grid') toggleLabel = '⠿';
            if (curStyle === 'rounded_glow') toggleLabel = '◉ ☰';
            if (curStyle === 'cyber') toggleLabel = '[☰]';

            var justifyVal = (curAlign === 'left') ? 'flex-start' : ((curAlign === 'center') ? 'center' : 'flex-end');
            var itemStyleLabel = d.mobile_item_style || 'card';

            if (isMobile) {
                return '<div style="display:flex; justify-content:' + justifyVal + '; width:100%; align-items:center; gap:8px;">' +
                       '<button type="button" class="header-btn" style="padding:3px 10px; font-size:12px; background:' + escapeAttr(d.toggle_bg_color || 'rgba(56,189,248,0.2)') + '; border-color:' + escapeAttr(d.toggle_border_color || 'var(--primary)') + '; color:' + escapeAttr(d.toggle_color || 'var(--primary)') + ';" onclick="event.stopPropagation(); testMobileDrawer(\'' + escapeAttr(curStyle) + '\')">' + escapeHtml(toggleLabel) + '</button>' +
                       '<span style="font-size:11px; color:#38bdf8;">📱 ' + (curAlign === 'left' ? '⬅️ Esq' : (curAlign === 'center' ? '🎯 Centro' : '➡️ Dir')) + ' (' + escapeHtml(curStyle) + ' · ' + escapeHtml(itemStyleLabel) + ')</span>' +
                       '</div>';
            } else {
                var alignText = (curAlign === 'left') ? '⬅️ Esquerda' : ((curAlign === 'center') ? '🎯 Centro' : '➡️ Direita');
                return '🧭 Menu: <strong>' + escapeHtml(d.menu_id || 'Principal') + '</strong> <span style="font-size:10px; color:#38bdf8; margin-left:8px; padding:2px 6px; background:rgba(56,189,248,0.1); border-radius:4px;">📱 Toggle: ' + alignText + ' (' + escapeHtml(curStyle) + ' · Botões: ' + escapeHtml(itemStyleLabel) + ')</span>';
            }
        }
        if (block.type === 'divider') return 'Estilo: ' + (d.style || 'solid') + ' (' + (d.height || '20px') + ')';
        if (block.type === 'copyright') return '© ' + new Date().getFullYear() + ' ' + escapeHtml(d.site_name || 'Domain-System');
        if (block.type === 'social_icons') {
            var nets = d.networks || [];
            var count = Array.isArray(nets) ? nets.length : (typeof nets === 'object' && nets !== null ? Object.keys(nets).length : 0);
            var shapeLabel = (d.shape === 'circle' ? 'Círculo' : (d.shape === 'rounded' ? 'Arredondado' : (d.shape === 'square' ? 'Quadrado' : 'Minimal')));
            return '🌐 Redes Sociais: <strong>' + count + ' ícones</strong> <span style="font-size:10px; opacity:0.75; padding:2px 6px; background:rgba(56,189,248,0.12); color:var(--primary); border-radius:4px; margin-left:6px;">' + shapeLabel + ' • ' + (d.color_mode || 'official') + '</span>';
        }
        if (block.type === 'post_cards_grid') {
            var cols = d.columns || 3;
            var limit = d.limit || 6;
            var styleLabel = d.card_style || 'cyber_glass';
            return '📰 Grid de Artigos: <strong>' + cols + ' colunas</strong> (até ' + limit + ' posts) <span style="font-size:10px; opacity:0.75; padding:2px 6px; background:rgba(56,189,248,0.12); color:var(--primary); border-radius:4px; margin-left:6px;">' + styleLabel + '</span>';
        }
        return block.type;
    }

    var isEditorMobileDrawerOpen = false;

    function findActiveOrFirstNavBlock() {
        var blk = getSelectedBlock();
        if (blk && blk.type === 'nav_menu') return blk;
        if (state.sections) {
            for (var s = 0; s < state.sections.length; s++) {
                var sec = state.sections[s];
                if (sec && sec.columns_data) {
                    for (var col in sec.columns_data) {
                        var list = sec.columns_data[col];
                        if (Array.isArray(list)) {
                            for (var b = 0; b < list.length; b++) {
                                if (list[b] && list[b].type === 'nav_menu') return list[b];
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    function testMobileDrawer(style) {
        isEditorMobileDrawerOpen = !isEditorMobileDrawerOpen;
        renderEditorMobileDrawerOverlay(isEditorMobileDrawerOpen);
    }

    function closeEditorMobileDrawer() {
        isEditorMobileDrawerOpen = false;
        renderEditorMobileDrawerOverlay(false);
    }

    function renderEditorMobileDrawerOverlay(open) {
        var viewport = document.getElementById('device-viewport');
        if (!viewport) return;
        var existing = document.getElementById('studio_mobile_drawer_overlay');
        if (!open) {
            if (existing) existing.remove();
            return;
        }

        var navBlock = findActiveOrFirstNavBlock();
        var d = (navBlock && navBlock.data) ? navBlock.data : {};
        var toggleAlign = d.toggle_align || d.align || state.mobile_toggle_align || 'right';
        var drawerPos = d.drawer_position || (toggleAlign === 'left' ? 'left' : 'right');
        var drawerTitle = (d.drawer_title !== undefined) ? d.drawer_title : 'Navegação';
        var activeColor = d.active_color || 'var(--color-primary, #38bdf8)';
        var linkColor = d.link_color || 'var(--color-text-main, #c5c6c7)';
        var drawerTitleColor = d.drawer_title_color || activeColor;
        var drawerBg = d.drawer_bg || 'var(--color-surface, rgba(11, 14, 18, 0.98))';
        var drawerBorderColor = d.drawer_border_color || 'var(--color-border, rgba(255, 255, 255, 0.08))';
        var drawerWidth = d.drawer_width || '320px';

        var itemStyle = d.mobile_item_style || 'card';
        var itemAlign = d.mobile_item_align || 'left';
        var itemJustify = (itemAlign === 'center') ? 'center' : (itemAlign === 'right' ? 'flex-end' : 'flex-start');
        var itemGap = d.mobile_item_gap || '8px';
        var itemPadding = d.mobile_item_padding || '12px 16px';
        var mobileLinkColor = d.mobile_link_color || (itemStyle === 'filled' ? 'var(--color-background, #0b0c10)' : linkColor);
        var mobileHoverTextColor = d.mobile_hover_text_color || activeColor;

        var defaultItemBg = 'transparent';
        if (itemStyle === 'card') defaultItemBg = 'rgba(255, 255, 255, 0.04)';
        else if (itemStyle === 'pill') defaultItemBg = 'rgba(255, 255, 255, 0.05)';
        else if (itemStyle === 'filled') defaultItemBg = 'var(--color-primary, #38bdf8)';
        else if (itemStyle === 'cyber_bar') defaultItemBg = 'rgba(56, 189, 248, 0.05)';
        var mobileItemBg = d.mobile_item_bg || defaultItemBg;

        var defaultItemBorder = '1px solid transparent';
        if (itemStyle === 'card') defaultItemBorder = '1px solid rgba(255, 255, 255, 0.07)';
        else if (itemStyle === 'pill') defaultItemBorder = '1px solid rgba(255, 255, 255, 0.08)';
        else if (itemStyle === 'outlined') defaultItemBorder = '1px solid ' + (d.mobile_item_border_color || 'var(--color-border, rgba(255,255,255,0.18))');
        else if (itemStyle === 'divider') defaultItemBorder = 'none';
        if (d.mobile_item_border_color && itemStyle !== 'divider') {
            defaultItemBorder = '1px solid ' + d.mobile_item_border_color;
        }

        var defaultRadius = '8px';
        if (itemStyle === 'pill') defaultRadius = '999px';
        else if (itemStyle === 'divider' || itemStyle === 'cyber_bar') defaultRadius = '4px';
        var itemRadius = d.mobile_item_radius || defaultRadius;

        var hoverBg = d.mobile_hover_bg_color || 'rgba(56, 189, 248, 0.14)';
        var hoverBorder = d.mobile_hover_border_color || mobileHoverTextColor;
        var hoverEffect = d.mobile_hover_effect || 'slide_right';
        var hoverTransform = '';
        if (hoverEffect === 'slide_right') hoverTransform = 'transform: translateX(6px);';
        else if (hoverEffect === 'scale') hoverTransform = 'transform: scale(1.03);';
        else if (hoverEffect === 'glow') hoverTransform = 'box-shadow: 0 0 16px ' + hoverBorder + ';';

        var cyberBarCss = (itemStyle === 'cyber_bar') ? 'border-left: 3px solid ' + hoverBorder + ' !important;' : '';
        var dividerCss = (itemStyle === 'divider') ? 'border-bottom: 1px solid ' + drawerBorderColor + '; border-radius: 0;' : '';

        var posCss = 'top: 0; right: 0; bottom: 0; width: 85%; max-width: ' + drawerWidth + '; border-left: 1px solid ' + drawerBorderColor + ';';
        if (drawerPos === 'left') {
            posCss = 'top: 0; left: 0; bottom: 0; width: 85%; max-width: ' + drawerWidth + '; border-right: 1px solid ' + drawerBorderColor + ';';
        } else if (drawerPos === 'top') {
            posCss = 'top: 0; left: 0; right: 0; width: 100%; height: auto; border-bottom: 1px solid ' + drawerBorderColor + ';';
        } else if (drawerPos === 'fullscreen') {
            posCss = 'inset: 0; width: 100%; height: 100%;';
        }

        if (!existing) {
            existing = document.createElement('div');
            existing.id = 'studio_mobile_drawer_overlay';
            viewport.style.position = 'relative';
            viewport.appendChild(existing);
        }

        var sampleItems = [
            { title: 'Início', sub: [] },
            { title: 'Cursos', sub: ['Arquitetura SOLID', 'Flex Builder Pro'] },
            { title: 'Sobre', sub: [] },
            { title: 'Painel', sub: [] }
        ];
        var itemsHtml = sampleItems.map(function(itemObj, idx) {
            var isAct = (idx === 0) ? ' active' : '';
            if (itemObj.sub && itemObj.sub.length > 0) {
                var subLinks = itemObj.sub.map(function(sLbl) {
                    return '<li><a href="javascript:void(0)" class="studio-preview-mob-link" style="font-size:0.9rem;">' + escapeHtml(sLbl) + '</a></li>';
                }).join('');
                return '<li>' +
                       '<div style="display:flex; gap:6px; align-items:stretch;">' +
                           '<a href="javascript:void(0)" class="studio-preview-mob-link" style="flex:1;" onclick="var u=this.parentElement.nextElementSibling; u.style.display=(u.style.display===\'none\'?\'flex\':\'none\');">' + escapeHtml(itemObj.title) + '</a>' +
                           '<button type="button" onclick="var u=this.parentElement.nextElementSibling; u.style.display=(u.style.display===\'none\'?\'flex\':\'none\');" style="width:40px; border-radius:' + itemRadius + '; background:' + mobileItemBg + '; border:' + defaultItemBorder + '; color:' + mobileLinkColor + '; cursor:pointer;">▾</button>' +
                       '</div>' +
                       '<ul style="list-style:none; margin:6px 0 4px 14px; padding:0 0 0 10px; border-left:2px solid ' + drawerBorderColor + '; display:flex; flex-direction:column; gap:6px;">' + subLinks + '</ul>' +
                       '</li>';
            }
            return '<li><a href="javascript:void(0)" class="studio-preview-mob-link' + isAct + '">' + escapeHtml(itemObj.title) + '</a></li>';
        }).join('');

        existing.innerHTML =
            '<style>' +
            '#studio_mobile_drawer_overlay { position: absolute; inset: 34px 0 0 0; z-index: 950; display: flex; }' +
            '#studio_mobile_drawer_overlay .studio-mob-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.62); backdrop-filter: blur(3px); }' +
            '#studio_mobile_drawer_overlay .studio-mob-panel { position: absolute; ' + posCss + ' background: ' + drawerBg + '; backdrop-filter: blur(18px); padding: 22px; display: flex; flex-direction: column; gap: 16px; z-index: 2; box-shadow: 0 10px 35px rgba(0,0,0,0.75); overflow-y: auto; }' +
            '#studio_mobile_drawer_overlay .studio-mob-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: ' + itemGap + '; }' +
            '#studio_mobile_drawer_overlay .studio-preview-mob-link { display: flex; align-items: center; justify-content: ' + itemJustify + '; text-align: ' + itemAlign + '; padding: ' + itemPadding + '; border-radius: ' + itemRadius + '; color: ' + mobileLinkColor + '; background: ' + mobileItemBg + '; border: ' + defaultItemBorder + '; text-decoration: none; font-size: 0.98rem; font-family: ' + (d.font_family || 'var(--font-primary, inherit)') + '; font-weight: ' + (d.font_weight || '500') + '; transition: all 0.22s ease; ' + cyberBarCss + dividerCss + ' }' +
            '#studio_mobile_drawer_overlay .studio-preview-mob-link:hover, #studio_mobile_drawer_overlay .studio-preview-mob-link.active { color: ' + mobileHoverTextColor + ' !important; background: ' + hoverBg + ' !important; border-color: ' + hoverBorder + ' !important; ' + hoverTransform + ' }' +
            '</style>' +
            '<div class="studio-mob-backdrop" onclick="closeEditorMobileDrawer()"></div>' +
            '<aside class="studio-mob-panel">' +
                '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid ' + drawerBorderColor + '; padding-bottom:12px;">' +
                    '<span style="font-weight:800; font-size:0.88rem; letter-spacing:1px; text-transform:uppercase; color:' + drawerTitleColor + ';">' + escapeHtml(drawerTitle) + '</span>' +
                    '<button type="button" onclick="closeEditorMobileDrawer()" style="background:rgba(255,255,255,0.08); border:1px solid ' + drawerBorderColor + '; color:' + mobileLinkColor + '; width:30px; height:30px; border-radius:8px; cursor:pointer;">✕</button>' +
                '</div>' +
                '<ul class="studio-mob-list">' + itemsHtml + '</ul>' +
                '<div style="margin-top:auto; padding-top:10px; border-top:1px dashed rgba(255,255,255,0.08); font-size:10px; color:var(--text-muted); text-align:center;">💡 Passe o mouse sobre os botões acima para testar as cores Hover em tempo real!</div>' +
            '</aside>';
    }

    function selectBlock(secIdx, colIdx, blockIdx) {
        selectionType = 'block';
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        selectedBlockCoord = { sec: secIdx, col: colIdx, blockIdx: blockIdx };
        renderCanvas();
        renderInspector();
        renderLayersTree();
        var curSec = getSelectedSection();
        if (curSec) {
            renderSectionStyleControls(curSec);
        }

        // Se a janela flutuante estiver aberta, atualiza a barra lateral para o Inspetor
        // permitindo que o usuário edite sem perder o popup flutuante de camadas!
        var floatingPanel = document.getElementById('floating_layers_panel');
        var isFloatingOpen = floatingPanel && floatingPanel.style.display !== 'none';
        if (isFloatingOpen || currentSidebarTab !== 'layers') {
            switchSidebarTab('inspector');
        }
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

        var floatingPanel = document.getElementById('floating_layers_panel');
        var isFloatingOpen = floatingPanel && floatingPanel.style.display !== 'none';
        if (isFloatingOpen || currentSidebarTab !== 'layers') {
            switchSidebarTab('blocks');
        }
        showToast('Célula ' + (colIdx + 1) + ' selecionada.');
    }

    function renderLayersTree() {
        var targets = [
            document.getElementById('layers_tree_container'),
            document.getElementById('floating_layers_tree_container')
        ];
        targets.forEach(function(c) {
            if (c) populateLayersTree(c);
        });
    }

    function populateLayersTree(container) {
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
                    '<span class="layer-text" ondblclick="startInlineRenameSection(' + sIdx + ', this, event)" title="Duplo clique para renomear">' + escapeHtml(secTitle) + '</span>' +
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

                var colTitle = getColumnTitle(sec.layout_preset, cIdx, presetInfo.columns, sec);
                var blocksInCol = (sec.columns_data && sec.columns_data[cIdx]) ? sec.columns_data[cIdx] : [];

                var colHeader = document.createElement('div');
                colHeader.className = 'layer-row layer-row-col';
                colHeader.innerHTML = 
                    '<div class="layer-title-wrap" onclick="selectColFromLayers(' + sIdx + ', ' + cIdx + ', event)">' +
                        '<span class="layer-icon">▦</span>' +
                        '<span class="layer-text" ondblclick="startInlineRenameColumn(' + sIdx + ', ' + cIdx + ', this, event)" title="Duplo clique para renomear">' + escapeHtml(colTitle) + '</span>' +
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
                    var blockDisplayName = block.custom_name || bMeta.name;

                    bNode.innerHTML = 
                        '<div class="layer-title-wrap" onclick="selectBlock(' + sIdx + ', ' + cIdx + ', ' + bIdx + ')">' +
                            '<span class="layer-icon">' + bMeta.icon + '</span>' +
                            '<span class="layer-text" ondblclick="startInlineRenameBlock(' + sIdx + ', ' + cIdx + ', ' + bIdx + ', this, event)" title="Duplo clique para renomear">' + escapeHtml(blockDisplayName) + '</span>' +
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

    // RENOMEAÇÃO INLINE NAS CAMADAS (ELEMENTOR-STYLE INLINE RENAME)
    function startInlineRenameSection(secIdx, el, e) {
        if (e) e.stopPropagation();
        var sec = state.sections[secIdx];
        if (!sec) return;
        var currentText = sec.title || ('Seção ' + (secIdx + 1));
        var input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.className = 'layer-inline-input';
        input.style.cssText = 'background:#1e293b; color:#fff; border:1px solid #38bdf8; border-radius:4px; font-size:12px; padding:2px 6px; width:120px; outline:none;';
        
        var finish = function() {
            var val = input.value.trim();
            if (val) {
                sec.title = val;
                var secTitleInput = document.getElementById('sec_title_input');
                if (secTitleInput && activeSecIdx === secIdx) secTitleInput.value = val;
            }
            renderCanvas();
            renderLayersTree();
        };

        input.onblur = finish;
        input.onkeydown = function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                input.blur();
            } else if (ev.key === 'Escape') {
                ev.preventDefault();
                renderLayersTree();
            }
        };

        el.innerHTML = '';
        el.appendChild(input);
        input.focus();
        input.select();
    }

    function startInlineRenameColumn(secIdx, colIdx, el, e) {
        if (e) e.stopPropagation();
        var sec = state.sections[secIdx];
        if (!sec) return;
        if (!sec.columns_labels) sec.columns_labels = {};
        var currentText = sec.columns_labels[colIdx] || getColumnTitle(sec.layout_preset, colIdx, (presets[sec.layout_preset] || {}).columns || 3, sec);
        
        var input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.className = 'layer-inline-input';
        input.style.cssText = 'background:#1e293b; color:#fff; border:1px solid #38bdf8; border-radius:4px; font-size:12px; padding:2px 6px; width:120px; outline:none;';

        var finish = function() {
            var val = input.value.trim();
            if (val) {
                sec.columns_labels[colIdx] = val;
            }
            renderCanvas();
            renderLayersTree();
        };

        input.onblur = finish;
        input.onkeydown = function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                input.blur();
            } else if (ev.key === 'Escape') {
                ev.preventDefault();
                renderLayersTree();
            }
        };

        el.innerHTML = '';
        el.appendChild(input);
        input.focus();
        input.select();
    }

    function startInlineRenameBlock(secIdx, colIdx, blockIdx, el, e) {
        if (e) e.stopPropagation();
        var sec = state.sections[secIdx];
        if (!sec || !sec.columns_data || !sec.columns_data[colIdx]) return;
        var block = sec.columns_data[colIdx][blockIdx];
        if (!block) return;
        var bMeta = blocksCatalog.find(function(x) { return x.type === block.type; }) || { name: block.type };
        var currentText = block.custom_name || bMeta.name;

        var input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.className = 'layer-inline-input';
        input.style.cssText = 'background:#1e293b; color:#fff; border:1px solid #38bdf8; border-radius:4px; font-size:12px; padding:2px 6px; width:130px; outline:none;';

        var finish = function() {
            var val = input.value.trim();
            block.custom_name = val || null;
            renderCanvas();
            renderLayersTree();
            if (selectedBlockCoord && selectedBlockCoord.sec === secIdx && selectedBlockCoord.col === colIdx && selectedBlockCoord.blockIdx === blockIdx) {
                renderInspector();
            }
        };

        input.onblur = finish;
        input.onkeydown = function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                input.blur();
            } else if (ev.key === 'Escape') {
                ev.preventDefault();
                renderLayersTree();
            }
        };

        el.innerHTML = '';
        el.appendChild(input);
        input.focus();
        input.select();
    }

    function updateBlockCustomName(val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block) return;
        block.custom_name = val.trim() || null;
        renderCanvas();
        renderLayersTree();
    }

    function selectGlobalToggleStyle(val) {
        state.mobile_toggle_style = val;
        var hidden = document.getElementById('setting_mobile_toggle');
        if (hidden) hidden.value = val;
        ['classic', 'pill', 'minimal', 'dots', 'boxed', 'grid', 'rounded_glow', 'cyber'].forEach(function(s) {
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

    function selectGlobalToggleAlign(val) {
        state.mobile_toggle_align = val;
        var sel = document.getElementById('setting_mobile_toggle_align');
        if (sel) sel.value = val;
        if (state.sections) {
            state.sections.forEach(function(sec) {
                if (sec.columns_data) {
                    for (var col in sec.columns_data) {
                        if (Array.isArray(sec.columns_data[col])) {
                            sec.columns_data[col].forEach(function(b) {
                                if (b.type === 'nav_menu') {
                                    b.data = b.data || {};
                                    b.data.toggle_align = val;
                                }
                            });
                        }
                    }
                }
            });
        }
        renderCanvas();
        if (selectionType === 'block') renderInspector();
        showToast('Posição do toggle mobile: ' + (val === 'right' ? 'Direita' : (val === 'center' ? 'Centro' : 'Esquerda')));
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
                   '<strong style="font-size:15px; color:#fff;">' + (block.custom_name || bMeta.name) + '</strong>' +
                   '</div>' +
                   '<span style="font-size:10px; background:rgba(56,189,248,0.15); color:var(--primary); padding:2px 8px; border-radius:10px; font-weight:700;">' + (sec.title || 'Seção') + ' / Célula ' + (selectedBlockCoord.col + 1) + '</span>' +
                   '</div>';

        html += '<div class="inspector-field" style="background:rgba(255,255,255,0.03); padding:8px 10px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); margin-bottom:14px;">' +
                '<label class="inspector-label" style="color:var(--primary);">🏷️ Rótulo Personalizado na Layer (Camadas)</label>' +
                '<input type="text" class="inspector-input" value="' + escapeAttr(block.custom_name || '') + '" oninput="updateBlockCustomName(this.value)" placeholder="ex: ' + escapeAttr(bMeta.name) + ' Principal">' +
                '<span style="font-size:10px; color:var(--text-muted); display:block; margin-top:3px;">Renomeie este elemento para identificá-lo facilmente no navegador de camadas.</span>' +
                '</div>';

        if (block.type === 'heading') {
            var normalizedLevel = String(block.data.level || 'h2').toLowerCase().replace(/^([1-6])$/, 'h$1');
            html += inspectorTextInput('Texto do Título', 'text', block.data.text || '');
            html += inspectorSelect('Nível Hierárquico', 'level', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], normalizedLevel);
            html += inspectorFontSelector('Tipografia / Fonte do Título', 'font_family', block.data.font_family || 'var(--font-secondary)');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || block.data.size || '2rem');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'left');
            html += inspectorTokenColorPicker('Cor do Título', 'color', block.data.color || 'var(--color-primary)', 'primary');
        } else if (block.type === 'paragraph') {
            html += inspectorTextarea('Conteúdo do Texto', 'text', block.data.text || '');
            html += inspectorFontSelector('Tipografia / Fonte do Texto', 'font_family', block.data.font_family || 'var(--font-primary)');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || block.data.size || '1rem');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right', 'justify'], block.data.align || 'left');
            html += inspectorTokenColorPicker('Cor do Texto', 'color', block.data.color || 'var(--color-text-main)', 'text_main');
        } else if (block.type === 'button') {
            html += inspectorTextInput('Texto do Botão', 'label', block.data.label || 'Clique Aqui');
            html += inspectorTextInput('Subtexto Auxiliar (Opcional)', 'subtext', block.data.subtext || '');
            
            var actionOpts = [
                { value: 'link', label: '🔗 Link URL (Externo ou Interno)' },
                { value: 'scroll_to', label: '⚓ Rolar até Âncora (#secao)' },
                { value: 'whatsapp', label: '💬 Conversa no WhatsApp' },
                { value: 'tel', label: '📞 Ligação Telefônica' },
                { value: 'email', label: '✉️ Enviar Email' },
                { value: 'modal', label: '🪟 Abrir Janela Modal' },
                { value: 'javascript', label: '⚙️ Executar JavaScript' },
                { value: 'submit', label: '📤 Submeter Formulário' }
            ];
            html += inspectorSelect('Ação do Botão', 'action_type', actionOpts, block.data.action_type || 'link');

            var curAction = block.data.action_type || 'link';
            var urlPlaceholder = 'https://exemplo.com ou /pagina';
            var urlLabel = 'Link de Destino (URL)';
            if (curAction === 'scroll_to') {
                urlLabel = 'ID da Âncora de Destino';
                urlPlaceholder = '#contato ou #sobre';
            } else if (curAction === 'whatsapp') {
                urlLabel = 'Número do WhatsApp (com DDI)';
                urlPlaceholder = '5511999999999';
            } else if (curAction === 'tel') {
                urlLabel = 'Número de Telefone';
                urlPlaceholder = '+55 11 9999-9999';
            } else if (curAction === 'email') {
                urlLabel = 'Endereço de Email';
                urlPlaceholder = 'contato@seusite.com';
            } else if (curAction === 'modal') {
                urlLabel = 'ID do Modal a Abrir';
                urlPlaceholder = 'modal_orcamento';
            } else if (curAction === 'javascript') {
                urlLabel = 'Código JavaScript / Função';
                urlPlaceholder = 'alert("Olá!");';
            }

            if (curAction !== 'submit') {
                html += '<div class="inspector-field">' +
                        '<label class="inspector-label">' + urlLabel + '</label>' +
                        '<input type="text" class="inspector-input" value="' + escapeAttr(block.data.url || '') + '" oninput="updateBlockProp(\'url\', this.value)" placeholder="' + escapeAttr(urlPlaceholder) + '">' +
                        '</div>';
            }

            if (curAction === 'whatsapp') {
                html += inspectorTextInput('Mensagem Inicial do WhatsApp', 'whatsapp_message', block.data.whatsapp_message || '');
            } else if (curAction === 'link') {
                html += inspectorSelect('Destino da Página (Target)', 'target', [
                    { value: '_self', label: 'Mesma Aba / Janela (_self)' },
                    { value: '_blank', label: 'Nova Aba (_blank)' }
                ], block.data.target || '_self');
            }

            html += '<div style="margin: 12px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">🎨 Cores Normais (Estado Inicial)</div>';
            html += inspectorTokenColorPicker('Cor de Fundo do Botão', 'bg_color', block.data.bg_color || 'var(--color-primary)', 'primary');
            html += inspectorTokenColorPicker('Cor do Texto do Botão', 'text_color', block.data.text_color || '#0f172a', 'background');

            html += '<div style="margin: 14px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">✨ Efeito & Cores ao Passar o Mouse (Hover)</div>';
            html += inspectorHoverAnimation('Animação no Hover (Micro-interação)', 'hover_animation', block.data.hover_animation || 'lift');
            html += inspectorTokenColorPicker('Cor de Fundo no Hover', 'hover_bg_color', block.data.hover_bg_color || '', '');
            html += inspectorTokenColorPicker('Cor do Texto no Hover', 'hover_text_color', block.data.hover_text_color || '', '');

            html += '<div style="margin: 14px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">📐 Tipografia & Dimensões</div>';
            html += inspectorFontSelector('Tipografia do Botão', 'font_family', block.data.font_family || 'var(--font-primary)');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || '1rem');
            html += inspectorDimension('Padding Interno', 'padding', block.data.padding || '12px 24px');
            html += inspectorDimension('Arredondamento de Borda', 'border_radius', block.data.border_radius || '8px');
            html += inspectorSelect('Alinhamento', 'align', [
                { value: 'left', label: 'Esquerda' },
                { value: 'center', label: 'Centro' },
                { value: 'right', label: 'Direita' },
                { value: 'justify', label: 'Largura Total (100%)' }
            ], block.data.align || 'left');
            html += inspectorTextInput('Ícone (Emoji ou Símbolo)', 'icon', block.data.icon || '');
            if (block.data.icon) {
                html += inspectorSelect('Posição do Ícone', 'icon_position', [
                    { value: 'left', label: 'Antes do Texto (Esquerda)' },
                    { value: 'right', label: 'Depois do Texto (Direita)' }
                ], block.data.icon_position || 'left');
            }
        } else if (block.type === 'cta_button') {
            html += inspectorTextInput('Texto do Botão', 'label', block.data.label || 'Clique Aqui');
            html += inspectorTextInput('Link / URL de Destino', 'url', block.data.url || '#');
            html += inspectorSelect('Variante Estilizada', 'variant', ['primary', 'secondary', 'outline', 'glow'], block.data.variant || 'primary');

            html += '<div style="margin: 12px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">🎨 Cores Normais (Estado Inicial)</div>';
            html += inspectorTokenColorPicker('Cor de Fundo do Botão', 'bg_color', block.data.bg_color || 'var(--color-primary)', 'primary');
            html += inspectorTokenColorPicker('Cor do Texto do Botão', 'text_color', block.data.text_color || '#0f172a', 'background');

            html += '<div style="margin: 14px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">✨ Efeito & Cores ao Passar o Mouse (Hover)</div>';
            html += inspectorHoverAnimation('Animação no Hover (Micro-interação)', 'hover_animation', block.data.hover_animation || 'lift');
            html += inspectorTokenColorPicker('Cor de Fundo no Hover', 'hover_bg_color', block.data.hover_bg_color || '', '');
            html += inspectorTokenColorPicker('Cor do Texto no Hover', 'hover_text_color', block.data.hover_text_color || '', '');

            html += '<div style="margin: 14px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;">📐 Dimensões & Alinhamento</div>';
            html += inspectorDimension('Padding Interno', 'padding', block.data.padding || '12px 24px');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'left');
        } else if (block.type === 'user_auth') {
            html += inspectorTextInput('Texto do Botão Login', 'login_text', block.data.login_text || 'Entrar');
            html += inspectorTextInput('Link de Login', 'login_url', block.data.login_url || '/aluno/login');
            html += inspectorTextInput('Texto do Botão Cadastrar', 'register_text', block.data.register_text || 'Cadastrar');
            html += inspectorTextInput('Link de Cadastro', 'register_url', block.data.register_url || '/aluno/cadastro');
            html += inspectorTextInput('Texto da Área do Aluno (Logado)', 'dashboard_text', block.data.dashboard_text || '🎓 Área do Aluno');
            html += inspectorTextInput('Link do Painel do Aluno', 'dashboard_url', block.data.dashboard_url || '/aluno/painel');
            html += inspectorTextInput('Texto de Logout', 'logout_text', block.data.logout_text || 'Sair');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'right');
        } else if (block.type === 'logo') {
            html += inspectorTextInput('Texto do Logotipo', 'text', block.data.text || block.data.brand_text || 'DomainSystem');
            html += inspectorTokenColorPicker('Cor do Texto da Marca', 'text_color', block.data.text_color || block.data.color || 'var(--color-text-main)', 'text_main');
            html += inspectorFontSelector('Tipografia da Marca', 'font_family', block.data.font_family || 'var(--font-primary)');
            html += inspectorDimension('Tamanho da Fonte da Marca', 'font_size', block.data.font_size || '1.4rem');
            html += inspectorSelect('Peso da Fonte', 'font_weight', ['400', '500', '600', '700', '800', '900'], block.data.font_weight || '800');
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
            html += inspectorTextInput('Link de Destino', 'link', block.data.link || block.data.url || '/');
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
            html += inspectorShortcodeSelector('Selecione o Shortcode Pronto', 'code', block.data.code || block.data.tag || '');
        } else if (block.type === 'nav_menu') {
            html += inspectorMenuSelector('Menu do Sistema', 'menu_id', block.data.menu_id || '');
            html += '<div style="margin:-4px 0 12px 0;">' +
                    '<a href="<?= $baseUrl ?>/admin/themes/menus" target="_blank" style="display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--primary); text-decoration:none; background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.3); padding:5px 10px; border-radius:6px; font-weight:600;">' +
                    '🗂️ Gerenciar Menus &amp; Submenus (Drag &amp; Drop) ↗' +
                    '</a></div>';
            html += inspectorSelect('Alinhamento dos Links (Desktop)', 'align', ['left', 'center', 'right'], block.data.align || 'center');
            html += inspectorSelect('Animação Hover dos Links (Desktop)', 'hover_animation', [
                { value: 'underline', label: '📏 Sublinhado Dinâmico' },
                { value: 'pill_glow', label: '💊 Pílula Iluminada' },
                { value: 'grow', label: '🔍 Escala Suave' },
                { value: 'bracket', label: '🔲 Colchetes Revelados [ ]' },
                { value: 'none', label: '⚪ Sem Animação' }
            ], block.data.hover_animation || 'underline');
            html += inspectorTokenColorPicker('Cor dos Links (Desktop)', 'link_color', block.data.link_color || 'var(--color-text-main)', 'text_main');
            html += inspectorTokenColorPicker('Cor do Link Ativo / Hover', 'active_color', block.data.active_color || 'var(--color-primary)', 'primary');
            html += inspectorFontSelector('Tipografia dos Links', 'font_family', block.data.font_family || 'var(--font-primary)');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || '0.95rem');

            // ==========================================
            // PAINEL 1: BOTÃO HAMBURGUER MOBILE (8 TIPOS + CORES)
            // ==========================================
            var curToggle = block.data.toggle_style || state.mobile_toggle_style || 'classic';
            var curToggleAlign = block.data.toggle_align || block.data.align || state.mobile_toggle_align || 'right';
            html += '<div style="margin-top:16px; padding:12px; border-radius:8px; background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.22);">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">' +
                    '<strong style="font-size:12px; color:var(--primary);">🍔 1. Botão Toggle Hamburguer (768px / 375px)</strong>' +
                    '<button type="button" class="header-btn" onclick="testMobileDrawer(\'' + escapeAttr(curToggle) + '\')" style="font-size:10px; padding:3px 8px; background:rgba(56,189,248,0.2); border-color:var(--primary); color:#fff; font-weight:700;">👁️ Abrir / Fechar Gaveta Mobile</button>' +
                    '</div>';

            html += inspectorSelect('Tipo / Formato do Botão Hamburguer', 'toggle_style', [
                { value: 'classic', label: '☰ Clássico (3 Barras)' },
                { value: 'pill', label: 'MENU ☰ Pílula (Texto + Ícone)' },
                { value: 'minimal', label: '═ Minimalista (2 Linhas Assimétricas)' },
                { value: 'dots', label: '⋮ Pontos (3 Pontos Verticais)' },
                { value: 'boxed', label: '▣ Botão Sólido Preenchido' },
                { value: 'grid', label: '⠿ Bento Grid (9 Pontos Modernos)' },
                { value: 'rounded_glow', label: '◉ Circular Neon Glow (Redondo)' },
                { value: 'cyber', label: '[☰] Tech Cyber (Colchetes)' }
            ], curToggle);

            if (curToggle === 'pill') {
                html += inspectorTextInput('Texto do Botão Pílula', 'toggle_label', block.data.toggle_label || 'MENU');
            }

            html += inspectorSelect('Posição do Botão Hamburguer (Mobile)', 'toggle_align', [
                { value: 'right', label: '➡️ Alinhar à Direita (Padrão)' },
                { value: 'center', label: '🎯 Centralizar no Topo' },
                { value: 'left', label: '⬅️ Alinhar à Esquerda' }
            ], curToggleAlign);

            html += inspectorTokenColorPicker('Cor do Ícone / Texto do Toggle', 'toggle_color', block.data.toggle_color || 'var(--color-primary)', 'primary');
            html += inspectorTokenColorPicker('Cor de Fundo do Toggle', 'toggle_bg_color', block.data.toggle_bg_color || '', 'transparent');
            html += inspectorTokenColorPicker('Cor da Borda do Toggle', 'toggle_border_color', block.data.toggle_border_color || 'var(--color-border)', 'border');
            html += inspectorTokenColorPicker('Cor do Ícone no Hover (Toggle)', 'toggle_hover_color', block.data.toggle_hover_color || 'var(--color-primary)', 'primary');
            html += inspectorTokenColorPicker('Cor de Fundo no Hover (Toggle)', 'toggle_hover_bg_color', block.data.toggle_hover_bg_color || 'rgba(56, 189, 248, 0.18)', 'primary');
            html += '</div>';

            // ==========================================
            // PAINEL 2: PERSONALIZAÇÃO DA JANELA LATERAL (DRAWER MOBILE)
            // ==========================================
            html += '<div style="margin-top:14px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1);">' +
                    '<div style="font-size:12px; font-weight:700; color:#fff; margin-bottom:10px;">📱 2. Janela Lateral Mobile (Gaveta / Drawer)</div>';

            html += inspectorTextInput('Título do Cabeçalho da Janela', 'drawer_title', block.data.drawer_title !== undefined ? block.data.drawer_title : 'Navegação');
            html += inspectorTokenColorPicker('Cor do Título da Janela', 'drawer_title_color', block.data.drawer_title_color || 'var(--color-primary)', 'primary');
            html += inspectorSelect('Modo de Abertura / Posição da Janela', 'drawer_position', [
                { value: 'right', label: '⬅️ Deslizar da Direita (Slide Right)' },
                { value: 'left', label: '➡️ Deslizar da Esquerda (Slide Left)' },
                { value: 'top', label: '⬇️ Cortina Superior (Dropdown Top)' },
                { value: 'fullscreen', label: '🖥️ Modal Tela Cheia (Fullscreen)' }
            ], block.data.drawer_position || (curToggleAlign === 'left' ? 'left' : 'right'));
            html += inspectorDimension('Largura Máxima da Janela', 'drawer_width', block.data.drawer_width || '320px');
            html += inspectorTokenColorPicker('Cor de Fundo da Janela Mobile', 'drawer_bg', block.data.drawer_bg || 'var(--color-surface)', 'surface');
            html += inspectorTokenColorPicker('Cor da Borda / Divisória da Janela', 'drawer_border_color', block.data.drawer_border_color || 'var(--color-border)', 'border');
            html += '</div>';

            // ==========================================
            // PAINEL 3: BOTÕES DE MENU DENTRO DA JANELA MOBILE (FORMATOS & HOVER)
            // ==========================================
            html += '<div style="margin-top:14px; padding:12px; border-radius:8px; background:rgba(56,189,248,0.04); border:1px solid rgba(56,189,248,0.18);">' +
                    '<div style="font-size:12px; font-weight:700; color:var(--primary); margin-bottom:10px;">🔘 3. Botões dos Menus Mobile (Formatos & Cores Hover)</div>';

            html += inspectorSelect('Formato / Tipo dos Botões de Menu Mobile', 'mobile_item_style', [
                { value: 'card', label: '🃏 Card Suave (Fundo + Borda Arredondada)' },
                { value: 'pill', label: '💊 Botão Pílula (100% Arredondado)' },
                { value: 'filled', label: '🟦 Botão Sólido Preenchido (Destaque)' },
                { value: 'outlined', label: '🔲 Botão com Borda Definida (Outline)' },
                { value: 'cyber_bar', label: '⚡ Barra Lateral Neon (Cyber Accent)' },
                { value: 'divider', label: '➖ Lista com Linha Divisória Inferior' },
                { value: 'minimal', label: '✨ Link Minimalista (Sem Caixa)' }
            ], block.data.mobile_item_style || 'card');

            html += inspectorSelect('Alinhamento do Texto nos Botões Mobile', 'mobile_item_align', [
                { value: 'left', label: '⬅️ Alinhado à Esquerda' },
                { value: 'center', label: '🎯 Centralizado no Botão' },
                { value: 'right', label: '➡️ Alinhado à Direita' }
            ], block.data.mobile_item_align || 'left');

            html += inspectorSelect('Efeito ao Passar o Mouse / Toque (Hover Mobile)', 'mobile_hover_effect', [
                { value: 'slide_right', label: '➡️ Deslocar Suavemente à Direita' },
                { value: 'scale', label: '🔍 Crescer Escala (Scale Up)' },
                { value: 'glow', label: '✨ Brilho Neon na Borda (Glow)' },
                { value: 'fill', label: '🎨 Apenas Transição de Cor / Fundo' },
                { value: 'none', label: '⚪ Sem Movimento' }
            ], block.data.mobile_hover_effect || 'slide_right');

            html += inspectorDimension('Espaçamento Entre Botões (Gap)', 'mobile_item_gap', block.data.mobile_item_gap || '8px');
            html += inspectorTextInput('Padding Interno dos Botões Mobile', 'mobile_item_padding', block.data.mobile_item_padding || '12px 16px');
            html += inspectorDimension('Arredondamento dos Botões (Radius)', 'mobile_item_radius', block.data.mobile_item_radius || '8px');

            html += '<div style="margin-top:8px; padding-top:8px; border-top:1px dashed rgba(255,255,255,0.1);">' +
                    '<div style="font-size:11px; font-weight:700; color:#cbd5e1; margin-bottom:6px;">🎨 Cores Normais dos Botões Mobile</div>' +
                    inspectorTokenColorPicker('Cor do Texto (Normal)', 'mobile_link_color', block.data.mobile_link_color || block.data.link_color || 'var(--color-text-main)', 'text_main') +
                    inspectorTokenColorPicker('Cor de Fundo do Botão (Normal)', 'mobile_item_bg', block.data.mobile_item_bg || 'rgba(255, 255, 255, 0.04)', 'surface') +
                    inspectorTokenColorPicker('Cor da Borda do Botão (Normal)', 'mobile_item_border_color', block.data.mobile_item_border_color || 'rgba(255, 255, 255, 0.07)', 'border') +
                    '</div>';

            html += '<div style="margin-top:8px; padding-top:8px; border-top:1px dashed rgba(56,189,248,0.25);">' +
                    '<div style="font-size:11px; font-weight:700; color:var(--primary); margin-bottom:6px;">✨ Cores Hover / Ativo dos Botões Mobile</div>' +
                    inspectorTokenColorPicker('Cor do Texto no Hover / Ativo', 'mobile_hover_text_color', block.data.mobile_hover_text_color || block.data.active_color || 'var(--color-primary)', 'primary') +
                    inspectorTokenColorPicker('Cor de Fundo no Hover / Ativo', 'mobile_hover_bg_color', block.data.mobile_hover_bg_color || 'rgba(56, 189, 248, 0.14)', 'primary') +
                    inspectorTokenColorPicker('Cor da Borda / Brilho no Hover', 'mobile_hover_border_color', block.data.mobile_hover_border_color || 'var(--color-primary)', 'primary') +
                    '</div>';

            html += '</div>';

            // ==========================================
            // PAINEL 4: SUBMENUS DROPDOWN (DESKTOP & MOBILE)
            // ==========================================
            html += '<div style="margin-top:14px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1);">' +
                    '<div style="font-size:12px; font-weight:700; color:#fff; margin-bottom:8px;">🗂️ 4. Submenus Dropdown (Hierarquia Desktop &amp; Mobile)</div>' +
                    '<p style="font-size:10px; color:var(--text-muted); margin:0 0 10px 0; line-height:1.4;">Personalize o card flutuante dos submenus criados no Gerenciador de Menus (arrastando itens para a direita).</p>';

            html += inspectorTokenColorPicker('Cor de Fundo do Card Submenu', 'submenu_bg', block.data.submenu_bg || 'var(--color-surface)', 'surface');
            html += inspectorTokenColorPicker('Cor da Borda do Card Submenu', 'submenu_border_color', block.data.submenu_border_color || 'var(--color-border)', 'border');
            html += inspectorTokenColorPicker('Cor do Texto dos Submenus', 'submenu_text_color', block.data.submenu_text_color || block.data.link_color || 'var(--color-text-main)', 'text_main');
            html += inspectorTokenColorPicker('Cor do Texto no Hover (Submenu)', 'submenu_hover_text_color', block.data.submenu_hover_text_color || block.data.active_color || 'var(--color-primary)', 'primary');
            html += inspectorTokenColorPicker('Cor de Fundo no Hover (Submenu)', 'submenu_hover_bg_color', block.data.submenu_hover_bg_color || 'rgba(56, 189, 248, 0.12)', 'primary');
            html += inspectorDimension('Largura Mínima do Submenu', 'submenu_min_width', block.data.submenu_min_width || '220px');
            html += inspectorDimension('Arredondamento do Card Submenu', 'submenu_radius', block.data.submenu_radius || '12px');
            html += '</div>';
        } else if (block.type === 'divider') {
            html += inspectorSelect('Estilo', 'style', ['solid', 'dashed', 'gradient', 'spacer', 'transparent'], block.data.style || 'solid');
            html += inspectorDimension('Altura / Espaçamento', 'height', block.data.height || '20px');
            html += inspectorTokenColorPicker('Cor da Linha', 'color', block.data.color || 'var(--color-border)', 'border');
        } else if (block.type === 'copyright') {
            html += inspectorTextInput('Nome do Site', 'site_name', block.data.site_name || 'Domain-System');
            html += inspectorTextInput('Texto Complementar', 'text', block.data.text || '');
            html += inspectorCheckbox('Exibir Links LGPD (Sobre, Privacidade, Termos)', 'show_lgpd_links', block.data.show_lgpd_links !== false);
            html += inspectorTokenColorPicker('Cor do Texto do Rodapé', 'text_color', block.data.text_color || block.data.color || 'var(--color-text-muted)', 'text_muted');
            html += inspectorTokenColorPicker('Cor dos Links LGPD', 'link_color', block.data.link_color || 'var(--color-text-muted)', 'text_muted');
            html += inspectorFontSelector('Tipografia do Copyright', 'font_family', block.data.font_family || 'var(--font-primary)');
            html += inspectorDimension('Tamanho da Fonte', 'font_size', block.data.font_size || '0.88rem');
            html += inspectorSelect('Alinhamento', 'align', ['left', 'center', 'right'], block.data.align || 'center');
        } else if (block.type === 'custom_code') {
            html += inspectorTextarea('Código HTML / CSS / JS Customizado', 'code', block.data.code || block.data.html || '');
        } else if (block.type === 'social_icons') {
            html += inspectorSelect('Formato da Silhueta', 'shape', [
                { value: 'circle', label: '🔘 Círculo (50%)' },
                { value: 'rounded', label: '🔲 Cantos Arredondados (10px)' },
                { value: 'square', label: '⬛ Quadrado (0px)' },
                { value: 'minimal', label: '✨ Minimalista (Sem Fundo)' }
            ], block.data.shape || 'circle');

            html += inspectorSelect('Modo de Cores', 'color_mode', [
                { value: 'official', label: '🎨 Cores Oficiais das Marcas' },
                { value: 'custom', label: '⚙️ Cores Personalizadas (Tokens)' }
            ], block.data.color_mode || 'official');

            if (block.data.color_mode === 'custom') {
                html += inspectorTokenColorPicker('Cor dos Ícones', 'icon_color', block.data.icon_color || '#ffffff', 'background');
                if (block.data.shape !== 'minimal') {
                    html += inspectorTokenColorPicker('Cor de Fundo dos Botões', 'bg_color', block.data.bg_color || 'var(--color-surface)', 'surface');
                }
            }

            html += inspectorHoverAnimation('Efeito ao Passar o Mouse (Hover)', 'hover_animation', block.data.hover_animation || 'lift');
            html += inspectorTokenColorPicker('Cor de Fundo no Hover', 'hover_bg_color', block.data.hover_bg_color || '', '');
            html += inspectorTokenColorPicker('Cor do Texto no Hover', 'hover_text_color', block.data.hover_text_color || '', '');

            html += inspectorSelect('Alinhamento', 'align', [
                { value: 'left', label: '⬅️ Esquerda' },
                { value: 'center', label: '🎯 Centro' },
                { value: 'right', label: '➡️ Direita' }
            ], block.data.align || 'center');

            html += inspectorDimension('Tamanho do Ícone', 'icon_size', block.data.icon_size || '20px');
            html += inspectorDimension('Tamanho do Botão', 'button_size', block.data.button_size || '40px');
            html += inspectorDimension('Espaçamento Entre Ícones (Gap)', 'gap', block.data.gap || '12px');

            html += inspectorSocialIconsRepeater(block);
        } else if (block.type === "post_cards_grid") {
            html += inspectorSelect("Colunas no Desktop", "columns", [
                { value: "1", label: "1 Coluna (Lista Cheia)" },
                { value: "2", label: "2 Colunas (50% / 50%)" },
                { value: "3", label: "3 Colunas (Padrão Blog)" },
                { value: "4", label: "4 Colunas (Grid Compacto)" }
            ], String(block.data.columns || "3"));

            html += inspectorInput("Limite de Artigos", "limit", block.data.limit || 6, "number");

            html += inspectorSelect("Estilo dos Cards", "card_style", [
                { value: "cyber_glass", label: "Cyber Glass (Glassmorphism & Glow)" },
                { value: "minimal", label: "Minimalista (Clean & Tipográfico)" },
                { value: "bordered", label: "Bordas Definidas (Contraste Token)" },
                { value: "flat", label: "Flat Surface (Preenchimento Sólido)" }
            ], block.data.card_style || "cyber_glass");

            html += inspectorSelect("Efeito ao Passar o Mouse (Hover)", "hover_effect", [
                { value: "lift", label: "Elevação 3D (Lift suave)" },
                { value: "glow", label: "Brilho Neon (Glow Primário)" },
                { value: "border_accent", label: "Realce de Borda (Color Accent)" },
                { value: "none", label: "Sem Animação" }
            ], block.data.hover_effect || "lift");

            html += inspectorDimension("Espaçamento do Grid (Gap)", "gap", block.data.gap || "24px");

            html += inspectorCheckbox("Exibir Imagem Destacada", "show_image", block.data.show_image !== false);
            html += inspectorCheckbox("Exibir Data de Publicação", "show_date", block.data.show_date !== false);
            html += inspectorCheckbox("Exibir Autor", "show_author", block.data.show_author !== false);
            html += inspectorCheckbox("Exibir Resumo (Excerto)", "show_excerpt", block.data.show_excerpt !== false);
            html += inspectorCheckbox("Exibir Botão \"Ler Artigo\"", "show_read_more", block.data.show_read_more !== false);
            html += inspectorInput("Texto do Link", "read_more_text", block.data.read_more_text || "Ler Artigo →");
            html += inspectorTokenColorPicker("Cor dos Títulos dos Cards", "title_color", block.data.title_color || "var(--color-primary)", "primary");
            html += inspectorTokenColorPicker("Cor do Resumo (Texto)", "excerpt_color", block.data.excerpt_color || "var(--color-text-main)", "text_main");
            html += inspectorTokenColorPicker("Cor da Data / Autor", "meta_color", block.data.meta_color || "var(--color-text-muted)", "text_muted");
            html += inspectorTokenColorPicker("Cor do Link 'Ler Artigo'", "readmore_color", block.data.readmore_color || "var(--color-primary)", "primary");
            html += inspectorTokenColorPicker("Cor de Fundo da Categoria (Badge)", "badge_bg_color", block.data.badge_bg_color || "var(--color-primary)", "primary");
            html += inspectorTokenColorPicker("Cor do Texto da Categoria (Badge)", "badge_text_color", block.data.badge_text_color || "#0b0c10", "background");
        }

        // ==========================================
        // ESTILOS CSS AVANÇADOS DO ELEMENTO (MARGIN, PADDING, FLEX, FLEXBOX, ETC.)
        // ==========================================
        var elemMarginVals = parse4Values(block.data.margin || '0px');
        var elemPaddingVals = parse4Values(block.data.padding || '0px');
        var isMarginLinked = (elemSpacingLinkState.margin !== undefined) ? elemSpacingLinkState.margin : true;
        var isPaddingLinked = (elemSpacingLinkState.padding !== undefined) ? elemSpacingLinkState.padding : true;

        html += '<div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 14px;">' +
                '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">' +
                '<div style="font-size:12px; font-weight:700; color:#fff;">🎨 Estilos CSS do Elemento</div>' +
                '<button type="button" class="header-btn" onclick="resetElementStyles()" title="Redefinir estilos CSS deste elemento" style="font-size:10px; color:#ef4444; border-color:rgba(239,68,68,0.3); padding:1px 6px;">↺ Reset</button>' +
                '</div>';

        // MARGEM EXTERNA (4 LADOS COM STEPPERS)
        html += '<div class="spacing-box-control">' +
                '<div class="spacing-box-header">' +
                '<span class="spacing-box-title">Margem Externa (Margin)</span>' +
                '<button type="button" class="spacing-link-btn ' + (isMarginLinked ? '' : 'unlinked') + '" onclick="toggleElemSpacingLink(\'margin\')">' + (isMarginLinked ? '🔗 Vinculado' : '🔓 Separado') + '</button>' +
                '</div>' +
                '<div class="spacing-quad-grid">' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Cima</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 0, -2)">-</button><input type="number" class="step-input" value="' + elemMarginVals[0] + '" oninput="onElemQuadInputChange(\'margin\', 0, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 0, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Direita</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 1, -2)">-</button><input type="number" class="step-input" value="' + elemMarginVals[1] + '" oninput="onElemQuadInputChange(\'margin\', 1, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 1, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Baixo</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 2, -2)">-</button><input type="number" class="step-input" value="' + elemMarginVals[2] + '" oninput="onElemQuadInputChange(\'margin\', 2, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 2, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Esquerda</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 3, -2)">-</button><input type="number" class="step-input" value="' + elemMarginVals[3] + '" oninput="onElemQuadInputChange(\'margin\', 3, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'margin\', 3, 2)">+</button></div></div>' +
                '</div></div>';

        // PREENCHIMENTO INTERNO (4 LADOS COM STEPPERS)
        html += '<div class="spacing-box-control">' +
                '<div class="spacing-box-header">' +
                '<span class="spacing-box-title">Preenchimento Interno (Padding)</span>' +
                '<button type="button" class="spacing-link-btn ' + (isPaddingLinked ? '' : 'unlinked') + '" onclick="toggleElemSpacingLink(\'padding\')">' + (isPaddingLinked ? '🔗 Vinculado' : '🔓 Separado') + '</button>' +
                '</div>' +
                '<div class="spacing-quad-grid">' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Cima</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 0, -2)">-</button><input type="number" class="step-input" value="' + elemPaddingVals[0] + '" oninput="onElemQuadInputChange(\'padding\', 0, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 0, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Direita</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 1, -2)">-</button><input type="number" class="step-input" value="' + elemPaddingVals[1] + '" oninput="onElemQuadInputChange(\'padding\', 1, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 1, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Baixo</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 2, -2)">-</button><input type="number" class="step-input" value="' + elemPaddingVals[2] + '" oninput="onElemQuadInputChange(\'padding\', 2, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 2, 2)">+</button></div></div>' +
                '<div class="spacing-side-col"><span class="spacing-side-label">Esquerda</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 3, -2)">-</button><input type="number" class="step-input" value="' + elemPaddingVals[3] + '" oninput="onElemQuadInputChange(\'padding\', 3, this.value)"><button type="button" class="step-btn" onclick="stepElemQuadValue(\'padding\', 3, 2)">+</button></div></div>' +
                '</div></div>';

        // FLEX & FLEXBOX
        html += '<div class="inspector-field">' +
                '<label class="inspector-label">Comportamento de Exibição (Display)</label>' +
                '<select class="inspector-select" onchange="updateBlockProp(\'display\', this.value); renderCanvas(); renderInspector();">' +
                '<option value="" ' + (!block.data.display ? 'selected' : '') + '>Padrão do Elemento</option>' +
                '<option value="flex" ' + (block.data.display === 'flex' ? 'selected' : '') + '>Flexbox Container (display: flex)</option>' +
                '<option value="inline-flex" ' + (block.data.display === 'inline-flex' ? 'selected' : '') + '>Inline Flex (display: inline-flex)</option>' +
                '<option value="block" ' + (block.data.display === 'block' ? 'selected' : '') + '>Bloco (display: block)</option>' +
                '<option value="inline-block" ' + (block.data.display === 'inline-block' ? 'selected' : '') + '>Inline Block (display: inline-block)</option>' +
                '</select>' +
                '</div>';

        if (block.data.display === 'flex' || block.data.display === 'inline-flex') {
            html += '<div style="background:rgba(56,189,248,0.05); border:1px solid rgba(56,189,248,0.2); border-radius:6px; padding:10px; margin-bottom:12px;">' +
                    '<div style="font-size:11px; font-weight:700; color:var(--primary); margin-bottom:8px;">Propriedades Flexbox</div>' +
                    inspectorSelect('Direção dos Itens (Flex Direction)', 'flex_direction', ['row', 'column', 'row-reverse', 'column-reverse'], block.data.flex_direction || 'row') +
                    inspectorSelect('Alinhamento Principal (Justify Content)', 'justify_content', ['flex-start', 'center', 'flex-end', 'space-between', 'space-around'], block.data.justify_content || 'flex-start') +
                    inspectorSelect('Alinhamento Transversal (Align Items)', 'align_items', ['stretch', 'center', 'flex-start', 'flex-end'], block.data.align_items || 'stretch') +
                    inspectorSelect('Quebra de Linha (Flex Wrap)', 'flex_wrap', ['nowrap', 'wrap'], block.data.flex_wrap || 'nowrap') +
                    '<div class="inspector-field">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                    '<label class="inspector-label" style="margin-bottom:0;">Espaçamento Entre Itens (Gap)</label>' +
                    '<span style="font-size:11px; color:var(--primary); font-weight:700;">' + (block.data.gap || '0px') + '</span>' +
                    '</div>' +
                    '<div class="stepper-single-wrap">' +
                    '<button type="button" class="step-btn" onclick="stepElemSingleValue(\'gap\', -2, 0, 100)">-</button>' +
                    '<input type="number" id="elem_gap_num" class="step-input-single" value="' + (parseFloat(block.data.gap || '0') || 0) + '" min="0" max="100" oninput="onElemSingleNumChange(\'gap\', this.value)">' +
                    '<button type="button" class="step-btn" onclick="stepElemSingleValue(\'gap\', 2, 0, 100)">+</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
        }

        // DIMENSÕES & VISUAL COM STEPPERS E PRESETS
        html += renderElemDimensionWithSteppers('Largura do Elemento (Width)', 'width', block.data.width || '', '%', [
            { label: 'Auto', value: 'auto' },
            { label: '25%', value: '25%' },
            { label: '50%', value: '50%' },
            { label: '75%', value: '75%' },
            { label: '100%', value: '100%' }
        ], 0, 2560, 10);

        html += renderElemDimensionWithSteppers('Largura Máxima (Max-Width)', 'max_width', block.data.max_width || '', 'px', [
            { label: 'Nenhum', value: 'none' },
            { label: '480px', value: '480px' },
            { label: '768px', value: '768px' },
            { label: '1024px', value: '1024px' },
            { label: '1200px', value: '1200px' },
            { label: '100%', value: '100%' }
        ], 0, 3840, 20);

        html += renderAdvancedBackgroundControl('Cor de Fundo do Elemento (Background)', 'background', block.data.background || 'transparent', 'transparent', false);

        // CORES DE TEXTO E FORMATAÇÃO TIPOGRÁFICA UNIVERSAL DO ELEMENTO (SOLID - StylableElementInterface)
        html += '<div style="margin-top:12px; margin-bottom:12px; background:rgba(15,23,42,0.45); padding:10px; border-radius:8px; border:1px solid rgba(56,189,248,0.2);">' +
                '<div style="font-size:11px; font-weight:700; color:var(--primary); margin-bottom:8px;">🔤 Formatação & Cores de Texto do Elemento</div>' +
                inspectorTokenColorPicker('Cor do Texto Geral (Text Color)', 'text_color', block.data.text_color || block.data.color || '', 'text_main') +
                inspectorTokenColorPicker('Cor dos Títulos Internos (Heading Color)', 'heading_color', block.data.heading_color || '', 'primary') +
                inspectorTokenColorPicker('Cor dos Links Internos (Link Color)', 'link_color', block.data.link_color || '', 'primary') +
                inspectorTokenColorPicker('Cor dos Links no Hover (Link Hover)', 'link_hover_color', block.data.link_hover_color || '', 'tertiary') +
                inspectorFontSelector('Família da Fonte (Font Family)', 'font_family', block.data.font_family || 'var(--font-primary)') +
                '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                inspectorDimension('Tamanho (Font Size)', 'font_size', block.data.font_size || block.data.size || '') +
                inspectorSelect('Peso (Font Weight)', 'font_weight', [
                    { value: '', label: 'Padrão' },
                    { value: '300', label: '300 (Light)' },
                    { value: '400', label: '400 (Regular)' },
                    { value: '500', label: '500 (Medium)' },
                    { value: '600', label: '600 (SemiBold)' },
                    { value: '700', label: '700 (Bold)' },
                    { value: '800', label: '800 (ExtraBold)' },
                    { value: '900', label: '900 (Black)' }
                ], block.data.font_weight || '') +
                '</div>' +
                '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                inspectorTextInput('Altura de Linha (Line Height)', 'line_height', block.data.line_height || '') +
                inspectorDimension('Espaçamento Letras', 'letter_spacing', block.data.letter_spacing || '') +
                '</div>' +
                '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                inspectorSelect('Alinhamento Texto', 'text_align', [
                    { value: '', label: 'Padrão' },
                    { value: 'left', label: 'Esquerda' },
                    { value: 'center', label: 'Centro' },
                    { value: 'right', label: 'Direita' },
                    { value: 'justify', label: 'Justificado' }
                ], block.data.text_align || block.data.align || '') +
                inspectorSelect('Transformação', 'text_transform', [
                    { value: '', label: 'Nenhuma' },
                    { value: 'uppercase', label: 'MAIÚSCULAS' },
                    { value: 'lowercase', label: 'minúsculas' },
                    { value: 'capitalize', label: 'Capitalizar' }
                ], block.data.text_transform || '') +
                '</div>' +
                '</div>';

        // BORDER RADIUS COM STEPPER
        var curRadiusNum = parseFloat(block.data.border_radius || '0') || 0;
        html += '<div class="inspector-field">' +
                '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                '<label class="inspector-label" style="margin-bottom:0;">Arredondamento de Borda (Border Radius)</label>' +
                '<span style="font-size:11px; color:var(--primary); font-weight:700;">' + (block.data.border_radius || '0px') + '</span>' +
                '</div>' +
                '<div class="stepper-single-wrap">' +
                '<button type="button" class="step-btn" onclick="stepElemSingleValue(\'border_radius\', -2, 0, 100)">-</button>' +
                '<input type="number" id="elem_border_radius_num" class="step-input-single" value="' + curRadiusNum + '" min="0" max="100" oninput="onElemSingleNumChange(\'border_radius\', this.value)">' +
                '<button type="button" class="step-btn" onclick="stepElemSingleValue(\'border_radius\', 2, 0, 100)">+</button>' +
                '</div>' +
                '</div>';

        // BORDA COMPLETA COM TIPO, 4 LADOS STEPPERS E DESIGN TOKENS
        html += renderElemBorderControl(block);

        // SOMBRA DA CAIXA (BOX SHADOW) COM PRESETS VISUAIS
        html += renderElemBoxShadowControl(block);

        // OPACIDADE COM SLIDER E STEPPERS
        html += renderElemOpacityControl(block);

        // VISIBILIDADE RESPONSIVA DO ELEMENTO (4 RESOLUÇÕES: DESKTOP, 1024px, 768px, 375px)
        html += '<div class="inspector-field" style="margin-top:14px; background:rgba(56,189,248,0.05); border:1px solid rgba(56,189,248,0.2); border-radius:6px; padding:10px;">' +
                '<label class="inspector-label" style="color:var(--primary); font-weight:700; margin-bottom:4px;">📱 Visibilidade Responsiva do Elemento (4 Resoluções)</label>' +
                '<div style="font-size:10px; color:var(--text-muted); margin-bottom:8px; line-height:1.35;">Oculta <strong>apenas este elemento (' + escapeHtml(block.custom_name || bMeta.name) + ')</strong> nas resoluções marcadas, mantendo os demais elementos da seção visíveis:</div>' +
                inspectorCheckbox('🖥️ Ocultar no Desktop (100%)', 'hide_desktop', !!block.data.hide_desktop) +
                inspectorCheckbox('💻 Ocultar em 1024px (Laptop)', 'hide_laptop', !!(block.data.hide_laptop || block.data.hide_1024)) +
                inspectorCheckbox('📱 Ocultar em 768px (Tablet)', 'hide_tablet', !!(block.data.hide_tablet || block.data.hide_768)) +
                inspectorCheckbox('📲 Ocultar em 375px (Mobile)', 'hide_mobile', !!(block.data.hide_mobile || block.data.hide_375)) +
                '</div>';

        html += '</div>';

        container.innerHTML = html;
    }

    // HELPER FUNCTIONS PARA ESTILOS CSS DOS ELEMENTOS (STEPPERS + RESET)
    var elemSpacingLinkState = {
        margin: true,
        padding: true,
        border: true
    };

    function renderElemDimensionWithSteppers(label, key, curVal, defaultUnit, presets, minVal, maxVal, stepDelta) {
        var valStr = String(curVal || '').trim();
        var num = parseFloat(valStr.replace(/[^0-9.-]/g, ''));
        var isAutoOrNone = (valStr === 'auto' || valStr === 'none' || valStr === '');
        if (isNaN(num)) num = isAutoOrNone ? 0 : 100;
        var unit = valStr.replace(/[0-9.-]/g, '') || defaultUnit;
        if (unit !== 'px' && unit !== '%' && unit !== 'vw') {
            unit = defaultUnit;
        }

        var displayVal = valStr !== '' ? valStr : (isAutoOrNone ? 'auto' : 'padrão');

        var out = '<div class="inspector-field" style="margin-bottom:12px;">' +
                  '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                  '<label class="inspector-label" style="margin-bottom:0;">' + label + '</label>' +
                  '<span style="font-size:11px; color:var(--primary); font-weight:700;">' + escapeHtml(displayVal) + '</span>' +
                  '</div>' +
                  '<div style="display:flex; gap:6px; align-items:center;">' +
                  '<div class="stepper-single-wrap" style="flex:1;">' +
                  '<button type="button" class="step-btn" onclick="stepElemDimValue(\'' + key + '\', -' + stepDelta + ', ' + minVal + ', ' + maxVal + ', \'' + unit + '\')">-</button>' +
                  '<input type="number" class="step-input-single" value="' + (isAutoOrNone ? '' : num) + '" placeholder="auto" min="' + minVal + '" max="' + maxVal + '" oninput="onElemDimNumChange(\'' + key + '\', this.value, \'' + unit + '\')">' +
                  '<button type="button" class="step-btn" onclick="stepElemDimValue(\'' + key + '\', ' + stepDelta + ', ' + minVal + ', ' + maxVal + ', \'' + unit + '\')">+</button>' +
                  '</div>' +
                  '<select class="inspector-select" style="width:70px; padding:6px 4px; font-size:11px;" onchange="changeElemDimUnit(\'' + key + '\', this.value)">' +
                  '<option value="px"' + (unit === 'px' ? ' selected' : '') + '>px</option>' +
                  '<option value="%"' + (unit === '%' ? ' selected' : '') + '>%</option>' +
                  '<option value="vw"' + (unit === 'vw' ? ' selected' : '') + '>vw</option>' +
                  '<option value="auto"' + (valStr === 'auto' ? ' selected' : '') + '>auto</option>' +
                  '</select>' +
                  '</div>';

        if (presets && presets.length > 0) {
            out += '<div style="display:flex; gap:4px; margin-top:5px; flex-wrap:wrap;">';
            presets.forEach(function(p) {
                var isPAct = (valStr === p.value);
                var actStyle = isPAct ? 'background:rgba(56,189,248,0.25); border-color:var(--primary); color:#fff;' : 'background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.1); color:var(--text-muted);';
                out += '<button type="button" class="header-btn" style="font-size:10px; padding:1px 6px; border-radius:3px; ' + actStyle + '" onclick="setElemDimPreset(\'' + key + '\', \'' + p.value + '\')">' + p.label + '</button>';
            });
            out += '</div>';
        }

        out += '</div>';
        return out;
    }

    function stepElemDimValue(key, delta, minVal, maxVal, unit) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var valStr = String(block.data[key] || '').trim();
        var curNum = parseFloat(valStr.replace(/[^0-9.-]/g, ''));
        if (isNaN(curNum)) curNum = (delta > 0 ? minVal : maxVal);
        var next = Math.max(minVal, Math.min(maxVal, curNum + delta));
        block.data[key] = next + unit;
        renderCanvas();
        renderInspector();
    }

    function onElemDimNumChange(key, rawVal, unit) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var trimmed = String(rawVal || '').trim();
        if (trimmed === '' || trimmed === 'auto') {
            block.data[key] = trimmed;
        } else {
            var num = parseFloat(trimmed);
            if (!isNaN(num)) {
                block.data[key] = num + unit;
            }
        }
        renderCanvas();
    }

    function changeElemDimUnit(key, newUnit) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        if (newUnit === 'auto' || newUnit === 'none') {
            block.data[key] = newUnit;
        } else {
            var curNum = parseFloat(String(block.data[key] || '').replace(/[^0-9.-]/g, '')) || (newUnit === '%' ? 100 : 320);
            block.data[key] = curNum + newUnit;
        }
        renderCanvas();
        renderInspector();
    }

    function setElemDimPreset(key, val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data[key] = val;
        renderCanvas();
        renderInspector();
    }

    function renderElemBorderControl(block) {
        var d = block.data || {};
        var bStyle = d.border_style || '';
        if (!bStyle && d.border) {
            if (d.border.indexOf('solid') !== -1) bStyle = 'solid';
            else if (d.border.indexOf('dashed') !== -1) bStyle = 'dashed';
            else if (d.border.indexOf('dotted') !== -1) bStyle = 'dotted';
            else if (d.border.indexOf('double') !== -1) bStyle = 'double';
            else if (d.border.indexOf('groove') !== -1) bStyle = 'groove';
        }

        var bWidthVals = parse4Values(d.border_width || (d.border ? d.border.replace(/[^0-9px ]/g, '').trim() : '0px'));
        var isBorderLinked = (elemSpacingLinkState.border !== undefined) ? elemSpacingLinkState.border : true;
        var bColor = d.border_color || 'var(--color-border)';

        var styleOptions = [
            { value: '', label: 'Nenhuma Borda' },
            { value: 'solid', label: '── Sólida (solid)' },
            { value: 'dashed', label: '┈┈ Tracejada (dashed)' },
            { value: 'dotted', label: '┈ Pontilhada (dotted)' },
            { value: 'double', label: '══ Dupla (double)' },
            { value: 'groove', label: '◫ Entalhada 3D (groove)' }
        ];

        var out = '<div class="inspector-field" style="margin-top:14px; border-top:1px solid rgba(255,255,255,0.06); padding-top:10px;">' +
                  '<label class="inspector-label" style="font-weight:700; color:#fff;">Borda do Elemento (Border)</label>' +
                  '<select class="inspector-select" onchange="setElemBorderStyle(this.value)">' +
                  styleOptions.map(function(opt) {
                      return '<option value="' + escapeAttr(opt.value) + '"' + (bStyle === opt.value ? ' selected' : '') + '>' + escapeHtml(opt.label) + '</option>';
                  }).join('') +
                  '</select>' +
                  '</div>';

        if (bStyle && bStyle !== 'none') {
            out += '<div class="spacing-box-control" style="margin-top:8px;">' +
                   '<div class="spacing-box-header">' +
                   '<span class="spacing-side-label" style="font-weight:700; color:var(--text-main);">Espessura da Borda (Border Width)</span>' +
                   '<button type="button" class="spacing-link-btn ' + (isBorderLinked ? '' : 'unlinked') + '" onclick="toggleElemBorderLink()">' + (isBorderLinked ? '🔗 Vinculado' : '🔓 Separado') + '</button>' +
                   '</div>' +
                   '<div class="spacing-quad-grid">' +
                   '<div class="spacing-side-col"><span class="spacing-side-label">Cima</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemBorderWidth(0, -1)">-</button><input type="number" class="step-input" value="' + bWidthVals[0] + '" min="0" oninput="onElemBorderWidthChange(0, this.value)"><button type="button" class="step-btn" onclick="stepElemBorderWidth(0, 1)">+</button></div></div>' +
                   '<div class="spacing-side-col"><span class="spacing-side-label">Direita</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemBorderWidth(1, -1)">-</button><input type="number" class="step-input" value="' + bWidthVals[1] + '" min="0" oninput="onElemBorderWidthChange(1, this.value)"><button type="button" class="step-btn" onclick="stepElemBorderWidth(1, 1)">+</button></div></div>' +
                   '<div class="spacing-side-col"><span class="spacing-side-label">Baixo</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemBorderWidth(2, -1)">-</button><input type="number" class="step-input" value="' + bWidthVals[2] + '" min="0" oninput="onElemBorderWidthChange(2, this.value)"><button type="button" class="step-btn" onclick="stepElemBorderWidth(2, 1)">+</button></div></div>' +
                   '<div class="spacing-side-col"><span class="spacing-side-label">Esquerda</span><div class="stepper-wrap"><button type="button" class="step-btn" onclick="stepElemBorderWidth(3, -1)">-</button><input type="number" class="step-input" value="' + bWidthVals[3] + '" min="0" oninput="onElemBorderWidthChange(3, this.value)"><button type="button" class="step-btn" onclick="stepElemBorderWidth(3, 1)">+</button></div></div>' +
                   '</div></div>';

            out += inspectorTokenColorPicker('Cor da Borda (Design Tokens)', 'border_color', bColor, 'border');
        }

        return out;
    }

    function toggleElemBorderLink() {
        elemSpacingLinkState.border = !elemSpacingLinkState.border;
        renderInspector();
    }

    function setElemBorderStyle(style) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data.border_style = style;
        compileElemBorder(block);
        renderCanvas();
        renderInspector();
    }

    function stepElemBorderWidth(sideIdx, delta) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var curVals = parse4Values(block.data.border_width || '1px');
        if (elemSpacingLinkState.border) {
            var newVal = Math.max(0, curVals[sideIdx] + delta);
            curVals = [newVal, newVal, newVal, newVal];
        } else {
            curVals[sideIdx] = Math.max(0, curVals[sideIdx] + delta);
        }

        block.data.border_width = curVals[0] + 'px ' + curVals[1] + 'px ' + curVals[2] + 'px ' + curVals[3] + 'px';
        compileElemBorder(block);
        renderCanvas();
        renderInspector();
    }

    function onElemBorderWidthChange(sideIdx, rawVal) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var num = parseFloat(rawVal);
        if (isNaN(num) || num < 0) num = 0;
        var curVals = parse4Values(block.data.border_width || '1px');
        if (elemSpacingLinkState.border) {
            curVals = [num, num, num, num];
        } else {
            curVals[sideIdx] = num;
        }

        block.data.border_width = curVals[0] + 'px ' + curVals[1] + 'px ' + curVals[2] + 'px ' + curVals[3] + 'px';
        compileElemBorder(block);
        renderCanvas();
    }

    function compileElemBorder(block) {
        var style = block.data.border_style || '';
        if (!style || style === 'none') {
            block.data.border = 'none';
            return;
        }
        var width = block.data.border_width || '1px';
        var color = block.data.border_color || 'var(--color-border)';
        var wParts = parse4Values(width);
        if (wParts[0] === wParts[1] && wParts[1] === wParts[2] && wParts[2] === wParts[3]) {
            block.data.border = wParts[0] + 'px ' + style + ' ' + color;
        } else {
            block.data.border = width + ' ' + style + ' ' + color;
        }
    }

    function renderElemBoxShadowControl(block) {
        var curShadow = block.data.box_shadow || '';

        var shadowPresets = [
            { id: '', label: 'Nenhuma', css: '' },
            { id: 'soft', label: 'Suave', css: '0 2px 8px rgba(0,0,0,0.15)' },
            { id: 'medium', label: 'Média', css: '0 6px 18px rgba(0,0,0,0.25)' },
            { id: 'elevated', label: 'Elevada', css: '0 12px 28px rgba(0,0,0,0.38)' },
            { id: 'floating', label: 'Flutuante', css: '0 20px 45px rgba(0,0,0,0.55)' },
            { id: 'neon', label: 'Neon Glow', css: '0 0 20px var(--color-primary, #38bdf8)' },
            { id: 'inset', label: 'Interna', css: 'inset 0 2px 8px rgba(0,0,0,0.4)' }
        ];

        var out = '<div class="inspector-field" style="margin-top:12px;">' +
                  '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">' +
                  '<label class="inspector-label" style="margin-bottom:0; font-weight:700; color:#fff;">Sombra da Caixa (Box Shadow)</label>' +
                  '</div>' +
                  '<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:8px;">';

        shadowPresets.forEach(function(p) {
            var isAct = (curShadow === p.css);
            var actStyle = isAct ? 'background:rgba(56,189,248,0.2); border-color:var(--primary); color:#fff; font-weight:700;' : 'background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08); color:var(--text-muted);';
            out += '<button type="button" class="header-btn" style="padding:6px 4px; font-size:11px; border-radius:4px; text-align:center; ' + actStyle + '" onclick="setElemBoxShadowPreset(\'' + escapeAttr(p.css) + '\')">' + p.label + '</button>';
        });

        out += '</div>' +
               '<input type="text" class="inspector-input" value="' + escapeAttr(curShadow) + '" oninput="updateBlockProp(\'box_shadow\', this.value)" placeholder="ex: 0 4px 12px rgba(0,0,0,0.3)">' +
               '</div>';

        return out;
    }

    function setElemBoxShadowPreset(css) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data.box_shadow = css;
        renderCanvas();
        renderInspector();
    }

    function renderElemOpacityControl(block) {
        var raw = block.data.opacity !== undefined ? block.data.opacity : '';
        var num = parseFloat(raw);
        if (isNaN(num)) num = 1.0;
        var pct = Math.round(num * 100);

        var out = '<div class="inspector-field" style="margin-top:12px;">' +
                  '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                  '<label class="inspector-label" style="margin-bottom:0; font-weight:700; color:#fff;">Opacidade do Elemento (Opacity)</label>' +
                  '<span style="font-size:11px; color:var(--primary); font-weight:700;">' + pct + '% (' + num.toFixed(2) + ')</span>' +
                  '</div>' +
                  '<div style="display:flex; gap:8px; align-items:center;">' +
                  '<div class="stepper-single-wrap" style="width:110px;">' +
                  '<button type="button" class="step-btn" onclick="stepElemOpacity(-0.05)">-</button>' +
                  '<input type="number" step="0.05" min="0.05" max="1.0" class="step-input-single" value="' + num.toFixed(2) + '" oninput="onElemOpacityChange(this.value)">' +
                  '<button type="button" class="step-btn" onclick="stepElemOpacity(0.05)">+</button>' +
                  '</div>' +
                  '<input type="range" min="0.05" max="1.0" step="0.05" value="' + num + '" style="flex:1; accent-color:var(--primary); cursor:pointer;" oninput="onElemOpacityChange(this.value)">' +
                  '</div>' +
                  '<div style="display:flex; gap:4px; margin-top:6px;">' +
                  [1.0, 0.8, 0.5, 0.2].map(function(val) {
                      var isAct = (Math.abs(num - val) < 0.01);
                      var actStyle = isAct ? 'background:rgba(56,189,248,0.25); border-color:var(--primary); color:#fff;' : 'background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08); color:var(--text-muted);';
                      return '<button type="button" class="header-btn" style="font-size:10px; padding:1px 6px; border-radius:3px; flex:1; ' + actStyle + '" onclick="onElemOpacityChange(' + val + ')">' + Math.round(val * 100) + '%</button>';
                  }).join('') +
                  '</div>' +
                  '</div>';

        return out;
    }

    function stepElemOpacity(delta) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        var cur = parseFloat(block.data.opacity !== undefined ? block.data.opacity : '1.0');
        if (isNaN(cur)) cur = 1.0;
        var next = Math.max(0.05, Math.min(1.0, Math.round((cur + delta) * 100) / 100));
        block.data.opacity = String(next);
        renderCanvas();
        renderInspector();
    }

    function onElemOpacityChange(val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        var num = parseFloat(val);
        if (isNaN(num)) num = 1.0;
        num = Math.max(0.05, Math.min(1.0, Math.round(num * 100) / 100));
        block.data.opacity = String(num);
        renderCanvas();
        renderInspector();
    }

    function toggleElemSpacingLink(type) {
        elemSpacingLinkState[type] = !elemSpacingLinkState[type];
        renderInspector();
    }

    function stepElemQuadValue(type, sideIdx, delta) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var curVals = parse4Values(block.data[type] || '0px');
        if (elemSpacingLinkState[type]) {
            var newVal = Math.max(0, curVals[sideIdx] + delta);
            curVals = [newVal, newVal, newVal, newVal];
        } else {
            curVals[sideIdx] = Math.max(0, curVals[sideIdx] + delta);
        }

        var cssVal = curVals[0] + 'px ' + curVals[1] + 'px ' + curVals[2] + 'px ' + curVals[3] + 'px';
        block.data[type] = cssVal;
        renderCanvas();
        renderInspector();
    }

    function onElemQuadInputChange(type, sideIdx, rawVal) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var num = parseFloat(rawVal);
        if (isNaN(num) || num < 0) num = 0;
        var curVals = parse4Values(block.data[type] || '0px');

        if (elemSpacingLinkState[type]) {
            curVals = [num, num, num, num];
        } else {
            curVals[sideIdx] = num;
        }

        var cssVal = curVals[0] + 'px ' + curVals[1] + 'px ' + curVals[2] + 'px ' + curVals[3] + 'px';
        block.data[type] = cssVal;
        renderCanvas();
    }

    function stepElemSingleValue(key, delta, minVal, maxVal) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var cur = parseFloat(block.data[key] || '0');
        if (isNaN(cur)) cur = minVal;
        var next = Math.max(minVal, Math.min(maxVal, cur + delta));
        block.data[key] = next + 'px';
        renderCanvas();
        renderInspector();
    }

    function onElemSingleNumChange(key, rawVal) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};

        var num = parseFloat(rawVal);
        if (isNaN(num)) return;
        block.data[key] = num + 'px';
        renderCanvas();
    }

    function resetElementStyles() {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) return;

        delete block.data.margin;
        delete block.data.padding;
        delete block.data.display;
        delete block.data.flex_direction;
        delete block.data.justify_content;
        delete block.data.align_items;
        delete block.data.flex_wrap;
        delete block.data.gap;
        delete block.data.width;
        delete block.data.max_width;
        delete block.data.background;
        delete block.data.text_color;
        delete block.data.color;
        delete block.data.heading_color;
        delete block.data.link_color;
        delete block.data.link_hover_color;
        delete block.data.font_family;
        delete block.data.font_size;
        delete block.data.font_weight;
        delete block.data.line_height;
        delete block.data.letter_spacing;
        delete block.data.text_align;
        delete block.data.text_transform;
        delete block.data.border;
        delete block.data.border_style;
        delete block.data.border_width;
        delete block.data.border_color;
        delete block.data.border_radius;
        delete block.data.box_shadow;
        delete block.data.opacity;
        delete block.data.hide_desktop;
        delete block.data.hide_laptop;
        delete block.data.hide_1024;
        delete block.data.hide_tablet;
        delete block.data.hide_768;
        delete block.data.hide_mobile;
        delete block.data.hide_375;

        renderCanvas();
        renderInspector();
        showToast('↺ Estilos CSS do elemento redefinidos para os padrões!');
    }

    function updateNavToggleStyle(val) {
        updateBlockProp('toggle_style', val);
        state.mobile_toggle_style = val;
        var partSetting = document.getElementById('setting_mobile_toggle');
        if (partSetting) partSetting.value = val;
        renderCanvas();
        renderInspector();
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

        // DESIGN TOKENS COLOR PICKER
    // ==========================================
    // ==========================================
    // GERADOR AVANÇADO DE FUNDOS E DEGRADÊS (ESTILO ELEMENTOR / SOLID)
    // ==========================================
    var bgControlModeState = {
        section: null,
        element: null
    };

    function getSelectedBlock() {
        if (!selectedBlockCoord) return null;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data || !sec.columns_data[selectedBlockCoord.col]) return null;
        return sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx] || null;
    }

    function getSelectedSection() {
        return state.sections[activeSecIdx] || state.sections[0] || null;
    }

    function refreshActiveContextUI(isSection) {
        if (isSection) {
            var curSec = getSelectedSection();
            if (curSec) {
                renderSectionStyleControls(curSec);
            }
        } else {
            renderInspector();
        }
    }

    function renderSectionSettingsTab() {
        refreshActiveContextUI(true);
    }

    function parseGradientCss(str) {
        var def = {
            type: 'linear',          // 'linear' | 'radial'
            direction: 'horizontal', // 'horizontal' (90deg) | 'vertical' (180deg) | 'diagonal' (135deg) | 'radial' | 'custom'
            angle: 90,
            position: 'center center',
            color1: '#0b0c10',
            stop1: 0,
            has_middle: false,
            color_mid: '#0284c7',
            stop_mid: 50,
            color2: '#38bdf8',
            stop2: 100
        };

        if (!str || typeof str !== 'string' || str.indexOf('gradient(') === -1) {
            return def;
        }

        var s = str.trim();

        if (s.indexOf('radial-gradient(') === 0) {
            def.type = 'radial';
            def.direction = 'radial';
            var m3 = s.match(/radial-gradient\((?:circle at ([^,]+),\s*)?([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%\)/i);
            if (m3) {
                def.position = m3[1] ? m3[1].trim() : 'center center';
                def.color1 = m3[2].trim();
                def.stop1 = parseInt(m3[3], 10) || 0;
                def.has_middle = true;
                def.color_mid = m3[4].trim();
                def.stop_mid = parseInt(m3[5], 10) || 50;
                def.color2 = m3[6].trim();
                def.stop2 = parseInt(m3[7], 10) || 100;
                return def;
            }

            var m2 = s.match(/radial-gradient\((?:circle at ([^,]+),\s*)?([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%\)/i);
            if (m2) {
                def.position = m2[1] ? m2[1].trim() : 'center center';
                def.color1 = m2[2].trim();
                def.stop1 = parseInt(m2[3], 10) || 0;
                def.color2 = m2[4].trim();
                def.stop2 = parseInt(m2[5], 10) || 100;
                return def;
            }
            return def;
        }

        if (s.indexOf('linear-gradient(') === 0) {
            def.type = 'linear';

            var m3 = s.match(/linear-gradient\((\d+)deg,\s*([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%\)/i);
            if (m3) {
                var ang3 = parseInt(m3[1], 10) || 90;
                def.angle = ang3;
                def.direction = (ang3 === 90) ? 'horizontal' : ((ang3 === 180) ? 'vertical' : ((ang3 === 135) ? 'diagonal' : 'custom'));
                def.color1 = m3[2].trim();
                def.stop1 = parseInt(m3[3], 10) || 0;
                def.has_middle = true;
                def.color_mid = m3[4].trim();
                def.stop_mid = parseInt(m3[5], 10) || 50;
                def.color2 = m3[6].trim();
                def.stop2 = parseInt(m3[7], 10) || 100;
                return def;
            }

            var m2 = s.match(/linear-gradient\((\d+)deg,\s*([^%]+?)\s+(\d+)%,\s*([^%]+?)\s+(\d+)%\)/i);
            if (m2) {
                var ang2 = parseInt(m2[1], 10) || 90;
                def.angle = ang2;
                def.direction = (ang2 === 90) ? 'horizontal' : ((ang2 === 180) ? 'vertical' : ((ang2 === 135) ? 'diagonal' : 'custom'));
                def.color1 = m2[2].trim();
                def.stop1 = parseInt(m2[3], 10) || 0;
                def.color2 = m2[4].trim();
                def.stop2 = parseInt(m2[5], 10) || 100;
                return def;
            }
        }

        return def;
    }

    function compileGradientCss(g) {
        var stops = g.has_middle
            ? g.color1 + ' ' + g.stop1 + '%, ' + g.color_mid + ' ' + g.stop_mid + '%, ' + g.color2 + ' ' + g.stop2 + '%'
            : g.color1 + ' ' + g.stop1 + '%, ' + g.color2 + ' ' + g.stop2 + '%';

        if (g.type === 'radial') {
            return 'radial-gradient(circle at ' + (g.position || 'center center') + ', ' + stops + ')';
        }

        var ang = (g.direction === 'horizontal') ? 90 
                : ((g.direction === 'vertical') ? 180 
                : ((g.direction === 'diagonal') ? 135 
                : (g.angle !== undefined ? g.angle : 90)));

        return 'linear-gradient(' + ang + 'deg, ' + stops + ')';
    }

    function renderAdvancedBackgroundControl(label, key, curVal, defaultToken, isSection) {
        var stateKey = isSection ? 'section' : 'element';
        var isGradient = curVal && typeof curVal === 'string' && curVal.indexOf('gradient(') !== -1;
        var activeMode = bgControlModeState[stateKey] || (isGradient ? 'gradient' : 'solid');
        var previewId = 'grad_live_preview_' + (isSection ? 'sec' : 'elem');

        var dt = window.DS_DESIGN_TOKENS || {};
        var palette = dt.palette || {
            primary: '#38bdf8', secondary: '#1f2833', tertiary: '#60a5fa',
            background: '#0b0c10', surface: 'rgba(31, 40, 51, 0.7)',
            text_main: '#c5c6c7', text_muted: '#8b8c8d', border: 'rgba(255, 255, 255, 0.1)'
        };

        var tokenList = [
            { id: 'primary', label: 'Primária', var: 'var(--color-primary)', hex: palette.primary || '#38bdf8' },
            { id: 'secondary', label: 'Secundária', var: 'var(--color-secondary)', hex: palette.secondary || '#1f2833' },
            { id: 'tertiary', label: 'Terciária', var: 'var(--color-tertiary)', hex: palette.tertiary || '#60a5fa' },
            { id: 'background', label: 'Fundo', var: 'var(--color-background)', hex: palette.background || '#0b0c10' },
            { id: 'surface', label: 'Superfície', var: 'var(--color-surface)', hex: palette.surface || '#1f2833' },
            { id: 'border', label: 'Borda', var: 'var(--color-border)', hex: palette.border || '#334155' },
            { id: 'transparent', label: 'Transparente', var: 'transparent', hex: 'transparent' }
        ];

        var gData = parseGradientCss(isGradient ? curVal : '');

        var html = '<div class="inspector-field" style="margin-top:12px; background:rgba(15,23,42,0.4); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.06);">' +
                   '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">' +
                   '<label class="inspector-label" style="margin-bottom:0; font-weight:700;">' + label + '</label>' +
                   '</div>' +
                   '<div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:10px;">' +
                   '<button type="button" class="btn ' + (activeMode === 'solid' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:11px; padding:6px;" onclick="setBgMode(\'' + key + '\', \'solid\', ' + isSection + ')">🎨 Sólido / Tokens</button>' +
                   '<button type="button" class="btn ' + (activeMode === 'gradient' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:11px; padding:6px;" onclick="setBgMode(\'' + key + '\', \'gradient\', ' + isSection + ')">🌈 Degradê (Gradiente)</button>' +
                   '</div>';

        if (activeMode === 'solid') {
            var swatchesHtml = '';
            tokenList.forEach(function(t) {
                var isAct = (curVal === t.var || curVal === t.hex || (!curVal && defaultToken === t.id));
                var actClass = isAct ? ' active' : '';
                var transClass = (t.id === 'transparent') ? ' is-transparent' : '';
                var bgStyle = (t.id === 'transparent') ? '' : 'background-color:' + t.hex + ';';
                var clickFn = isSection
                    ? 'updateActiveSecSetting(\'' + key + '\', \'' + escapeAttr(t.var) + '\'); refreshActiveContextUI(true);'
                    : 'updateBlockProp(\'' + key + '\', \'' + escapeAttr(t.var) + '\'); refreshActiveContextUI(false);';

                swatchesHtml += '<button type="button" class="token-swatch-item' + actClass + transClass + '" style="' + bgStyle + '" title="' + escapeAttr(t.label) + '" onclick="' + clickFn + '"></button>';
            });

            var popularSwatchesHtml = '';
            DS_POPULAR_COLORS.forEach(function(c) {
                var isAct = (curVal && curVal.toLowerCase() === c.hex.toLowerCase());
                var actClass = isAct ? ' active' : '';
                var clickPopFn = isSection
                    ? 'updateActiveSecSetting(\'' + key + '\', \'' + c.hex + '\'); refreshActiveContextUI(true);'
                    : 'updateBlockProp(\'' + key + '\', \'' + c.hex + '\'); refreshActiveContextUI(false);';
                popularSwatchesHtml += '<button type="button" class="token-swatch-item' + actClass + '" style="background-color:' + c.hex + ';" title="' + escapeAttr(c.name + ' (' + c.hex + ')') + '" onclick="' + clickPopFn + '"></button>';
            });

            var hexVal = curVal || '#0b0c10';
            if (hexVal.indexOf('var(') === 0) {
                var matched = tokenList.find(function(t) { return t.var === hexVal; });
                hexVal = (matched && matched.hex && matched.hex !== 'transparent' && matched.hex.indexOf('#') === 0) ? matched.hex : '#38bdf8';
            }
            if (!hexVal.startsWith('#') || hexVal.length !== 7) hexVal = '#38bdf8';

            var changeColorFn = isSection
                ? 'updateActiveSecSetting(\'' + key + '\', this.value); refreshActiveContextUI(true);'
                : 'updateBlockProp(\'' + key + '\', this.value); refreshActiveContextUI(false);';
            var inputColorFn = isSection
                ? 'updateActiveSecSetting(\'' + key + '\', this.value); renderCanvas();'
                : 'updateBlockProp(\'' + key + '\', this.value); renderCanvas();';
            var resetFn = isSection
                ? 'updateActiveSecSetting(\'' + key + '\', \'\'); refreshActiveContextUI(true);'
                : 'updateBlockProp(\'' + key + '\', \'\'); refreshActiveContextUI(false);';

            html += '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:3px; text-transform:uppercase; letter-spacing:0.4px;">🌐 Tokens Globais do Tema:</div>' +
                    '<div class="token-swatches-grid" style="margin-top:2px; margin-bottom:8px;">' + swatchesHtml + '</div>' +
                    '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:3px; text-transform:uppercase; letter-spacing:0.4px;">✨ Cores Populares & Contrastes:</div>' +
                    '<div class="token-swatches-grid" style="margin-top:2px; margin-bottom:8px;">' + popularSwatchesHtml + '</div>' +
                    '<div style="font-size:10px; color:var(--text-muted); font-weight:700; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.4px;">🎨 Seletor Livre ou Código Hex / CSS:</div>' +
                    '<div style="display:flex; gap:8px; align-items:center;">' +
                    '<div style="position:relative; width:28px; height:28px; min-width:28px; border-radius:50%; border:2px solid rgba(255,255,255,0.3); overflow:hidden; cursor:pointer; background:' + escapeAttr(hexVal) + '; box-shadow:0 1px 4px rgba(0,0,0,0.5);" title="Clique para abrir o espectro livre de cores">' +
                    '<input type="color" value="' + escapeAttr(hexVal) + '" oninput="' + inputColorFn + '" onchange="' + changeColorFn + '" style="opacity:0; position:absolute; top:0; left:0; width:100%; height:100%; cursor:pointer;">' +
                    '</div>' +
                    '<input type="text" class="inspector-input" value="' + escapeAttr(curVal || '') + '" oninput="' + inputColorFn + '" placeholder="var(--color-primary), #hex ou rgba" style="margin:0; font-size:11px; font-family:monospace; flex:1;">' +
                    '<button type="button" class="header-btn" style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.06); color:var(--text-muted);" onclick="' + resetFn + '" title="Limpar / Restaurar Padrão">✕</button>' +
                    '</div>';
        } else {
            // MODO DEGRADÊ / GRADIENTE
            var currentGradCss = compileGradientCss(gData);
            html += '<div id="' + previewId + '" style="height:34px; border-radius:6px; border:1px solid rgba(255,255,255,0.25); margin-bottom:10px; background:' + currentGradCss + '; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);" title="Visualização do Degradê em Tempo Real"></div>';

            // 1. ESCOLHA DE TIPO / DIREÇÃO DO DEGRADÊ
            html += '<div style="margin-bottom:10px;">' +
                    '<label class="inspector-label" style="font-size:11px; margin-bottom:4px; font-weight:700;">TIPO E DIREÇÃO DO DEGRADÊ:</label>' +
                    '<div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:4px;">' +
                    '<button type="button" class="btn ' + (gData.direction === 'horizontal' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:10px; padding:6px 4px;" onclick="setGradientDirection(\'' + key + '\', \'horizontal\', ' + isSection + ')">↔️ Linear (Horizontal)</button>' +
                    '<button type="button" class="btn ' + (gData.direction === 'vertical' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:10px; padding:6px 4px;" onclick="setGradientDirection(\'' + key + '\', \'vertical\', ' + isSection + ')">↕️ Linear (Vertical)</button>' +
                    '</div>' +
                    '<div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:4px;">' +
                    '<button type="button" class="btn ' + (gData.direction === 'diagonal' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:10px; padding:6px 4px;" onclick="setGradientDirection(\'' + key + '\', \'diagonal\', ' + isSection + ')">↗️ Linear (Diagonal)</button>' +
                    '<button type="button" class="btn ' + (gData.direction === 'radial' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:10px; padding:6px 4px;" onclick="setGradientDirection(\'' + key + '\', \'radial\', ' + isSection + ')">🎯 Radial (Esférico)</button>' +
                    '</div>' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">' +
                    '<button type="button" class="btn ' + (gData.direction === 'custom' ? 'btn-primary' : 'btn-secondary') + '" style="font-size:10px; padding:4px 8px; width:100%;" onclick="setGradientDirection(\'' + key + '\', \'custom\', ' + isSection + ')">📐 Ângulo Livre (' + gData.angle + '°)</button>' +
                    '</div>' +
                    '</div>';

            // Se for Ângulo Livre
            if (gData.direction === 'custom') {
                html += '<div style="margin-bottom:10px; padding:6px; background:rgba(0,0,0,0.2); border-radius:6px;">' +
                        '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2px;">' +
                        '<span style="font-size:10px; color:#fff; font-weight:700;">Ângulo Personalizado:</span>' +
                        '<span id="badge_angle_' + (isSection ? 'sec' : 'elem') + '" style="font-size:10px; color:var(--primary); font-weight:700;">' + gData.angle + '°</span>' +
                        '</div>' +
                        '<input type="range" min="0" max="360" value="' + gData.angle + '" oninput="document.getElementById(\'badge_angle_' + (isSection ? 'sec' : 'elem') + '\').textContent = this.value + \'°\'; updateGradientProp(\'' + key + '\', \'angle\', parseInt(this.value,10), ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="width:100%; cursor:pointer;">' +
                        '</div>';
            }

            // Se for Radial: Posição do centro
            if (gData.type === 'radial') {
                html += '<div style="margin-bottom:10px;">' +
                        '<span style="font-size:11px; font-weight:700; color:#fff; display:block; margin-bottom:4px;">Origem do Centro Radial:</span>' +
                        '<select class="inspector-select" onchange="updateGradientProp(\'' + key + '\', \'position\', this.value, ' + isSection + ')">' +
                        '<option value="center center"' + (gData.position === 'center center' ? ' selected' : '') + '>🎯 Centro (Center)</option>' +
                        '<option value="top center"' + (gData.position === 'top center' ? ' selected' : '') + '>⬆️ Topo Centro</option>' +
                        '<option value="top left"' + (gData.position === 'top left' ? ' selected' : '') + '>↖️ Topo Esquerda</option>' +
                        '<option value="top right"' + (gData.position === 'top right' ? ' selected' : '') + '>↗️ Topo Direita</option>' +
                        '<option value="bottom center"' + (gData.position === 'bottom center' ? ' selected' : '') + '>⬇️ Base Centro</option>' +
                        '</select></div>';
            }

            // Função interna para gerar Swatches de Tokens para os Stops
            function renderColorStopSwatches(stopKey, curStopColor) {
                var sHtml = '<div style="display:flex; gap:4px; margin-bottom:6px; flex-wrap:wrap;">';
                tokenList.forEach(function(t) {
                    if (t.id === 'transparent') return;
                    var isSelected = (curStopColor === t.hex || curStopColor === t.var);
                    var borderStyle = isSelected ? 'border:2px solid #fff; outline:2px solid var(--primary); transform:scale(1.15);' : 'border:1px solid rgba(255,255,255,0.2);';
                    sHtml += '<button type="button" style="width:18px; height:18px; border-radius:4px; ' + borderStyle + ' background-color:' + t.hex + '; cursor:pointer; padding:0; transition:transform 0.15s;" title="' + escapeAttr(t.label + ' (' + t.hex + ')') + '" onclick="updateGradientProp(\'' + key + '\', \'' + stopKey + '\', \'' + t.hex + '\', ' + isSection + ')"></button>';
                });
                sHtml += '</div>';
                return sHtml;
            }

            // 2. COR DE INÍCIO
            var hex1 = gData.color1.startsWith('#') ? gData.color1 : '#0b0c10';
            html += '<div style="margin-bottom:8px; padding:8px; background:rgba(0,0,0,0.3); border-radius:6px; border-left:3px solid var(--primary);">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                    '<span style="font-size:11px; font-weight:700; color:#fff;">🟢 Cor de Início (Start)</span>' +
                    '<span id="badge_stop1_' + (isSection ? 'sec' : 'elem') + '" style="font-size:10px; color:var(--primary); font-weight:700;">' + gData.stop1 + '%</span>' +
                    '</div>' +
                    renderColorStopSwatches('color1', gData.color1) +
                    '<div style="display:flex; gap:6px; align-items:center; margin-bottom:4px;">' +
                    '<input type="color" value="' + escapeAttr(hex1) + '" onchange="updateGradientProp(\'' + key + '\', \'color1\', this.value, ' + isSection + ')" style="width:30px; height:24px; padding:0; border:1px solid var(--border); border-radius:4px; cursor:pointer; background:transparent;">' +
                    '<input type="text" class="inspector-input" value="' + escapeAttr(gData.color1) + '" oninput="updateGradientProp(\'' + key + '\', \'color1\', this.value, ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="font-size:11px; height:24px;" placeholder="#hex ou rgba">' +
                    '</div>' +
                    '<input type="range" min="0" max="100" value="' + gData.stop1 + '" oninput="document.getElementById(\'badge_stop1_' + (isSection ? 'sec' : 'elem') + '\').textContent = this.value + \'%\'; updateGradientProp(\'' + key + '\', \'stop1\', parseInt(this.value,10), ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="width:100%; cursor:pointer;">' +
                    '</div>';

            // 3. COR INTERMEDIÁRIA (OPCIONAL COM TOGGLE)
            if (gData.has_middle) {
                var hexMid = gData.color_mid.startsWith('#') ? gData.color_mid : '#0284c7';
                html += '<div style="margin-bottom:8px; padding:8px; background:rgba(0,0,0,0.3); border-radius:6px; border-left:3px solid #f59e0b;">' +
                        '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                        '<span style="font-size:11px; font-weight:700; color:#fff;">🟡 Cor Intermediária (Meio)</span>' +
                        '<div style="display:flex; align-items:center; gap:6px;">' +
                        '<span id="badge_stopmid_' + (isSection ? 'sec' : 'elem') + '" style="font-size:10px; color:#f59e0b; font-weight:700;">' + gData.stop_mid + '%</span>' +
                        '<button type="button" style="background:transparent; border:none; color:#ef4444; cursor:pointer; font-size:12px; font-weight:700; padding:0;" title="Remover cor intermediária" onclick="updateGradientProp(\'' + key + '\', \'has_middle\', false, ' + isSection + ')">✕</button>' +
                        '</div></div>' +
                        renderColorStopSwatches('color_mid', gData.color_mid) +
                        '<div style="display:flex; gap:6px; align-items:center; margin-bottom:4px;">' +
                        '<input type="color" value="' + escapeAttr(hexMid) + '" onchange="updateGradientProp(\'' + key + '\', \'color_mid\', this.value, ' + isSection + ')" style="width:30px; height:24px; padding:0; border:1px solid var(--border); border-radius:4px; cursor:pointer; background:transparent;">' +
                        '<input type="text" class="inspector-input" value="' + escapeAttr(gData.color_mid) + '" oninput="updateGradientProp(\'' + key + '\', \'color_mid\', this.value, ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="font-size:11px; height:24px;" placeholder="#hex ou rgba">' +
                        '</div>' +
                        '<input type="range" min="0" max="100" value="' + gData.stop_mid + '" oninput="document.getElementById(\'badge_stopmid_' + (isSection ? 'sec' : 'elem') + '\').textContent = this.value + \'%\'; updateGradientProp(\'' + key + '\', \'stop_mid\', parseInt(this.value,10), ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="width:100%; cursor:pointer;">' +
                        '</div>';
            } else {
                html += '<div style="margin-bottom:8px; text-align:center;">' +
                        '<button type="button" class="btn btn-secondary" style="font-size:10px; padding:5px 8px; width:100%; border-style:dashed; border-color:rgba(255,255,255,0.25);" onclick="updateGradientProp(\'' + key + '\', \'has_middle\', true, ' + isSection + ')">➕ Adicionar Cor Intermediária (3 Cores)</button>' +
                        '</div>';
            }

            // 4. COR DE FIM
            var hex2 = gData.color2.startsWith('#') ? gData.color2 : '#38bdf8';
            html += '<div style="margin-bottom:10px; padding:8px; background:rgba(0,0,0,0.3); border-radius:6px; border-left:3px solid #ec4899;">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">' +
                    '<span style="font-size:11px; font-weight:700; color:#fff;">🔴 Cor de Fim (Stop)</span>' +
                    '<span id="badge_stop2_' + (isSection ? 'sec' : 'elem') + '" style="font-size:10px; color:#ec4899; font-weight:700;">' + gData.stop2 + '%</span>' +
                    '</div>' +
                    renderColorStopSwatches('color2', gData.color2) +
                    '<div style="display:flex; gap:6px; align-items:center; margin-bottom:4px;">' +
                    '<input type="color" value="' + escapeAttr(hex2) + '" onchange="updateGradientProp(\'' + key + '\', \'color2\', this.value, ' + isSection + ')" style="width:30px; height:24px; padding:0; border:1px solid var(--border); border-radius:4px; cursor:pointer; background:transparent;">' +
                    '<input type="text" class="inspector-input" value="' + escapeAttr(gData.color2) + '" oninput="updateGradientProp(\'' + key + '\', \'color2\', this.value, ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="font-size:11px; height:24px;" placeholder="#hex ou rgba">' +
                    '</div>' +
                    '<input type="range" min="0" max="100" value="' + gData.stop2 + '" oninput="document.getElementById(\'badge_stop2_' + (isSection ? 'sec' : 'elem') + '\').textContent = this.value + \'%\'; updateGradientProp(\'' + key + '\', \'stop2\', parseInt(this.value,10), ' + isSection + ', true)" onchange="refreshActiveContextUI(' + isSection + ')" style="width:100%; cursor:pointer;">' +
                    '</div>';

            // 5. PRESETS PRONTOS DE DEGRADÊ COM 1 CLIQUE
            var presets = [
                { name: '🌌 Cyber', css: 'linear-gradient(90deg, #0b0c10 0%, #1f2833 100%)' },
                { name: '⚡ Neon Blue', css: 'linear-gradient(90deg, #0284c7 0%, #38bdf8 100%)' },
                { name: '🔮 Synthwave', css: 'linear-gradient(135deg, #4c1d95 0%, #c026d3 100%)' },
                { name: '🌿 Emerald', css: 'linear-gradient(90deg, #064e3b 0%, #10b981 100%)' },
                { name: '🔥 Sunset', css: 'linear-gradient(180deg, #7c2d12 0%, #f97316 100%)' },
                { name: '🖤 Carbon', css: 'linear-gradient(180deg, #18181b 0%, #09090b 100%)' },
                { name: '🪟 Frost', css: 'linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%)' }
            ];

            html += '<div style="font-size:10px; color:var(--text-muted); margin-bottom:4px; font-weight:700;">PRESETS DE DEGRADÊ (1 CLIQUE):</div>' +
                    '<div style="display:flex; flex-wrap:wrap; gap:4px;">';
            presets.forEach(function(p) {
                var pClick = isSection
                    ? 'updateActiveSecSetting(\'' + key + '\', \'' + escapeAttr(p.css) + '\'); refreshActiveContextUI(true);'
                    : 'updateBlockProp(\'' + key + '\', \'' + escapeAttr(p.css) + '\'); refreshActiveContextUI(false);';
                html += '<button type="button" class="btn btn-secondary" style="font-size:10px; padding:3px 6px; border-radius:4px;" onclick="' + pClick + '">' + p.name + '</button>';
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function setGradientDirection(key, dir, isSection) {
        var curVal = '';
        if (isSection) {
            var curSec = getSelectedSection();
            curVal = (curSec && curSec.container_settings && curSec.container_settings[key]) || '';
        } else {
            var block = getSelectedBlock();
            curVal = (block && block.data && block.data[key]) || '';
        }

        var gData = parseGradientCss(curVal);
        gData.direction = dir;

        if (dir === 'radial') {
            gData.type = 'radial';
        } else {
            gData.type = 'linear';
            if (dir === 'horizontal') gData.angle = 90;
            else if (dir === 'vertical') gData.angle = 180;
            else if (dir === 'diagonal') gData.angle = 135;
        }

        var compiled = compileGradientCss(gData);

        if (isSection) {
            updateActiveSecSetting(key, compiled);
        } else {
            updateBlockProp(key, compiled);
        }
        refreshActiveContextUI(isSection);
    }

    function setBgMode(key, mode, isSection) {
        var stateKey = isSection ? 'section' : 'element';
        bgControlModeState[stateKey] = mode;
        if (mode === 'gradient') {
            var defaultGrad = 'linear-gradient(90deg, #0b0c10 0%, #38bdf8 100%)';
            if (isSection) {
                updateActiveSecSetting(key, defaultGrad);
            } else {
                updateBlockProp(key, defaultGrad);
            }
        } else {
            var defaultSolid = 'transparent';
            if (isSection) {
                updateActiveSecSetting(key, defaultSolid);
            } else {
                updateBlockProp(key, defaultSolid);
            }
        }
        refreshActiveContextUI(isSection);
    }

    function updateGradientProp(key, prop, val, isSection, skipFullRefresh) {
        var curVal = '';
        if (isSection) {
            var curSec = getSelectedSection();
            curVal = (curSec && curSec.container_settings && curSec.container_settings[key]) || '';
        } else {
            var block = getSelectedBlock();
            curVal = (block && block.data && block.data[key]) || '';
        }

        var gData = parseGradientCss(curVal);
        gData[prop] = val;
        var compiled = compileGradientCss(gData);

        if (isSection) {
            updateActiveSecSetting(key, compiled);
        } else {
            updateBlockProp(key, compiled);
        }

        var previewEl = document.getElementById('grad_live_preview_' + (isSection ? 'sec' : 'elem'));
        if (previewEl) {
            previewEl.style.background = compiled;
        }

        if (!skipFullRefresh) {
            refreshActiveContextUI(isSection);
        }
    }

    // FONTS / TYPOGRAPHY SELECTOR COM DESIGN TOKENS E FONTES WEB
    function inspectorFontSelector(label, key, curVal) {
        var dt = window.DS_DESIGN_TOKENS || {};
        var typo = dt.typography || {};
        var fontPrimary = typo.primary_font || 'Inter, sans-serif';
        var fontSecondary = typo.secondary_font || 'Fira Code, monospace';
        var fontTertiary = typo.tertiary_font || 'Montserrat, sans-serif';

        var groups = [
            {
                group: 'Design Tokens do Tema (Recomendado)',
                options: [
                    { value: 'var(--font-primary)', label: '⭐ Fonte Primária (' + fontPrimary.split(',')[0].replace(/['"]/g, '') + ')' },
                    { value: 'var(--font-secondary)', label: '⭐ Fonte Secundária (' + fontSecondary.split(',')[0].replace(/['"]/g, '') + ')' },
                    { value: 'var(--font-tertiary)', label: '⭐ Fonte Terciária (' + fontTertiary.split(',')[0].replace(/['"]/g, '') + ')' }
                ]
            },
            {
                group: 'Sans-Serif (Modernas)',
                options: [
                    { value: 'Inter, sans-serif', label: 'Inter' },
                    { value: 'Roboto, sans-serif', label: 'Roboto' },
                    { value: 'Montserrat, sans-serif', label: 'Montserrat' },
                    { value: 'Poppins, sans-serif', label: 'Poppins' },
                    { value: 'Open Sans, sans-serif', label: 'Open Sans' },
                    { value: 'system-ui, -apple-system, sans-serif', label: 'System UI (Nativa)' }
                ]
            },
            {
                group: 'Serif (Editoriais & Clássicas)',
                options: [
                    { value: 'Georgia, serif', label: 'Georgia' },
                    { value: "'Playfair Display', serif", label: 'Playfair Display' },
                    { value: "'Merriweather', serif", label: 'Merriweather' }
                ]
            },
            {
                group: 'Monospace (Código & Técnico)',
                options: [
                    { value: "'Fira Code', monospace", label: 'Fira Code' },
                    { value: "'Courier New', monospace", label: 'Courier New' }
                ]
            }
        ];

        var html = '<div class="inspector-field">' +
                   '<label class="inspector-label">' + label + '</label>' +
                   '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value); renderCanvas(); renderInspector();">';

        groups.forEach(function(g) {
            html += '<optgroup label="' + escapeAttr(g.group) + '">';
            g.options.forEach(function(opt) {
                var isSel = (opt.value === curVal);
                html += '<option value="' + escapeAttr(opt.value) + '"' + (isSel ? ' selected' : '') + '>' + escapeHtml(opt.label) + '</option>';
            });
            html += '</optgroup>';
        });

        html += '</select></div>';
        return html;
    }

    // HOVER ANIMATION SELECTOR
    function inspectorHoverAnimation(label, key, curVal) {
        var options = [
            { value: 'lift', label: '🚀 Elevar com Sombra (Lift & Shadow)' },
            { value: 'grow', label: '🔍 Aumentar Escala (Scale Up)' },
            { value: 'glow', label: '✨ Brilho Pulsante (Glow)' },
            { value: 'shimmer', label: '💫 Reflexo Metálico (Shimmer)' },
            { value: 'bounce', label: '⚡ Salto Suave (Bounce)' },
            { value: 'none', label: '⚪ Nenhuma Animação' }
        ];

        return inspectorSelect(label, key, options, curVal || 'lift');
    }

    function inspectorTextInput(label, key, val) {
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<input type="text" class="inspector-input" value="' + escapeAttr(val) + '" oninput="updateBlockProp(\'' + key + '\', this.value)">' +
               '</div>';
    }

    function inspectorInput(label, key, val, type) {
        var inputType = type || 'text';
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<input type="' + escapeAttr(inputType) + '" class="inspector-input" value="' + escapeAttr(val) + '" oninput="updateBlockProp(\'' + key + '\', this.value)">' +
               '</div>';
    }

    function inspectorCheckbox(label, key, isChecked) {
        return '<label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#cbd5e1; margin-bottom:6px; cursor:pointer; user-select:none;">' +
               '<input type="checkbox" ' + (isChecked ? 'checked' : '') + ' onchange="updateBlockProp(\'' + key + '\', this.checked); renderInspector(); var s = getSelectedSection(); if (s) renderSectionStyleControls(s);"> ' +
               label +
               '</label>';
    }

    function inspectorTextarea(label, key, val) {
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<textarea rows="4" class="inspector-textarea" oninput="updateBlockProp(\'' + key + '\', this.value)">' + escapeHtml(val) + '</textarea>' +
               '</div>';
    }

    function inspectorSelect(label, key, options, curVal) {
        var opts = options.map(function(o) {
            var val = (typeof o === 'object') ? o.value : o;
            var text = (typeof o === 'object') ? o.label : o;
            return '<option value="' + escapeAttr(val) + '"' + (String(val) === String(curVal) ? ' selected' : '') + '>' + escapeHtml(text) + '</option>';
        }).join('');
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value); renderCanvas(); renderInspector();">' + opts + '</select>' +
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
        var headerMenu = menusList.find(function(m) { return m.location === 'header'; }) || menusList[0] || null;
        var effectiveVal = curVal ? String(curVal) : (headerMenu ? String(headerMenu.id) : '');
        var opts = '<option value=""' + (!effectiveVal ? ' selected' : '') + '>-- Padrão: Menu Principal (Header) --</option>';
        menusList.forEach(function(m) {
            var locLabel = m.location ? ' (' + m.location + ')' : '';
            opts += '<option value="' + m.id + '"' + (String(m.id) === effectiveVal ? ' selected' : '') + '>' + escapeHtml(m.name + locLabel) + '</option>';
        });
        return '<div class="inspector-field">' +
               '<label class="inspector-label">' + label + '</label>' +
               '<select class="inspector-select" onchange="updateBlockProp(\'' + key + '\', this.value); renderCanvas(); renderInspector();">' + opts + '</select>' +
               '</div>';
    }

    function inspectorSocialIconsRepeater(block) {
        var rawNetworks = block.data.networks || [
            { network: 'instagram', url: 'https://instagram.com' },
            { network: 'whatsapp', url: 'https://wa.me/5511999999999' },
            { network: 'youtube', url: 'https://youtube.com' }
        ];

        var list = [];
        if (Array.isArray(rawNetworks)) {
            list = rawNetworks;
        } else if (typeof rawNetworks === 'object' && rawNetworks !== null) {
            for (var k in rawNetworks) {
                list.push({ network: k, url: rawNetworks[k] });
            }
        }
        block.data.networks = list;

        var availableNetworks = [
            { id: 'whatsapp', label: '💬 WhatsApp' },
            { id: 'instagram', label: '📸 Instagram' },
            { id: 'youtube', label: '🎥 YouTube' },
            { id: 'linkedin', label: '💼 LinkedIn' },
            { id: 'facebook', label: '👥 Facebook' },
            { id: 'x', label: '✖️ X (Twitter)' },
            { id: 'tiktok', label: '🎵 TikTok' },
            { id: 'github', label: '🐙 GitHub' },
            { id: 'telegram', label: '✈️ Telegram' },
            { id: 'email', label: '✉️ Email' },
            { id: 'phone', label: '📞 Telefone' },
            { id: 'website', label: '🌐 Website Geral' }
        ];

        var out = '<div class="inspector-field" style="margin-top:16px;">' +
                  '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">' +
                  '<label class="inspector-label" style="margin-bottom:0; font-weight:700; color:var(--primary);">🔗 Redes Sociais Conectadas</label>' +
                  '<button type="button" class="header-btn" onclick="addSocialIconItem()" style="font-size:10px; padding:2px 8px; background:rgba(56,189,248,0.15); border-color:var(--primary); color:var(--primary);">➕ Adicionar</button>' +
                  '</div>';

        if (list.length === 0) {
            out += '<div style="font-size:11px; color:var(--text-muted); text-align:center; padding:12px; background:rgba(255,255,255,0.02); border-radius:6px;">Nenhuma rede adicionada. Clique em Adicionar acima.</div>';
        } else {
            out += '<div style="display:flex; flex-direction:column; gap:8px;">';
            list.forEach(function(item, idx) {
                var netSelect = '<select class="inspector-select" style="width:130px; font-size:11px; padding:4px 6px;" onchange="updateSocialIconItem(' + idx + ', \'network\', this.value)">';
                availableNetworks.forEach(function(an) {
                    netSelect += '<option value="' + an.id + '"' + (item.network === an.id ? ' selected' : '') + '>' + an.label + '</option>';
                });
                netSelect += '</select>';

                out += '<div style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:6px 8px;">' +
                       netSelect +
                       '<input type="text" class="inspector-input" style="flex:1; font-size:11px; padding:4px 8px;" value="' + escapeAttr(item.url || '') + '" placeholder="https://..." oninput="updateSocialIconItem(' + idx + ', \'url\', this.value)">' +
                       '<button type="button" class="block-act-btn del" onclick="removeSocialIconItem(' + idx + ')" title="Remover rede" style="width:24px; height:24px; line-height:22px; font-size:11px;">✕</button>' +
                       '</div>';
            });
            out += '</div>';
        }

        out += '</div>';
        return out;
    }

    function addSocialIconItem() {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        if (!Array.isArray(block.data.networks)) block.data.networks = [];
        block.data.networks.push({ network: 'instagram', url: 'https://instagram.com' });
        renderCanvas();
        renderInspector();
    }

    function removeSocialIconItem(idx) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (block.data && Array.isArray(block.data.networks)) {
            block.data.networks.splice(idx, 1);
            renderCanvas();
            renderInspector();
        }
    }

    function updateSocialIconItem(idx, key, val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (block.data && Array.isArray(block.data.networks) && block.data.networks[idx]) {
            block.data.networks[idx][key] = val;
            renderCanvas();
        }
    }

    function updateBlockProp(key, val) {
        if (!selectedBlockCoord) return;
        var sec = state.sections[selectedBlockCoord.sec];
        if (!sec || !sec.columns_data[selectedBlockCoord.col]) return;
        var block = sec.columns_data[selectedBlockCoord.col][selectedBlockCoord.blockIdx];
        if (!block.data) block.data = {};
        block.data[key] = val;
        if (key === 'border_color') {
            compileElemBorder(block);
        }
        if (key === 'toggle_style') {
            state.mobile_toggle_style = val;
            var partSetting = document.getElementById('setting_mobile_toggle');
            if (partSetting) partSetting.value = val;
        }
        if (key === 'toggle_align') {
            state.mobile_toggle_align = val;
            var alignSetting = document.getElementById('setting_mobile_toggle_align');
            if (alignSetting) alignSetting.value = val;
        }
        renderCanvas();
        if (isEditorMobileDrawerOpen) {
            renderEditorMobileDrawerOverlay(true);
        }
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
        if (type === 'user_auth') defaultData = { login_text: 'Entrar', login_url: '/aluno/login', register_text: 'Cadastrar', register_url: '/aluno/cadastro', dashboard_text: '🎓 Área do Aluno', dashboard_url: '/aluno/painel', logout_text: 'Sair', logout_url: '/aluno/logout', align: 'right' };
        if (type === 'logo') defaultData = { text: 'DomainSystem', height: '36px' };
        if (type === 'divider') defaultData = { style: 'solid', height: '24px' };
        if (type === 'copyright') defaultData = { site_name: 'Domain-System' };
        if (type === 'button') defaultData = { label: 'Clique Aqui', action_type: 'link', url: '#', bg_color: 'var(--color-primary)', text_color: '#0f172a', hover_animation: 'lift', border_radius: '8px', align: 'left' };
        if (type === 'social_icons') defaultData = {
            networks: [
                { network: 'instagram', url: 'https://instagram.com' },
                { network: 'whatsapp', url: 'https://wa.me/5511999999999' },
                { network: 'youtube', url: 'https://youtube.com' }
            ],
            shape: 'circle',
            color_mode: 'official',
            hover_animation: 'lift',
            align: 'center',
            gap: '12px',
            icon_size: '20px',
            button_size: '40px'
        };
        if (type === 'nav_menu') defaultData = { toggle_style: state.mobile_toggle_style || 'classic', toggle_align: state.mobile_toggle_align || 'right', align: 'center' };
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
        renderSectionStyleControls(sec);
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
            renderSectionStyleControls(sec);
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

    /* ==========================================================================
       MODAL DE SELEÇÃO RÁPIDA DE BLOCOS (QUICK ADD BLOCK)
       ========================================================================== */
    var currentQuickCategory = 'all';

    function openAddBlockForCell(secIdx, colIdx) {
        activeSecIdx = secIdx;
        activeColIdx = colIdx;
        renderBlocksCatalog();
        openQuickBlockModal(secIdx, colIdx);
    }

    function openQuickBlockModal(secIdx, colIdx) {
        activeSecIdx = secIdx;
        activeColIdx = colIdx;

        var sec = state.sections[secIdx] || {};
        var presetInfo = presets[sec.layout_preset] || presets['1_col'];
        var colTitle = getColumnTitle(sec.layout_preset, colIdx, presetInfo.columns, sec);
        var secTitle = sec.title || ('Seção ' + (secIdx + 1));

        var titleEl = document.getElementById('quick_add_block_title');
        if (titleEl) {
            titleEl.innerHTML = 'Adicionar Bloco na <strong style="color:var(--primary);">' + escapeHtml(colTitle) + '</strong> (' + escapeHtml(secTitle) + ')';
        }

        currentQuickCategory = 'all';
        var searchInput = document.getElementById('quick_block_search_input');
        if (searchInput) {
            searchInput.value = '';
        }

        var pills = document.querySelectorAll('#quick_block_category_pills .quick-cat-btn');
        pills.forEach(function(p, i) { p.classList.toggle('active', i === 0); });

        renderQuickBlocksGrid('');

        var modal = document.getElementById('modalQuickAddBlock');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(function() {
                if (searchInput) searchInput.focus();
            }, 80);
        }
    }

    function closeQuickBlockModal() {
        var modal = document.getElementById('modalQuickAddBlock');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function setQuickBlockCategory(cat, btn) {
        currentQuickCategory = cat;
        var pills = document.querySelectorAll('#quick_block_category_pills .quick-cat-btn');
        pills.forEach(function(p) { p.classList.remove('active'); });
        if (btn) btn.classList.add('active');

        var searchInput = document.getElementById('quick_block_search_input');
        renderQuickBlocksGrid(searchInput ? searchInput.value : '');
    }

    function filterQuickBlocks(query) {
        renderQuickBlocksGrid(query);
    }

    function categorizeBlockType(type) {
        if (type === 'heading' || type === 'text' || type === 'paragraph' || type === 'callout') return 'text';
        if (type === 'image' || type === 'video' || type === 'logo' || type === 'pdf') return 'media';
        if (type === 'button' || type === 'cta' || type === 'auth') return 'action';
        if (type === 'card' || type === 'accordion' || type === 'social' || type === 'shortcode' || type === 'menu' || type === 'blog_posts') return 'interactive';
        return 'other';
    }

    function renderQuickBlocksGrid(query) {
        var grid = document.getElementById('quick_blocks_grid');
        if (!grid) return;
        grid.innerHTML = '';

        var q = (query || '').toLowerCase().trim();
        var curPartType = '<?= $part->getType() ?>';

        var filtered = blocksCatalog.filter(function(b) {
            if (b.allowed_types && b.allowed_types.indexOf(curPartType) === -1) return false;

            if (currentQuickCategory !== 'all') {
                var blockCat = categorizeBlockType(b.type);
                if (blockCat !== currentQuickCategory) return false;
            }

            if (q !== '') {
                var nameMatch = (b.name || '').toLowerCase().indexOf(q) !== -1;
                var descMatch = (b.desc || '').toLowerCase().indexOf(q) !== -1;
                var typeMatch = (b.type || '').toLowerCase().indexOf(q) !== -1;
                if (!nameMatch && !descMatch && !typeMatch) return false;
            }

            return true;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px;">' +
                             'Nenhum bloco encontrado para os filtros selecionados.' +
                             '</div>';
            return;
        }

        filtered.forEach(function(b) {
            var item = document.createElement('div');
            item.className = 'quick-block-card';
            item.onclick = function() {
                addBlockToCell(activeSecIdx, activeColIdx, b.type);
                closeQuickBlockModal();
            };
            item.innerHTML = '<div class="quick-block-icon">' + b.icon + '</div>' +
                             '<div class="quick-block-name">' + escapeHtml(b.name) + '</div>' +
                             '<div class="quick-block-desc">' + escapeHtml(b.desc) + '</div>';
            grid.appendChild(item);
        });
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
            mobile_toggle_align: state.mobile_toggle_align || 'right',
            is_default: !!state.is_default,
            assigned_theme: assignedTheme,
            assigned_template: document.getElementById('setting_assigned_template') ? document.getElementById('setting_assigned_template').value : ''
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
                var templateChoice = document.getElementById('setting_assigned_template') ? document.getElementById('setting_assigned_template').value : '';
                showToast('✓ ' + data.message);
                if (state.type === 'section') {
                    showSaveSuccessModal(state.title, '[section id="' + state.id + '"]', templateChoice);
                }
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
                iframe.srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{margin:0;padding:20px;background:#06090e;color:#fff;font-family:sans-serif;} a{cursor:pointer !important;}</style></head><body>' + html + getPreviewGuardScript() + '</body></html>';
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

        function copyStudioShortcode(btn) {
        var sc = (state.type === 'section') ? '[section id="' + state.id + '"]' : ('[theme_part id="' + state.id + '"]');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sc).then(function() {
                showToast('✓ Shortcode ' + sc + ' copiado! Cole na página que preferir (menu Páginas).');
                if (btn) {
                    var orig = btn.innerHTML;
                    btn.innerHTML = '✓ Copiado!';
                    btn.style.color = '#10b981';
                    setTimeout(function() { btn.innerHTML = orig; btn.style.color = '#38bdf8'; }, 2000);
                }
            });
        } else {
            prompt('Copie o shortcode da seção:', sc);
        }
    }

    function showSaveSuccessModal(title, shortcode, assignedTemplate) {
        var modal = document.getElementById('saveSuccessModal');
        if (!modal) return;

        document.getElementById('save_success_title').textContent = title || 'Seção';
        document.getElementById('save_success_sc_code').textContent = shortcode;
        
        var tNotice = document.getElementById('save_success_template_notice');
        if (assignedTemplate && assignedTemplate !== 'none') {
            tNotice.style.display = 'block';
            tNotice.innerHTML = '🎯 <strong>Atribuição Direta:</strong> Esta seção está vinculada ao template <code>' + escapeHtml(assignedTemplate) + '.php</code> e substituirá o conteúdo da página automaticamente!';
        } else {
            tNotice.style.display = 'none';
        }

        modal.style.display = 'flex';
    }

    function copyModalShortcode() {
        var sc = document.getElementById('save_success_sc_code').textContent;
        var btn = document.getElementById('btn_modal_copy_sc');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sc).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '✓ Copiado!';
                btn.style.background = '#10b981';
                btn.style.color = '#000';
                setTimeout(function() { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; }, 2000);
            });
        } else {
            prompt('Copie o shortcode abaixo:', sc);
        }
    }

    document.addEventListener('DOMContentLoaded', initStudio);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickBlockModal();
        }
    });
    </script>
    <!-- MODAL DE CONFIRMAÇÃO DE SALVAMENTO COM SHORTCODE E ATRIBUIÇÃO -->
    <div id="saveSuccessModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(5px); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; width: 90%; max-width: 520px; padding: 26px; box-shadow: 0 25px 50px rgba(0,0,0,0.6); text-align: center;">
            <div style="font-size: 38px; margin-bottom: 10px;">🎉</div>
            <h3 style="color: #fff; margin: 0 0 8px; font-size: 19px; font-weight: 700;">Seção Salva com Sucesso!</h3>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px; line-height: 1.5;">
                A seção <strong id="save_success_title" style="color:#fff;"></strong> foi gravada. Você pode copiá-la para usar em qualquer página do menu <strong>Páginas</strong>.
            </p>

            <div style="background: rgba(0,0,0,0.4); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px;">
                <code id="save_success_sc_code" style="font-size: 13px; color: #38bdf8; font-weight: 700; font-family: monospace; user-select: all;">[section id="..."]</code>
                <button type="button" class="btn btn-primary" id="btn_modal_copy_sc" onclick="copyModalShortcode()" style="font-size: 12px; padding: 7px 12px; white-space: nowrap; font-weight: 700;">
                    📋 Copiar Shortcode
                </button>
            </div>

            <div id="save_success_template_notice" style="display: none; font-size: 11px; color: #10b981; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); border-radius: 6px; padding: 8px 12px; margin-bottom: 16px; text-align: left; line-height: 1.4;"></div>

            <div style="font-size: 11px; color: var(--text-muted); line-height: 1.45; background: rgba(255,255,255,0.02); padding: 8px 12px; border-radius: 6px; border-left: 2px solid var(--accent-blue, #58a6ff); margin-bottom: 18px; text-align: left;">
                💡 <strong>Como usar:</strong> Copie esse shortcode e cole no conteúdo da sua página (no <em>menu Páginas</em> que você preferir) para exibir este layout.
            </div>

            <div style="display: flex; justify-content: center; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('saveSuccessModal').style.display='none'" style="padding: 8px 16px; font-size: 13px;">
                    Continuar Editando
                </button>
                <a href="<?= $baseUrl ?>/admin/builder" class="btn btn-primary" style="text-decoration: none; padding: 8px 18px; font-size: 13px; font-weight: 700;">
                    Ir para o Painel do Builder
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL DE SELEÇÃO RÁPIDA DE BLOCOS (QUICK ADD BLOCK) -->
    <div id="modalQuickAddBlock" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(6px); z-index: 10005; align-items: center; justify-content: center; padding: 20px;" onclick="if(event.target===this) closeQuickBlockModal();">
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 12px; width: 100%; max-width: 680px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 60px rgba(0,0,0,0.7); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.25);">
                <div>
                    <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px;">
                        <span>⚡</span>
                        <span id="quick_add_block_title">Adicionar Bloco na Célula</span>
                    </h3>
                    <p style="margin: 0; font-size: 12px; color: var(--text-muted, #8c8f94);">Clique em qualquer bloco abaixo para inserir instantaneamente nesta divisão.</p>
                </div>
                <button type="button" onclick="closeQuickBlockModal()" style="background: transparent; border: none; color: #8c8f94; font-size: 22px; cursor: pointer; padding: 4px 8px; line-height: 1; border-radius: 4px;" title="Fechar (Esc)">&times;</button>
            </div>

            <!-- BUSCA E CATEGORIAS -->
            <div style="padding: 14px 22px 10px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="position: relative; margin-bottom: 10px;">
                    <input type="text" id="quick_block_search_input" placeholder="🔍 Digite para filtrar blocos (ex: botão, imagem, texto, vídeo...)" oninput="filterQuickBlocks(this.value)" style="width: 100%; padding: 10px 14px; border-radius: 6px; border: 1px solid #4a545a; background: #0b0c10; color: #fff; font-size: 13px; box-sizing: border-box; outline: none;">
                </div>
                <div id="quick_block_category_pills" style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="quick-cat-btn active" onclick="setQuickBlockCategory('all', this)">Todos</button>
                    <button type="button" class="quick-cat-btn" onclick="setQuickBlockCategory('text', this)">📝 Texto &amp; Títulos</button>
                    <button type="button" class="quick-cat-btn" onclick="setQuickBlockCategory('media', this)">🖼️ Mídia &amp; Imagens</button>
                    <button type="button" class="quick-cat-btn" onclick="setQuickBlockCategory('action', this)">⚡ Ações &amp; Botões</button>
                    <button type="button" class="quick-cat-btn" onclick="setQuickBlockCategory('interactive', this)">🎯 Interativo &amp; Cards</button>
                </div>
            </div>

            <!-- GRADE DE BLOCOS -->
            <div id="quick_blocks_grid" style="padding: 16px 22px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; max-height: calc(85vh - 200px);">
            </div>

            <div style="padding: 12px 22px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.25); display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: var(--text-muted, #8c8f94);">
                <span>💡 <em>Dica: Você também pode arrastar blocos da barra lateral diretamente para as divisórias.</em></span>
                <button type="button" onclick="closeQuickBlockModal()" class="btn btn-secondary" style="padding: 6px 14px; font-size: 12px;">Fechar</button>
            </div>
        </div>
    </div>
</body>
</html>
