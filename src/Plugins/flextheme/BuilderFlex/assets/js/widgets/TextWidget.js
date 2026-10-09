/**
 * TextWidget — o "TLabel".
 */
BuilderFlex.TextWidget = class TextWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'TextWidget';
        Object.assign(this.props, {
            name: 'TLabel', width: 120, height: 30, text: 'Texto Exemplo',
            color: '#333333', fontSize: 16, fontFamily: 'Arial, sans-serif',
            fontWeight: 'normal', textAlign: 'left'
        });
    }

    extraStyles(bp = 'base') {
        const get = key => this.getProp(key, bp);
        return `color: ${get('color')}; font-size: ${get('fontSize')}px; font-family: ${get('fontFamily')}; `
            + `font-weight: ${get('fontWeight')}; text-align: ${get('textAlign')}; display: flex; align-items: center;`;
    }

    content() {
        return BuilderFlex.escape(this.getProp('text', 'base')); // Text doesn't usually change by breakpoint, but let's read from base for now
    }
};

BuilderFlex.WidgetRegistry.register('TextWidget', BuilderFlex.TextWidget, {
    label: 'TLabel (Texto)', icon: 'fa-font', palette: true
});
