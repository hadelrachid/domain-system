/**
 * ResizeController - gerencia o redimensionamento dos componentes
 */
BuilderFlex.ResizeController = class ResizeController {
    constructor(state, bus, overlay) {
        this.state = state;
        this.bus = bus;
        this.overlay = overlay;
        this.resize = { active: false, dir: null, nodeId: null, startX: 0, startY: 0, startW: 0, startH: 0, startL: 0, startT: 0 };
    }

    begin(node, dir, event) {
        this.resize.active = true;
        this.resize.dir = dir;
        this.resize.nodeId = node.id;
        this.resize.startX = event.clientX;
        this.resize.startY = event.clientY;
        
        const nodeEl = document.getElementById(node.id);
        const rect = nodeEl.getBoundingClientRect();
        
        // Always use pixel dimensions for the math during dragging
        this.resize.startW = rect.width;
        this.resize.startH = rect.height;
        
        // For position, we need the relative position to the offsetParent
        this.resize.startL = nodeEl.offsetLeft;
        this.resize.startT = nodeEl.offsetTop;

        if (this.overlay) this.overlay.style.display = 'block';
    }

    move(event) {
        if (!this.resize.active) return;

        const node = BuilderFlex.NodeTree.find(this.state.rootContainer.children, this.resize.nodeId);
        if (!node) return;

        const dx = event.clientX - this.resize.startX;
        const dy = event.clientY - this.resize.startY;
        const grid = this.state.gridSize || 10;
        
        let newW = this.resize.startW;
        let newH = this.resize.startH;
        let newL = this.resize.startL;
        let newT = this.resize.startT;

        const dir = this.resize.dir;

        if (dir.includes('e')) newW += dx;
        if (dir.includes('s')) newH += dy;
        if (dir.includes('w')) {
            newW -= dx;
            newL += dx;
        }
        if (dir.includes('n')) {
            newH -= dy;
            newT += dy;
        }

        // Snap to grid
        newW = Math.max(grid, Math.round(newW / grid) * grid);
        newH = Math.max(grid, Math.round(newH / grid) * grid);
        
        if (dir.includes('w')) newL = this.resize.startL + (this.resize.startW - newW);
        if (dir.includes('n')) newT = this.resize.startT + (this.resize.startH - newH);

        const nodeEl = document.getElementById(node.id);
        const parentEl = nodeEl ? nodeEl.offsetParent : null;
        
        let finalW = newW;
        let finalH = newH;
        let finalL = newL;
        let finalT = newT;
        
        if (node.getProp('position') === 'absolute' && parentEl) {
            const pW = parentEl.clientWidth || 1;
            const pH = parentEl.clientHeight || 1;
            finalW = (newW / pW * 100).toFixed(2) + '%';
            finalL = (newL / pW * 100).toFixed(2) + '%';
            finalT = (newT / pH * 100).toFixed(2) + '%';
        }

        node.setProp('width', finalW);
        node.setProp('height', finalH);
        
        if (node.getProp('position') === 'absolute') {
            if (dir.includes('w')) node.setProp('left', finalL);
            if (dir.includes('n')) node.setProp('top', finalT);
        }

        if (nodeEl) {
            nodeEl.style.width = newW + 'px';
            nodeEl.style.height = newH + 'px';
            if (node.getProp('position') === 'absolute') {
                if (dir.includes('w')) nodeEl.style.left = newL + 'px';
                if (dir.includes('n')) nodeEl.style.top = newT + 'px';
            }
        }
    }

    end(event) {
        if (!this.resize.active) return;
        this.resize.active = false;
        this.resize.nodeId = null;
        if (this.overlay) this.overlay.style.display = 'none';
        this.bus.emit('tree:changed');
    }
};
