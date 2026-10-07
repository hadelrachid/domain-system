/**
 * TextWidget — o "TLabel".
 */
FlexBuilder.TextWidget = class TextWidget extends FlexBuilder.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'TextWidget';
        Object.assign(this.props, {
            name: 'TLabel', width: 120, height: 30, text: 'Texto Exemplo',
            color: '#333333', fontSize: 16, fontFamily: 'Arial, sans-serif',
            fontWeight: 'normal', textAlign: 'left'
        });
    }

    extraStyles() {
        const p = this.props;
        return `color: ${p.color}; font-size: ${p.fontSize}px; font-family: ${p.fontFamily}; `
            + `font-weight: ${p.fontWeight}; text-align: ${p.textAlign}; display: flex; align-items: center;`;
    }

    content() {
        return FlexBuilder.escape(this.props.text);
    }
};

FlexBuilder.WidgetRegistry.register('TextWidget', FlexBuilder.TextWidget, {
    label: 'TLabel (Texto)', icon: 'fa-font', palette: true
});
