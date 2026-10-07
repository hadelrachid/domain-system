/**
 * ImageWidget — o "TImage".
 */
FlexBuilder.ImageWidget = class ImageWidget extends FlexBuilder.BaseWidget {
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
        return `src="${FlexBuilder.escape(this.props.src)}" draggable="false"`;
    }

    extraStyles() {
        return `object-fit: ${this.props.objectFit};`;
    }
};

FlexBuilder.WidgetRegistry.register('ImageWidget', FlexBuilder.ImageWidget, {
    label: 'TImage (Imagem)', icon: 'fa-image', palette: true
});
