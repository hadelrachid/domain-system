/**
 * BaseWidget — o "TControl" do Builder (pai de todos os componentes).
 *
 * Define as propriedades universais (posição, tamanho, cores, camada...) e o
 * esqueleto de renderização. Os filhos só sobrescrevem pequenos ganchos
 * (Template Method): tagName(), attributes(), extraStyles(), content().
 */
BuilderFlex.BaseWidget = class BaseWidget {
    constructor(id) {
        this.id = id || 'comp_' + Math.random().toString(36).substr(2, 9);
        this.type = 'BaseWidget';
        // The base properties for Desktop
        this.props = {
            name: 'BaseControl', position: 'absolute', left: 20, top: 20, width: 150, height: 50,
            minHeight: 'auto', flex: 'none',
            margin: '0px', padding: '0px', display: 'block', flexDirection: 'row',
            justifyContent: 'flex-start', alignItems: 'stretch', gap: '0px',
            backgroundColor: 'transparent', border: 'none', borderRadius: 0,
            boxShadow: 'none', opacity: 1, zIndex: 1, overflow: 'visible'
        };
        
        // Responsive Overrides
        this.tablet = {};
        this.mobile = {};
        
        this.children = [];
    }

    /** Retorna o valor resolvido da propriedade para o breakpoint ativo */
    getRawProp(key, breakpoint = 'base') {
        if (breakpoint === 'mobile') return this.mobile[key];
        if (breakpoint === 'tablet') return this.tablet[key];
        return this.props[key];
    }

    getProp(key, breakpoint = 'base') {
        if (BuilderFlex.IViewportResolver.getResponsiveProps().includes(key)) {
            return BuilderFlex.IViewportResolver.resolve(this, breakpoint).props[key];
        }
        return this.props[key];
    }
    
    setProp(key, value, breakpoint = 'base') {
        if (!BuilderFlex.IViewportResolver.getResponsiveProps().includes(key)) {
            this.props[key] = value;
            return;
        }

        if (breakpoint === 'base') this.props[key] = value;
        else if (breakpoint === 'tablet') this.tablet[key] = value;
        else if (breakpoint === 'mobile') this.mobile[key] = value;
    }

    // ── Ganchos de renderização (sobrescritos pelos filhos) ──────────────
    tagName()     { return 'div'; }
    isVoid()      { return false; }
    canHaveChildren() { return false; }
    canAccept(child) { return false; }
    get isContainer() { return this.canHaveChildren(); }
    attributes()  { return ''; }
    extraStyles() { return ''; }
    content(innerContent) { return innerContent; }

    // ── Renderização ─────────────────────────────────────────────────────
    getLayoutStrategy(bp = 'base') {
        if (!this.canHaveChildren()) return null;
        const layout = this.getProp('layout', bp) || 'absolute';
        if (layout === 'flow') return new BuilderFlex.FlowLayout();
        if (layout === 'grid') return new BuilderFlex.GridLayout();
        return new BuilderFlex.AbsoluteLayout();
    }

    baseStyles(bp = 'base', parentStrategy = null) {
        const get = key => this.getProp(key, bp);
        
        let styles = `
            margin: ${get('margin')};
            padding: ${get('padding')};
            display: ${get('display')};
            flex-direction: ${get('flexDirection')};
            justify-content: ${get('justifyContent')};
            align-items: ${get('alignItems')};
            gap: ${get('gap')};
            order: ${get('order') || 0};
            background-color: ${get('backgroundColor')};
            border: ${get('border')};
            border-radius: ${get('borderRadius')}px;
            box-shadow: ${get('boxShadow')};
            opacity: ${get('opacity')};
            overflow: ${get('overflow')};
            box-sizing: border-box;
        `;

        if (parentStrategy) {
            styles += parentStrategy.childStyles(null, this, bp);
        } else {
            const unit = value => {
                if (value === null || value === undefined || value === '') return '';
                if (!isNaN(value)) return value + 'px';
                return value;
            };
            const leftVal = get('left');
            const topVal = get('top');
            styles += `
                position: ${get('position') || 'absolute'};
                left: ${unit(leftVal)};
                top: ${unit(topVal)};
                width: ${unit(get('width'))};
                height: ${get('height') === 'auto' ? 'auto' : unit(get('height'))};
                min-height: ${unit(get('minHeight'))};
                z-index: ${get('zIndex') || 1};
            `;
        }

        if (this.canHaveChildren()) {
            const myStrategy = this.getLayoutStrategy();
            if (myStrategy) {
                styles += myStrategy.containerStyles(this, bp);
            }
        }

        return styles;
    }

    render(innerContent = '', bp = 'base', parentStrategy = null) {
        const esc = BuilderFlex.escape;
        const tag = this.tagName();
        let layoutClass = this.canHaveChildren() ? `layout-${this.getProp('layout', bp) || 'absolute'}` : '';
        if (this.getProp('hidden', bp)) {
            layoutClass += ' hidden-in-canvas';
        }
        
        const attrs = `id="${esc(this.id)}" data-node-id="${esc(this.id)}" class="widget-node ${layoutClass}" `
            + `style="${esc(this.baseStyles(bp, parentStrategy) + this.extraStyles(bp))}" ${this.attributes()}`;

        if (this.isVoid()) return `<${tag} ${attrs}>`;
        return `<${tag} ${attrs}>${this.content(innerContent)}</${tag}>`;
    }

    // ── Persistência ─────────────────────────────────────────────────────
    hydrate(data) {
        this.id = data.id;
        this.type = data.type;
        this.props = { ...this.props, ...data.props };
        if (data.tablet) this.tablet = { ...data.tablet };
        if (data.mobile) this.mobile = { ...data.mobile };

        (data.children || []).forEach(childData => {
            const child = BuilderFlex.WidgetRegistry.createInstance(childData.type, childData.id);
            child.hydrate(childData);
            this.children.push(child);
        });
    }
};
