/**
 * ContainerWidget — o "TPanel": único tipo que aceita filhos.
 */
FlexBuilder.ContainerWidget = class ContainerWidget extends FlexBuilder.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ContainerWidget';
        this.isContainer = true;
        Object.assign(this.props, {
            name: 'TPanel', width: 300, height: 200,
            backgroundColor: '#ffffff', border: '1px solid #cccccc',
            padding: '15px', display: 'flex', flexDirection: 'column', gap: '10px'
        });
    }
};

FlexBuilder.WidgetRegistry.register('ContainerWidget', FlexBuilder.ContainerWidget, {
    label: 'TPanel (Container)', icon: 'fa-box', palette: true
});
