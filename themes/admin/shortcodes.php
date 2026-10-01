<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px; color: var(--text-main);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-green);">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
        </svg>
        Catálogo de Shortcodes
    </h1>
    <p style="color: var(--text-muted);">Estes são os blocos (componentes) disponibilizados pelos plugins atualmente ativos no sistema. Você pode usá-los em qualquer tema.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 30px;">
        <?php if (empty($shortcodes)): ?>
            <div style="background: var(--bg-panel); padding: 30px; border-radius: 12px; border: 1px solid var(--border); text-align: center; color: var(--text-muted); grid-column: 1 / -1;">
                Nenhum shortcode registrado no sistema.
            </div>
        <?php else: ?>
            <?php foreach ($shortcodes as $tag => $info): ?>
                <div style="background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column;" onmouseover="this.style.borderColor='var(--accent-green)';" onmouseout="this.style.borderColor='var(--border)';">
                    <div style="background: var(--bg-deep); padding: 15px 20px; border-bottom: 1px solid var(--border); font-weight: bold; color: var(--text-main); display: flex; align-items: center; justify-content: space-between;">
                        <code style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; color: var(--accent-blue); font-size: 14px;">&#91;<?= htmlspecialchars($tag) ?>&#93;</code>
                        <button onclick="copyShortcode('<?= htmlspecialchars($tag) ?>')" class="btn" style="background: rgba(0, 210, 132, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 210, 132, 0.3); padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='var(--accent-green)'; this.style.color='#0b0c10';" onmouseout="this.style.background='rgba(0, 210, 132, 0.1)'; this.style.color='var(--accent-green)';">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 4px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copiar
                        </button>
                    </div>
                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                        <p style="color: var(--text-muted); margin-top: 0; margin-bottom: 20px; font-size: 14px; line-height: 1.6;">
                            <?= htmlspecialchars($info['description'] ?: 'Sem descrição fornecida.') ?>
                        </p>
                        
                        <div style="flex: 1;">
                            <?php if (!empty($info['attributes'])): ?>
                                <h4 style="margin: 0 0 12px 0; font-size: 12px; color: var(--accent-orange); text-transform: uppercase; letter-spacing: 0.5px;">Atributos Aceitos</h4>
                                <ul style="margin: 0; padding-left: 20px; color: var(--text-main); font-size: 13px; line-height: 1.8;">
                                    <?php foreach ($info['attributes'] as $attrName => $attrDesc): ?>
                                        <li style="margin-bottom: 5px;"><strong style="color: var(--accent-blue);"><?= htmlspecialchars($attrName) ?></strong> <span style="color: var(--text-muted);">&mdash; <?= htmlspecialchars($attrDesc) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div style="font-size: 13px; color: var(--text-muted); font-style: italic; opacity: 0.7; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 6px; border: 1px dashed rgba(255,255,255,0.1); text-align: center;">Não aceita parâmetros opcionais.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1);">
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 8px; font-weight: bold; letter-spacing: 1px;">EXEMPLO DE USO:</span>
                            <code style="background: rgba(0,0,0,0.5); padding: 12px; border-radius: 6px; display: block; font-size: 13px; color: #fff; border: 1px solid var(--border); font-family: monospace;">
                                <span style="color: var(--accent-blue);">&#91;<?= htmlspecialchars($tag) ?></span><?php if(!empty($info['attributes'])) { echo ' <span style="color: var(--accent-green);">' . key($info['attributes']) . '</span><span style="color: var(--text-muted);">="</span><span style="color: var(--accent-orange);">valor</span><span style="color: var(--text-muted);">"</span>'; } ?><span style="color: var(--accent-blue);">&#93;</span>
                            </code>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function copyShortcode(tag) {
    var text = '[' + tag + ']';
    navigator.clipboard.writeText(text).then(function() {
        if(typeof DS !== 'undefined' && DS.toast) {
            DS.toast.success('Copiado', 'Shortcode ' + text + ' copiado com sucesso!');
        } else {
            alert('Shortcode ' + text + ' copiado para a área de transferência!');
        }
    }, function(err) {
        if(typeof DS !== 'undefined' && DS.toast) {
            DS.toast.error('Erro', 'Não foi possível copiar o shortcode.');
        } else {
            alert('Erro ao copiar shortcode.');
        }
    });
}
</script>
