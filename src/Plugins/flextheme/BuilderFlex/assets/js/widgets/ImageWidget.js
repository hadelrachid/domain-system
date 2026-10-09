/**
 * ImageWidget — o "TImage".
 */
BuilderFlex.ImageWidget = class ImageWidget extends BuilderFlex.BaseWidget {
    constructor(id) {
        super(id);
        this.type = 'ImageWidget';
        Object.assign(this.props, {
            name: 'TImage', width: 200, height: 150,
            src: 'https://via.placeholder.com/200x150?text=Imagem',
            objectFit: 'cover'
        });
    }

    tagName()  { return 'img'; }
    isVoid()   { return true; }

    attributes() {
        return `src="${BuilderFlex.escape(this.props.src)}" draggable="false"`;
    }

    extraStyles() {
        return `object-fit: ${this.props.objectFit};`;
    }
};

BuilderFlex.WidgetRegistry.register('ImageWidget', BuilderFlex.ImageWidget, {
    label: 'TImage (Imagem)', icon: 'fa-image', palette: true
});
