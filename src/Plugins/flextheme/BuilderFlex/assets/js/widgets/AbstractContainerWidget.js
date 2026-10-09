/**
 * AbstractContainerWidget — Classe abstrata para widgets que podem conter
 * outros widgets (Layouts).
 */
BuilderFlex.AbstractContainerWidget = class AbstractContainerWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);

        // Properties padrão que TODO container compartilha
        Object.assign(this.props, {
            layout: 'flow',
            position: 'relative',
            left: 0,
            top: 0,
            width: '100%',
            height: 'auto',
            minHeight: '60px',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'flex-start',
            alignItems: 'stretch',
            gap: '10px',
            padding: '10px',
            backgroundColor: 'transparent',
            border: '1px dashed rgba(0, 229, 255, 0.25)',
            overflow: 'visible'
        });
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
