/**
 * ShortcodeWidget — Representação visual (placeholder) de um shortcode
 * no editor. NÃO executa o shortcode — só mostra um preview esquemático.
 */
BuilderFlex.ShortcodeWidget = class ShortcodeWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ShortcodeWidget';

        Object.assign(this.props, {
            name: 'Shortcode',
            tag: 'meu_shortcode',    // A tag do shortcode (ex: carrossel)
            attrs: {},               // Atributos do shortcode
            previewIcon: 'fa-puzzle-piece',
            previewLabel: 'Shortcode Dinâmico',
            
            // Estilo padrão do placeholder no canvas
            width: '100%',
            height: '150px',
            backgroundColor: 'rgba(0, 229, 255, 0.05)',
            border: '2px dashed rgba(0, 229, 255, 0.5)',
            borderRadius: 6,
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            gap: '8px',
            color: '#00e5ff'
        });
    }

    tagName() { return 'div'; }

    content() {
        const esc = BuilderFlex.escape;
        const attrsList = Object.entries(this.props.attrs || {})
            .map(([k, v]) => `<code>${esc(k)}="${esc(v)}"</code>`)
            .join(' ');

        return `
            <div style="text-align: center; font-family: sans-serif; pointer-events: none;">
                <i class="fas ${esc(this.props.previewIcon)}" style="font-size: 32px; opacity: 0.7; margin-bottom: 8px;"></i>
                <div style="font-size: 14px; font-weight: bold; color: #fff;">${esc(this.props.previewLabel)}</div>
                <div style="font-family: monospace; font-size: 11px; background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 4px; margin-top: 8px;">
                    [${esc(this.props.tag)}${attrsList ? ' ' + attrsList : ''}]
                </div>
            </div>
        `;
    }
};

BuilderFlex.WidgetRegistry.register('ShortcodeWidget', BuilderFlex.ShortcodeWidget, {
    label: 'Shortcode Genérico',
    icon: 'fa-code',
    palette: true,
    category: 'Dinâmico'
});
