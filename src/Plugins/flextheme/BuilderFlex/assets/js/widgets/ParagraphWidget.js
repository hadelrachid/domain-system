/**
 * ParagraphWidget — Container de parágrafo com editor WYSIWYG.
 */
BuilderFlex.ParagraphWidget = class ParagraphWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ParagraphWidget';

        Object.assign(this.props, {
            name: 'Parágrafo',
            html: '<p>Escreva seu texto aqui. Use a barra para formatar.</p>',
            color: '#333333',
            fontSize: 16,
            fontFamily: 'Inter, sans-serif',
            fontWeight: 'normal',
            lineHeight: 1.6,
            textAlign: 'left',
            padding: '12px',
            backgroundColor: 'transparent'
        });
    }

    tagName() { return 'div'; }

    extraStyles(bp = 'base') {
        const get = key => this.getProp(key, bp);
        return `
            color: ${get('color')};
            font-size: ${get('fontSize')}px;
            font-family: ${get('fontFamily')};
            font-weight: ${get('fontWeight')};
            line-height: ${get('lineHeight')};
            text-align: ${get('textAlign')};
        `;
    }

    content() {
        const html = this.props.html || '';
        const isEditor = this._isInEditorMode();

        if (isEditor) {
            return `
                <div class="paragraph-toolbar" contenteditable="false">
                    <button type="button" data-cmd="bold" title="Negrito"><i class="fas fa-bold"></i></button>
                    <button type="button" data-cmd="italic" title="Itálico"><i class="fas fa-italic"></i></button>
                    <button type="button" data-cmd="underline" title="Sublinhado"><i class="fas fa-underline"></i></button>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-cmd="formatBlock" data-value="h1" title="Título 1">H1</button>
                    <button type="button" data-cmd="formatBlock" data-value="h2" title="Título 2">H2</button>
                    <button type="button" data-cmd="formatBlock" data-value="p" title="Parágrafo">¶</button>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-cmd="insertUnorderedList" title="Lista com marcadores"><i class="fas fa-list-ul"></i></button>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-cmd="createLink" title="Inserir link"><i class="fas fa-link"></i></button>
                    <button type="button" data-cmd="removeFormat" title="Limpar formatação"><i class="fas fa-eraser"></i></button>
                </div>
                <div class="paragraph-content"
                     contenteditable="true"
                     data-node-id="${this.id}"
                     spellcheck="true">${html}</div>
            `;
        }

        return `<div class="paragraph-content">${html}</div>`;
    }

    _isInEditorMode() {
        return window.BuilderFlex && window.BuilderFlex._editingMode !== false;
    }
};

BuilderFlex.WidgetRegistry.register('ParagraphWidget', BuilderFlex.ParagraphWidget, {
    label: 'Parágrafo (Texto Rico)',
    icon: 'fa-paragraph',
    palette: true,
    category: 'Conteúdo'
});
