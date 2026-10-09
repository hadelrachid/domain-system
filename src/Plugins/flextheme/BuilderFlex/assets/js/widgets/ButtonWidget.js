/**
 * ButtonWidget — o "TButton".
 */
BuilderFlex.ButtonWidget = class ButtonWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ButtonWidget';
        Object.assign(this.props, {
            name: 'TButton', width: 150, height: 40, text: 'Clique Aqui',
            backgroundColor: '#007bff', color: '#ffffff', borderRadius: 4,
            link: '#', cursor: 'pointer'
        });
    }

    tagName() { return 'a'; }

    attributes() {
        return `href="${BuilderFlex.escape(this.props.link)}"`;
    }

    extraStyles() {
        return `color: ${this.props.color}; display: flex; align-items: center; justify-content: center; `
            + `text-decoration: none; font-weight: bold; cursor: ${this.props.cursor};`;
    }

    content() {
        return BuilderFlex.escape(this.props.text);
    }
};

BuilderFlex.WidgetRegistry.register('ButtonWidget', BuilderFlex.ButtonWidget, {
    label: 'TButton (Botão)', icon: 'fa-mouse-pointer', palette: true
});
