/**
 * FieldFactory — Cria o HTML de cada campo.
 */
BuilderFlex.FieldFactory = (function () {
    const esc = BuilderFlex.escape;

    const FLEX_ALIGN = [
        ['flex-start', 'Início'], ['center', 'Centro'],
        ['flex-end', 'Fim'], ['space-between', 'Espaçado'],
        ['stretch', 'Esticar']
    ];

    const SELECT_OPTIONS = {
        position: [['absolute', 'absolute'], ['relative', 'relative'], ['static', 'static']],
        display: [['block', 'block'], ['flex', 'flex'], ['grid', 'grid'], ['none', 'none']],
        layout: [['absolute', 'Absolute'], ['flow', 'Flow'], ['grid', 'Grid']],
        flexDirection: [['row', 'Linha'], ['column', 'Coluna']],
        justifyContent: FLEX_ALIGN,
        alignItems: FLEX_ALIGN,
        overflow: [['visible', 'visible'], ['hidden', 'hidden'], ['auto', 'auto']],
        textAlign: [['left', 'Esquerda'], ['center', 'Centro'], ['right', 'Direita']]
    };

    const group = (key, control, def = {}) => {
        const isResponsive = def.responsive || false;
        const hasOverride = def.hasOverride || false;
        
        let indicatorHtml = '';
        if (isResponsive) {
            if (hasOverride) {
                indicatorHtml = '<span class="bp-indicator override" title="Sobrescreve neste breakpoint" style="background: var(--accent-neon); box-shadow: 0 0 5px var(--accent-neon);"></span>';
            } else {
                indicatorHtml = '<span class="bp-indicator inherit" title="Herdado" style="background: rgba(255,255,255,0.2);"></span>';
            }
        }

        return `
        <div class="prop-group ${isResponsive ? 'prop-responsive' : ''}">
            <label>
                ${esc(key)}
                ${indicatorHtml}
            </label>
            ${control}
        </div>`;
    };

    const strategies = [
        {
            supports: (key) => key in SELECT_OPTIONS,
            render: (key, val, def) => group(key,
                `<select class="prop-input" data-prop="${esc(key)}">`
                + SELECT_OPTIONS[key].map(([v, l]) =>
                    `<option value="${esc(v)}" ${val == v ? 'selected' : ''}>${esc(l)}</option>`
                ).join('')
                + `</select>`, def)
        },
        {
            supports: (key) => key === 'hidden',
            render: (key, val, def) => group(key,
                `<input type="checkbox" class="prop-input" data-prop="${esc(key)}" ${val ? 'checked' : ''}>`,
                def)
        },
        {
            supports: (key) => key === 'text' || key === 'src',
            render: (key, val, def) => group(key,
                `<textarea class="prop-input" rows="2" data-prop="${esc(key)}">${esc(val)}</textarea>`,
                def)
        },
        {
            supports: (key) => key.toLowerCase().includes('color'),
            render: (key, val, def) => group(key,
                `<div style="display:flex; gap:5px;">
                    <input type="color" class="prop-color-picker" style="width:40px; padding:2px; cursor:pointer;" 
                           data-sync="${esc(key)}"
                           value="${(typeof val === 'string' && val.startsWith('#') && val.length === 7) ? val : '#000000'}">
                    <input type="text" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}" placeholder="rgba, hex, transp...">
                 </div>`,
                def)
        },
        {
            supports: (key) => key === 'border',
            render: (key, val, def) => group(key,
                `<input type="text" list="border-presets" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}" placeholder="1px solid #000">
                 <datalist id="border-presets">
                    <option value="none"></option>
                    <option value="1px solid #cccccc"></option>
                    <option value="2px solid #00e5ff"></option>
                    <option value="1px dashed #666666"></option>
                    <option value="2px dotted #ff0000"></option>
                 </datalist>`,
                def)
        },
        {
            supports: (key) => key === 'boxShadow',
            render: (key, val, def) => group(key,
                `<input type="text" list="shadow-presets" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}" placeholder="X Y Blur Spread Color">
                 <datalist id="shadow-presets">
                    <option value="none"></option>
                    <option value="0px 4px 6px rgba(0,0,0,0.1)"></option>
                    <option value="0px 10px 15px rgba(0,0,0,0.2)"></option>
                    <option value="0px 0px 15px rgba(0,229,255,0.4)"></option>
                    <option value="inset 0px 2px 4px rgba(0,0,0,0.5)"></option>
                 </datalist>`,
                def)
        },
        {
            supports: (key, val) => ['opacity', 'zIndex', 'order'].includes(key),
            render: (key, val, def) => {
                const extra = key === 'opacity' ? ' min="0" max="1" step="0.1"' : '';
                return group(key,
                    `<input type="number" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}"${extra}>`,
                    def);
            }
        },
        {
            supports: () => true,
            render: (key, val, def) => group(key,
                `<input type="text" class="prop-input" data-prop="${esc(key)}" value="${esc(val)}">`,
                def)
        }
    ];

    return {
        register(strategy) { strategies.unshift(strategy); },

        create(key, val, controlDef = {}) {
            const strategy = strategies.find(s => s.supports(key, val, controlDef));
            return strategy.render(key, val, controlDef);
        }
    };
})();
