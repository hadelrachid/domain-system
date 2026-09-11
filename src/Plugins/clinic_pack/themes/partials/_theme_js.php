<?php
/**
 * Partial: _theme_js.php
 * Inclua DENTRO de <script>. Quem inclui define $themeColorsJson.
 * Exporta: previewTheme(key) + alias updateLiveTheme(key)
 */
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
?>
const _themeColors = <?= $themeColorsJson ?? '{}' ?>;
function previewTheme(key) {
    const c = _themeColors[key];
    if (!c) return;
    const r = document.documentElement.style;
    r.setProperty('--primary',        c.primary);
    r.setProperty('--primary-hover',  c.hover);
    r.setProperty('--primary-light',  c.light);
    r.setProperty('--primary-border', c.border);
    r.setProperty('--bg-body',        c.bg);
    r.setProperty('--bg-card',        c.card);
    r.setProperty('--text-main',      c.text);
    r.setProperty('--text-muted',     c.muted);
    document.querySelectorAll('[id^="swatch-"]').forEach(s => s.style.border = '3px solid transparent');
    const sel = document.getElementById('swatch-' + key);
    if (sel) sel.style.border = '3px solid #1e293b';
}
/* Alias para retrocompatibilidade com cockpit_doctor */
const updateLiveTheme = previewTheme;
