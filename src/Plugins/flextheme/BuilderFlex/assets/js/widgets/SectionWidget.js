/**
 * SectionWidget — Bloco de largura total. É o "pai" mais comum no canvas.
 * Contém Rows, Grids ou diretamente Column/Widgets.
 */
BuilderFlex.SectionWidget = class SectionWidget extends BuilderFlex.AbstractContainerWidget {
    constructor(id) {
        super(id);
        this.type = 'SectionWidget';

        Object.assign(this.props, {
            name: 'Seção',
            flexDirection: 'column',
            gap: '20px',
            padding: '60px 40px',
            minHeight: '200px',
            backgroundColor: 'transparent',
            maxWidth: '1200px',
            margin: '0 auto'
        });
    }

    tagName() { return 'section'; }

    baseStyles(bp = 'base') {
        const base = super.baseStyles(bp);
        const get = key => this.getProp(key, bp);
        return base + `
            max-width: ${get('maxWidth')};
            margin-left: auto;
            margin-right: auto;
        `;
    }
};

BuilderFlex.WidgetRegistry.register('SectionWidget', BuilderFlex.SectionWidget, {
    label: 'Seção',
    icon: 'fa-square-full',
    palette: true,
    category: 'Layout'
});
