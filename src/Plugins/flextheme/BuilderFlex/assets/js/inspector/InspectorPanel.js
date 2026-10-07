/**
 * InspectorPanel — o "Object Inspector": lista as propriedades do componente
 * selecionado, agrupadas por categoria. Só desenha; quem altera o valor é o
 * Editor (via evento 'input' delegado no corpo do painel).
 */
FlexBuilder.InspectorPanel = class InspectorPanel {
    static CATEGORIES = {
        'Posição e Tamanho':      ['left', 'top', 'width', 'height', 'margin', 'padding'],
        'Flex e Layout (Grid)':   ['display', 'flexDirection', 'justifyContent', 'alignItems', 'gap'],
        'Estética (Visual)':      ['backgroundColor', 'border', 'borderRadius', 'boxShadow', 'opacity', 'zIndex', 'color'],
        'Tipografia e Conteúdo':  ['text', 'fontSize', 'fontFamily', 'fontWeight', 'textAlign', 'src', 'link', 'objectFit']
    };

    constructor(bodyElement) {
        this.body = bodyElement;
    }

    render(node) {
        if (!node) {
            this.body.innerHTML = '<div class="inspector-empty">Selecione um objeto no form.</div>';
            return;
        }

        const esc = FlexBuilder.escape;
        const categorized = new Set(Object.values(InspectorPanel.CATEGORIES).flat());

        let html = `
            <div class="inspector-title">
                <strong>${esc(node.props.name)}</strong> <span class="inspector-type">${esc(node.type)}</span>
                <div class="inspector-id">ID: ${esc(node.id)}</div>
                <button class="btn btn-danger-soft" data-action="delete-node">
                    <i class="fas fa-trash"></i> Delete (Del)
                </button>
            </div>`;

        for (const [categoryName, keys] of Object.entries(InspectorPanel.CATEGORIES)) {
            const fields = keys
                .filter(key => node.props[key] !== undefined)
                .map(key => FlexBuilder.FieldFactory.create(key, node.props[key]))
                .join('');
            if (fields) html += this.section(categoryName, fields);
        }

        const extra = Object.keys(node.props)
            .filter(key => key !== 'name' && !categorized.has(key))
            .map(key => FlexBuilder.FieldFactory.create(key, node.props[key]))
            .join('');
        if (extra) html += this.section('Extra', extra);

        this.body.innerHTML = html;
    }

    section(title, fieldsHtml) {
        return `<div class="inspector-section">
                    <div class="inspector-section-title">${FlexBuilder.escape(title)}</div>
                    ${fieldsHtml}
                </div>`;
    }

    /** Sincroniza campos já desenhados (ex.: left/top durante o arraste). */
    syncField(key, value) {
        const input = this.body.querySelector(`[data-prop="${key}"]`);
        if (input) input.value = value;
    }
};
