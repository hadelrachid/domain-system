/**
 * LayoutWidgets - Grids e Colunas (Padrão Web / Elementor)
 * 
 * Diferente do TPanel (que usa posição absoluta livre), estes componentes
 * usam o fluxo natural da Web (position: relative, flexbox) para criar
 * estruturas de "encaixe" (grids, linhas e colunas).
 */

BuilderFlex.LayoutRowWidget = class LayoutRowWidget extends BuilderFlex.AbstractContainerWidget {
    constructor(id) {
        super(id);
        this.type = 'LayoutRowWidget';
        Object.assign(this.props, {
            name: 'Linha (Row)', width: '100%', height: 'auto',
            minHeight: '100px',
            backgroundColor: 'transparent', border: '1px dashed #444', padding: '10px',
            display: 'flex', flexDirection: 'row', gap: '15px', alignItems: 'stretch'
        });
    }

    canAccept(child) {
        return child && child.type === 'LayoutColWidget';
    }

    extraStyles() {
        return `min-height: ${this.props.minHeight};`;
    }
};

BuilderFlex.LayoutColWidget = class LayoutColWidget extends BuilderFlex.AbstractContainerWidget {
    constructor(id) {
        super(id);
        this.type = 'LayoutColWidget';
        Object.assign(this.props, {
            name: 'Coluna (Col)', width: '100%', height: 'auto',
            minHeight: '80px', flex: '1',
            backgroundColor: 'rgba(255,255,255,0.05)', border: '1px dashed #666', padding: '10px',
            display: 'flex', flexDirection: 'column', gap: '10px'
        });
    }

    extraStyles() {
        return `min-height: ${this.props.minHeight}; flex: ${this.props.flex};`;
    }
};

BuilderFlex.WidgetRegistry.register('LayoutRowWidget', BuilderFlex.LayoutRowWidget, {
    label: 'Linha (Grid)', icon: 'fa-table-rows', palette: true
});

BuilderFlex.WidgetRegistry.register('LayoutColWidget', BuilderFlex.LayoutColWidget, {
    label: 'Coluna (Col)', icon: 'fa-table-columns', palette: true
});
