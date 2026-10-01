<?php
$file = 'D:/xampp/htdocs/domain-system/themes/admin/plugins.php';
$content = file_get_contents($file);

// Add styles
$styles = <<<EOT
<style>
    .plugin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; margin-bottom: 30px; }
    .plugin-table th, .plugin-table td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: top; }
    .plugin-table th { background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; }
    .plugin-table tr:last-child td { border-bottom: none; }
    .plugin-row-active td { background-color: rgba(88,166,255,0.05); }
    .plugin-row-active td:first-child { border-left: 4px solid var(--accent-blue); }
    .plugin-row-disarmed td { background-color: rgba(245,110,40,0.05); }
    
    .core-toggle-btn { background: transparent; border: 1px dashed var(--border); color: var(--text-muted); padding: 10px 20px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-bottom: 20px; transition: all 0.3s; }
    .core-toggle-btn:hover { background: rgba(0,0,0,0.2); color: #fff; }
</style>

<?php 
    \$appPlugins = array_filter(\$plugins, fn(\$p) => !\$p['is_core']);
    \$corePlugins = array_filter(\$plugins, fn(\$p) => \$p['is_core']);
?>

<h2 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Aplicativos e Extensões</h2>
<p style="color: var(--text-muted); margin-bottom: 20px;">Plugins públicos e integrações de terceiros.</p>

<table class="plugin-table">
    <thead>
        <tr>
            <th style="width: 25%;">Aplicativo / Módulo</th>
            <th style="width: 10%;">Versão</th>
            <th style="width: 45%;">Descrição</th>
            <th style="width: 20%;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty(\$appPlugins)): ?>
            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">Nenhum aplicativo instalado no momento.</td></tr>
        <?php else: ?>
        <?php foreach (\$appPlugins as \$plugin): ?>
EOT;

$content = preg_replace('/<style>.*?\.plugin-table { width: 100%;.*?<tbody>\s*<\?php foreach \(\$plugins as \$plugin\): \?>/is', $styles, $content);

// Extract the row content
// Use regex to find the inner row which starts with '<tr class="<'.'?= $plugin['is_active']' and ends with '<'.'?php endforeach; ?'.'>' JUST before '</tbody>'
preg_match('/<tr class="<'.'\?= \$plugin\[\'is_active\'\].*?(?=<'.'\?php endforeach; \?'.'>\s*<\/tbody>)/is', $content, $matches);
$rowContent = $matches[0];

$footer = <<<EOT
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<button class="core-toggle-btn" onclick="document.getElementById('core-plugins-container').style.display = (document.getElementById('core-plugins-container').style.display === 'none') ? 'block' : 'none';">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
    Exibir/Ocultar Módulos Administrativos (Core)
</button>

<div id="core-plugins-container" style="display: none;">
    <h2 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Infraestrutura e Painéis (Core)</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Serviços privados e administrativos. A desativação comprometerá o sistema.</p>

    <table class="plugin-table" style="opacity: 0.85;">
        <thead>
            <tr>
                <th style="width: 25%;">Componente Core</th>
                <th style="width: 10%;">Versão</th>
                <th style="width: 45%;">Descrição</th>
                <th style="width: 20%;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (\$corePlugins as \$plugin): ?>
EOT;

// Replace the end
$content = preg_replace('/<'.'\?php endforeach; \?'.'>\s*<\/tbody>\s*<\/table>/is', $footer . "\n" . $rowContent . "\n" . '        <?php endforeach; ?>' . "\n" . '    </tbody>' . "\n" . '</table>' . "\n" . '</div>', $content);

file_put_contents($file, $content);
echo "View reescrita com sucesso!\n";
