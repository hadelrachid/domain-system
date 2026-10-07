/**
 * FieldFactory — escolhe o campo HTML certo para cada propriedade.
 *
 * Cada "estratégia" decide se atende a propriedade (supports) e a desenha
 * (render). Para suportar um novo tipo de campo, basta chamar
 * FieldFactory.register(...) — sem editar as estratégias existentes (OCP).
 */
FlexBuilder.FieldFactory = (function () {
    const esc = FlexBuilder.escape;

    const FLEX_ALIGN = [
        ['flex-start', 'flex-start (Início)'],
        ['center', 'center (Centro)'],
        ['flex-end', 'flex-end (Fim)'],
        ['space-between', 'space-between'],
        ['stretch', 'stretch (Esticar)']
    ];

    const SELECT_OPTIONS = {
        display: [['block', 'block'], ['flex', 'flex'], ['none', 'none']],
        flexDirection: [['row', 'row (Horizontal)'], ['column', 'column (Vertical)']],
        justifyContent: FLEX_ALIGN,
        alignItems: FLEX_ALIGN
    };

    const group = (key, control) =>
        `<div class="prop-group"><label>${esc(key)}</label>${control}</div>`;

    /** Ordem importa: a primeira estratégia que "suportar" a propriedade vence. */
    const strategies = [
        {   // <select> para propriedades com opções fixas
            supports: key => key in SELECT_OPTIONS,
            render: (key, val) => group(key,
                `<select class="prop-input" data-prop="${esc(key)}">`
                + SELECT_OPTIONS[key].map(([value, label]) =>
                    `<option value="${esc(value)}" ${val === value ? 'selected' : ''}>${esc(label)}</option>`).join('')
                + `</select>`)
        },
        {   // <textarea> para textos longos / URLs
            supports: key => key === 'text' || key === 'src',
            render: (key, val) => group(key,
                `<textarea class="prop-input" rows="2" data-prop="${esc(key)}">${esc(val)}</textarea>`)
        },
        {   // <input type="color">
            supports: key => key.toLowerCase().includes('color'),
            render: (key, val) => group(key,
                `<input type="color" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}">`)
        },
        {   // <input type="number">
            supports: (key, val) => typeof val === 'number',
            render: (key, val) => {
                const extra = key === 'opacity' ? ' min="0" max="1" step="0.1"' : '';
                return group(key,
                    `<input type="number" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}"${extra}>`);
            }
        },
        {   // <input type="text"> (padrão)
            supports: () => true,
            render: (key, val) => group(key,
                `<input type="text" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}">`)
        }
    ];

    return {
        /** Estratégias registradas depois têm prioridade sobre as padrão. */
        register(strategy) {
            strategies.unshift(strategy);
        },

        create(key, val) {
            return strategies.find(s => s.supports(key, val)).render(key, val);
        }
    };
})();
