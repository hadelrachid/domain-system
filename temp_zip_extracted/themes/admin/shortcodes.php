<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 24px;">🧩</span>
        Catálogo de Shortcodes
    </h1>
    <p>Estes são os blocos (componentes) disponibilizados pelos plugins atualmente ativos no sistema. Você pode usá-los em qualquer tema.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
        <?php if (empty($shortcodes)): ?>
            <div style="background: #fff; padding: 30px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center; color: #64748b; grid-column: 1 / -1;">
                Nenhum shortcode registrado.
            </div>
        <?php else: ?>
            <?php foreach ($shortcodes as $tag => $info): ?>
                <div style="background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #1e293b; display: flex; align-items: center; justify-content: space-between;">
                        <code>&#91;<?= htmlspecialchars($tag) ?>&#93;</code>
                        <button onclick="copyShortcode('<?= htmlspecialchars($tag) ?>')" class="btn" style="background: #e2e8f0; color: #334155; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">📋 Copiar</button>
                    </div>
                    <div style="padding: 20px;">
                        <p style="color: #475569; margin-top: 0; margin-bottom: 15px; font-size: 14px; min-height: 40px;">
                            <?= htmlspecialchars($info['description'] ?: 'Sem descrição fornecida.') ?>
                        </p>
                        
                        <?php if (!empty($info['attributes'])): ?>
                            <h4 style="margin: 0 0 10px 0; font-size: 13px; color: #1e293b; text-transform: uppercase;">Atributos Aceitos</h4>
                            <ul style="margin: 0; padding-left: 20px; color: #64748b; font-size: 13px;">
                                <?php foreach ($info['attributes'] as $attrName => $attrDesc): ?>
                                    <li style="margin-bottom: 5px;"><strong><?= htmlspecialchars($attrName) ?></strong>: <?= htmlspecialchars($attrDesc) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">Não aceita atributos opcionais.</span>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e2e8f0;">
                            <span style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 5px;">EXEMPLO DE USO:</span>
                            <code style="background: #f1f5f9; padding: 8px; border-radius: 4px; display: block; font-size: 13px; color: #0f172a;">
                                &#91;<?= htmlspecialchars($tag) ?><?php if(!empty($info['attributes'])) { echo ' ' . key($info['attributes']) . '="valor"'; } ?>&#93;
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
        alert('Shortcode ' + text + ' copiado para a área de transferência!');
    }, function(err) {
        alert('Erro ao copiar shortcode.');
    });
}
</script>
