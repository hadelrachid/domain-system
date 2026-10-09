/**
 * GridWidget — Layout com CSS Grid. Suporta colunas diferentes por breakpoint.
 */
BuilderFlex.GridWidget = class GridWidget extends BuilderFlex.AbstractContainerWidget {
    constructor(id) {
        super(id);
        this.type = 'GridWidget';

        Object.assign(this.props, {
            layout: 'grid',
            name: 'Grade (Grid)',
            display: 'grid',
            gridTemplateColumns: 'repeat(4, 1fr)',  // Desktop: 4 colunas
            gridTemplateRows: 'auto auto',
            gap: '20px',
            padding: '20px',
            minHeight: '120px',
            backgroundColor: 'transparent',
            border: '1px dashed rgba(0, 229, 255, 0.35)',
            columnsDesktop: 4,
            columnsTablet: 2,
            columnsMobile: 1,
            rowsDesktop: 2,
            rowsTablet: 2,
            rowsMobile: 4
        });

        // Overrides específicos de breakpoint
        this.tablet = { gridTemplateColumns: 'repeat(2, 1fr)', gridTemplateRows: 'auto auto' };
        this.mobile = { gridTemplateColumns: 'repeat(1, 1fr)', gridTemplateRows: 'auto auto auto auto' };
    }

    tagName() { return 'div'; }

    setColumns(count, breakpoint = 'base') {
        const prop = breakpoint === 'base' ? 'columnsDesktop'
                   : breakpoint === 'tablet' ? 'columnsTablet'
                   : 'columnsMobile';
        this.setProp(prop, count, breakpoint);
        this.setProp('gridTemplateColumns', `repeat(${count}, 1fr)`, breakpoint);
    }

    baseStyles(bp = 'base', parentStrategy = null) {
        const base = super.baseStyles(bp, parentStrategy);
        const get = key => this.getProp(key, bp);
        return base + `
            grid-template-columns: ${get('gridTemplateColumns')};
            grid-template-rows: ${get('gridTemplateRows')};
        `;
    }

    content(innerContent) {
        if (this.children.length > 0) {
            return innerContent;
        }
        const cols = parseInt(this.props.columnsDesktop) || 4;
        const rows = parseInt(this.props.rowsDesktop) || 2;
        let cells = '';
        for (let i = 0; i < cols * rows; i++) {
            cells += `<div class="grid-empty-cell"></div>`;
        }
        return cells;
    }
};

BuilderFlex.WidgetRegistry.register('GridWidget', BuilderFlex.GridWidget, {
    label: 'Grade (Grid)',
    icon: 'fa-th',
    palette: true,
    category: 'Layout'
});
