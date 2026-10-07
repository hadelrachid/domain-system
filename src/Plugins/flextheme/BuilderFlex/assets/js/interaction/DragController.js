/**
 * DragController — arrasta componentes com snap-to-grid.
 * Usa um overlay de tela cheia para não perder o mouse sobre outros elementos.
 */
FlexBuilder.DragController = class DragController {
    constructor(state, bus, overlayElement) {
        this.state = state;
        this.bus = bus;
        this.overlay = overlayElement;
    }

    begin(node, event) {
        const drag = this.state.drag;
        drag.active = true;
        drag.nodeId = node.id;
        drag.startX = event.clientX;
        drag.startY = event.clientY;
        drag.nodeStartX = parseInt(node.props.left) || 0;
        drag.nodeStartY = parseInt(node.props.top) || 0;
        this.overlay.style.display = 'block';
    }

    move(event) {
        const drag = this.state.drag;
        if (!drag.active) return;

        const node = FlexBuilder.NodeTree.find(this.state.tree, drag.nodeId);
        if (!node) return;

        const grid = this.state.gridSize;
        const rawLeft = drag.nodeStartX + (event.clientX - drag.startX);
        const rawTop = drag.nodeStartY + (event.clientY - drag.startY);
        node.props.left = Math.round(rawLeft / grid) * grid;
        node.props.top = Math.round(rawTop / grid) * grid;

        this.bus.emit('node:moved', node);
    }

    end() {
        const drag = this.state.drag;
        if (!drag.active) return;

        drag.active = false;
        drag.nodeId = null;
        this.overlay.style.display = 'none';
        this.bus.emit('tree:changed');
    }
};
