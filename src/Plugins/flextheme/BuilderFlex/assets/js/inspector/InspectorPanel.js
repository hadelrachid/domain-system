/**
 * InspectorPanel — Painel de propriedades.
 * Agora com suporte a TABS de breakpoint.
 */
BuilderFlex.InspectorPanel = class InspectorPanel {
    static CATEGORIES = {
        'Layout e Tamanho':      ['layout', 'left', 'top', 'width', 'height', 'minHeight', 'margin', 'padding', 'maxWidth', 'overflow'],
        'Flex / Grid':           ['display', 'flexDirection', 'justifyContent', 'alignItems', 'gap', 'gridTemplateColumns', 'order'],
        'Estética':              ['backgroundColor', 'border', 'borderRadius', 'boxShadow', 'opacity', 'zIndex', 'color', 'hidden'],
        'Conteúdo':              ['text', 'fontSize', 'fontFamily', 'fontWeight', 'textAlign', 'src', 'link', 'objectFit']
    };

    constructor(bodyElement, state, breakpoints) {
        this.body = bodyElement;
        this.state = state;
        this.breakpoints = breakpoints;
    }

    render(node) {
        if (!node) {
            this.body.innerHTML = '<div class="inspector-empty">Selecione um objeto no form.</div>';
            return;
        }

        const esc = BuilderFlex.escape;
        const bp = this.breakpoints ? this.breakpoints.get() : 'base';
        const categorized = new Set(Object.values(InspectorPanel.CATEGORIES).flat());

        let html = `
            <div class="inspector-title">
                <strong>${esc(node.props.name)}</strong>
                <span class="inspector-type">${esc(node.type)}</span>
                <div class="inspector-id">ID: ${esc(node.id)}</div>
                ${this.renderBreakpointTabs()}
                <button class="btn btn-danger-soft" data-action="delete-node" style="margin-top:10px;">
                    <i class="fas fa-trash"></i> Delete (Del)
                </button>
            </div>`;

        for (const [categoryName, keys] of Object.entries(InspectorPanel.CATEGORIES)) {
            const fields = keys
                .filter(key => node.props[key] !== undefined)
                .map(key => {
                    const hasOverride = this.hasBreakpointOverride(node, key, bp);
                    const controlDef = { 
                        responsive: this.isResponsiveProp(key),
                        hasOverride: hasOverride
                    };
                    return BuilderFlex.FieldFactory.create(key, node.getProp(key, bp), controlDef);
                })
                .join('');
            if (fields) html += this.section(categoryName, fields);
        }

        let extra = Object.keys(node.props)
            .filter(key => key !== 'name' && key !== 'attrs' && !categorized.has(key))
            .map(key => BuilderFlex.FieldFactory.create(key, node.getProp(key, bp), {
                responsive: this.isResponsiveProp(key),
                hasOverride: this.hasBreakpointOverride(node, key, bp)
            }))
            .join('');

        if (node.type === 'ShortcodeWidget' || node.type.endsWith('ShortcodeWidget')) {
            const attrs = node.props.attrs || {};
            const attrsHtml = Object.entries(attrs).map(([k, v]) => 
                BuilderFlex.FieldFactory.create(`attrs.${k}`, v, { responsive: false })
            ).join('');
            if (attrsHtml) extra += this.section('Shortcode Atributos', attrsHtml);
        }

        if (extra) html += this.section('Extra', extra);

        this.body.innerHTML = html;
    }

    renderBreakpointTabs() {
        if (!this.breakpoints) return '';
        const active = this.breakpoints.get();
        const tabs = [
            { id: 'base',   icon: 'fa-desktop',   title: 'Desktop' },
            { id: 'tablet', icon: 'fa-tablet-alt', title: 'Tablet' },
            { id: 'mobile', icon: 'fa-mobile-alt', title: 'Mobile' }
        ];
        const buttons = tabs.map(t => `
            <button type="button"
                class="bp-tab ${t.id === active ? 'active' : ''}"
                data-action="switch-breakpoint"
                data-breakpoint="${t.id}"
                title="${t.title}">
                <i class="fas ${t.icon}"></i>
            </button>
        `).join('');
        return `<div class="bp-tabs">${buttons}</div>`;
    }

    hasBreakpointOverride(node, key, bp) {
        if (bp === 'base') return false;
        if (bp === 'tablet') return node.tablet && node.tablet[key] !== undefined;
        if (bp === 'mobile') return node.mobile && node.mobile[key] !== undefined;
        return false;
    }

    isResponsiveProp(key) {
        const responsiveProps = [
            'width', 'height', 'minHeight', 'padding', 'margin', 'gap',
            'flexDirection', 'justifyContent', 'alignItems',
            'fontSize', 'textAlign', 'maxWidth',
            'gridTemplateColumns', 'columnsDesktop', 'columnsTablet', 'columnsMobile',
            'display'
        ];
        return responsiveProps.includes(key);
    }

    section(title, fieldsHtml) {
        return `<div class="inspector-section">
                    <div class="inspector-section-title">${BuilderFlex.escape(title)}</div>
                    ${fieldsHtml}
                </div>`;
    }

    syncField(key, value) {
        const input = this.body.querySelector(`[data-prop="${key}"]`);
        if (input) input.value = value;
    }
};
