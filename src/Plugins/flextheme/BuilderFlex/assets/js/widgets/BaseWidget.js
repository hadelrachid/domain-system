/**
 * BaseWidget — o "TControl" do Builder (pai de todos os componentes).
 *
 * Define as propriedades universais (posição, tamanho, cores, camada...) e o
 * esqueleto de renderização. Os filhos só sobrescrevem pequenos ganchos
 * (Template Method): tagName(), attributes(), extraStyles(), content().
 */
FlexBuilder.BaseWidget = class BaseWidget {
    constructor(id) {
        this.id = id || 'comp_' + Math.random().toString(36).substr(2, 9);
        this.type = 'BaseWidget';
        this.isContainer = false;
        this.props = {
            name: 'BaseControl', left: 20, top: 20, width: 150, height: 50,
            margin: '0px', padding: '0px', display: 'block', flexDirection: 'row',
            justifyContent: 'flex-start', alignItems: 'stretch', gap: '0px',
            backgroundColor: 'transparent', border: 'none', borderRadius: 0,
            boxShadow: 'none', opacity: 1, zIndex: 1
        };
        this.children = [];
    }

    // ── Ganchos de renderização (sobrescritos pelos filhos) ──────────────
    tagName()     { return 'div'; }
    isVoid()      { return false; }
    attributes()  { return ''; }
    extraStyles() { return ''; }
    content(innerContent) { return innerContent; }

    // ── Renderização ─────────────────────────────────────────────────────
    baseStyles() {
        const p = this.props;
        const unit = value => value + (typeof value === 'number' ? 'px' : '');
        return `
            position: absolute;
            left: ${p.left}px;
            top: ${p.top}px;
            width: ${unit(p.width)};
            height: ${unit(p.height)};
            margin: ${p.margin};
            padding: ${p.padding};
            display: ${p.display};
            flex-direction: ${p.flexDirection};
            justify-content: ${p.justifyContent};
            align-items: ${p.alignItems};
            gap: ${p.gap};
            background-color: ${p.backgroundColor};
            border: ${p.border};
            border-radius: ${p.borderRadius}px;
            box-shadow: ${p.boxShadow};
            opacity: ${p.opacity};
            z-index: ${p.zIndex};
        `;
    }

    render(innerContent = '') {
        const esc = FlexBuilder.escape;
        const tag = this.tagName();
        const attrs = `id="${esc(this.id)}" data-node-id="${esc(this.id)}" class="widget-node" `
            + `style="${esc(this.baseStyles() + this.extraStyles())}" ${this.attributes()}`;

        if (this.isVoid()) return `<${tag} ${attrs}>`;
        return `<${tag} ${attrs}>${this.content(innerContent)}</${tag}>`;
    }

    // ── Persistência ─────────────────────────────────────────────────────
    hydrate(data) {
        this.id = data.id;
        this.type = data.type;
        this.props = { ...this.props, ...data.props };

        (data.children || []).forEach(childData => {
            const child = FlexBuilder.WidgetRegistry.createInstance(childData.type, childData.id);
            child.hydrate(childData);
            this.children.push(child);
        });
    }
};
