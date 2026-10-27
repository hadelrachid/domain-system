<?php
/**
 * View: Editor Visual de Design Tokens (Paleta de Cores e Tipografia)
 *
 * @var array $palette
 * @var array $typography
 * @var string $cssVars
 * @var string $baseUrl
 * @var ?\DomainSystem\Core\Contracts\SessionManagerInterface $session
 */
?>

<div class="wrap" style="max-width: 1200px; margin: 0 auto; padding: 25px 20px;">
    <!-- HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <a href="<?= $baseUrl ?>/admin/builder" style="color: var(--text-muted); text-decoration: none; font-size: 14px;">← Voltar ao Flex Builder</a>
                <span style="color: var(--text-muted);">/</span>
                <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #fff;">Design Tokens Globais</h1>
            </div>
            <p style="margin: 0; font-size: 14px; color: var(--text-muted, #8b949e);">
                Configure a paleta de cores e tipografia mestre. Todos os headers, footers e blocos herdam essas variáveis automaticamente.
            </p>
        </div>

        <button type="button" onclick="document.getElementById('tokensForm').submit();" class="btn button-primary" style="background: #00d284; color: #000; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            💾 Salvar Tokens Globais
        </button>
    </div>

    <?php 
        $flash = $session ? $session->getFlash() : null;
        if ($flash && !empty($flash['msg'])): 
            $isSuccess = ($flash['type'] ?? 'info') === 'success';
            $bgColor = $isSuccess ? 'rgba(0, 210, 132, 0.15)' : 'rgba(239, 68, 68, 0.15)';
            $borderColor = $isSuccess ? '#00d284' : '#ef4444';
            $textColor = $isSuccess ? '#00d284' : '#f87171';
            $icon = $isSuccess ? '✓' : '✕';
    ?>
        <div style="background: <?= $bgColor ?>; border: 1px solid <?= $borderColor ?>; color: <?= $textColor ?>; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            <?= $icon ?> <?= htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form id="tokensForm" method="POST" action="<?= $baseUrl ?>/admin/builder/tokens/save">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            
            <!-- COLUNA ESQUERDA: CONFIGURAÇÕES -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- PALETA DE CORES -->
                <div style="background: var(--bg-panel, #161b22); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 22px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">
                        <span style="font-size: 18px;">🎨</span>
                        <h2 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">Paleta de Cores do Sistema</h2>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php
                            $colorFields = [
                                'primary'     => ['label' => 'Cor Primária (Ações, Destaques)', 'desc' => 'Cor de botões de conversão e elementos principais.'],
                                'secondary'   => ['label' => 'Cor Secundária (Subtítulos, Contrastes)', 'desc' => 'Destaques complementares.'],
                                'tertiary'    => ['label' => 'Cor Terciária (Acentos, Badges)', 'desc' => 'Pílulas, tags e elementos secundários.'],
                                'background'  => ['label' => 'Fundo Global (Canvas)', 'desc' => 'Cor do fundo principal do site.'],
                                'surface'     => ['label' => 'Superfície (Cards e Barras)', 'desc' => 'Fundo de cabeçalhos, cards e divisórias.'],
                                'text_main'   => ['label' => 'Texto Principal', 'desc' => 'Cor do texto de leitura de alta legibilidade.'],
                                'text_muted'  => ['label' => 'Texto Secundário / Muted', 'desc' => 'Legendas, metadados e rodapé.'],
                            ];
                        ?>

                        <?php foreach ($colorFields as $key => $meta): ?>
                            <?php $val = $palette[$key] ?? '#ffffff'; ?>
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                                    <?= htmlspecialchars($meta['label']) ?>
                                </label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="color" 
                                           id="color_picker_<?= $key ?>" 
                                           value="<?= htmlspecialchars($val) ?>" 
                                           onchange="updateColorText('<?= $key ?>', this.value)"
                                           style="width: 44px; height: 36px; padding: 0; border: 1px solid var(--border); border-radius: 4px; background: transparent; cursor: pointer;">
                                    
                                    <input type="text" 
                                           name="colors[<?= $key ?>]" 
                                           id="color_text_<?= $key ?>" 
                                           value="<?= htmlspecialchars($val) ?>" 
                                           oninput="updateColorPicker('<?= $key ?>', this.value)"
                                           style="flex-grow: 1; background: var(--bg-deep, #0d1117); color: #fff; border: 1px solid var(--border, #30363d); padding: 7px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                </div>
                                <span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($meta['desc']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TIPOGRAFIA GLOBAL -->
                <div style="background: var(--bg-panel, #161b22); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 22px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">
                        <span style="font-size: 18px;">🔤</span>
                        <h2 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">Tipografia Global</h2>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                                Fonte Principal (Corpo do Texto)
                            </label>
                            <input type="text" 
                                   name="typography[primary_font]" 
                                   id="input_font_primary" 
                                   value="<?= htmlspecialchars($typography['primary_font'] ?? 'Inter') ?>" 
                                   oninput="updateTypographyPreview()"
                                   style="width: 100%; box-sizing: border-box; background: var(--bg-deep, #0d1117); color: #fff; border: 1px solid var(--border, #30363d); padding: 8px 12px; border-radius: 4px; font-size: 13px;">
                            <span style="font-size: 11px; color: var(--text-muted);">Sugestões: <code>Inter</code>, <code>Roboto</code>, <code>Open Sans</code>, <code>system-ui</code></span>
                        </div>

                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                                Fonte Secundária (Títulos & Destaques)
                            </label>
                            <input type="text" 
                                   name="typography[secondary_font]" 
                                   id="input_font_secondary" 
                                   value="<?= htmlspecialchars($typography['secondary_font'] ?? 'Montserrat') ?>" 
                                   oninput="updateTypographyPreview()"
                                   style="width: 100%; box-sizing: border-box; background: var(--bg-deep, #0d1117); color: #fff; border: 1px solid var(--border, #30363d); padding: 8px 12px; border-radius: 4px; font-size: 13px;">
                            <span style="font-size: 11px; color: var(--text-muted);">Sugestões: <code>Montserrat</code>, <code>Poppins</code>, <code>Fira Code</code>, <code>Outfit</code></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUNA DIREITA: PRÉ-VISUALIZAÇÃO AO VIVO EM TEMPO REAL -->
            <div>
                <div style="position: sticky; top: 20px; background: var(--bg-panel, #161b22); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 22px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #00d284; display: inline-block; box-shadow: 0 0 8px #00d284;"></span>
                            <h2 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">Simulador Visual de Tokens</h2>
                        </div>
                        <span style="font-size: 11px; color: var(--text-muted);">Atualização em tempo real</span>
                    </div>

                    <!-- CONTAINER DO PREVIEW DINÂMICO -->
                    <div id="tokens_preview_card" style="border-radius: 8px; padding: 25px; transition: all 0.2s ease; border: 1px solid rgba(255,255,255,0.1);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <div id="preview_logo" style="font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">
                                Domain<span id="preview_logo_primary">System</span>
                            </div>
                            <span id="preview_badge" style="font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase;">
                                Pro Plan
                            </span>
                        </div>

                        <h3 id="preview_heading" style="margin: 0 0 10px; font-size: 22px; font-weight: 700; line-height: 1.25;">
                            Construa Interfaces Rápidas com Flex Builder
                        </h3>

                        <p id="preview_text_main" style="margin: 0 0 14px; font-size: 14px; line-height: 1.5;">
                            Este bloco reflete a cor primária, a fonte principal e o contraste do texto de alta legibilidade conforme configurado nos tokens.
                        </p>

                        <p id="preview_text_muted" style="margin: 0 0 20px; font-size: 12px;">
                            Texto auxiliar configurado com <code>--color-text-muted</code>.
                        </p>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" id="preview_btn_primary" style="padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer;">
                                Botão Primário ↗
                            </button>
                            <button type="button" id="preview_btn_secondary" style="padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                Botão Secundário
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function updateColorText(key, val) {
    document.getElementById('color_text_' + key).value = val;
    applyPreviewTokens();
}

function updateColorPicker(key, val) {
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        document.getElementById('color_picker_' + key).value = val;
    }
    applyPreviewTokens();
}

function updateTypographyPreview() {
    applyPreviewTokens();
}

function applyPreviewTokens() {
    var primary    = document.getElementById('color_text_primary').value;
    var secondary  = document.getElementById('color_text_secondary').value;
    var tertiary   = document.getElementById('color_text_tertiary').value;
    var background = document.getElementById('color_text_background').value;
    var surface    = document.getElementById('color_text_surface').value;
    var textMain   = document.getElementById('color_text_text_main').value;
    var textMuted  = document.getElementById('color_text_text_muted').value;

    var fontPrimary   = document.getElementById('input_font_primary').value || 'sans-serif';
    var fontSecondary = document.getElementById('input_font_secondary').value || 'sans-serif';

    var card = document.getElementById('tokens_preview_card');
    card.style.setProperty('background-color', surface, 'important');
    card.style.setProperty('font-family', fontPrimary + ', sans-serif', 'important');

    var previewHeading = document.getElementById('preview_heading');
    previewHeading.style.setProperty('color', textMain, 'important');
    previewHeading.style.setProperty('font-family', fontSecondary + ', sans-serif', 'important');

    document.getElementById('preview_text_main').style.setProperty('color', textMain, 'important');
    document.getElementById('preview_text_muted').style.setProperty('color', textMuted, 'important');

    document.getElementById('preview_logo').style.setProperty('color', textMain, 'important');
    document.getElementById('preview_logo_primary').style.setProperty('color', primary, 'important');

    var badge = document.getElementById('preview_badge');
    badge.style.setProperty('background-color', tertiary, 'important');
    badge.style.setProperty('color', '#0f172a', 'important');

    var btnPri = document.getElementById('preview_btn_primary');
    btnPri.style.setProperty('background-color', primary, 'important');
    btnPri.style.setProperty('color', '#0f172a', 'important');

    var btnSec = document.getElementById('preview_btn_secondary');
    btnSec.style.setProperty('background-color', 'transparent', 'important');
    btnSec.style.setProperty('color', primary, 'important');
    btnSec.style.setProperty('border', '1px solid ' + primary, 'important');
}

document.addEventListener('DOMContentLoaded', applyPreviewTokens);
</script>
