/**
 * ContainerWidget — o "TPanel": único tipo que aceita filhos.
 */
BuilderFlex.ContainerWidget = class ContainerWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ContainerWidget';
        Object.assign(this.props, {
            layout: 'absolute',
            name: 'TPanel', width: '100%', height: 200,
            backgroundColor: '#ffffff', border: '1px solid #cccccc',
            padding: '0px', display: 'block', gap: '0px',
            overflow: 'hidden'
        });
        
        // Inicializa children array porque extends BaseWidget diretamente
        this.children = [];
    }

    canHaveChildren() { return true; }
    canAccept(child) { return true; }
    
    addChild(widget, index = null) {
        if (index !== null && index >= 0 && index <= this.children.length) {
            this.children.splice(index, 0, widget);
        } else {
            this.children.push(widget);
        }
    }
};

BuilderFlex.WidgetRegistry.register('ContainerWidget', BuilderFlex.ContainerWidget, {
    label: 'TPanel (Container)', icon: 'fa-box', palette: true
});
