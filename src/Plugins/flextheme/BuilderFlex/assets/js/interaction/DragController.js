/**
 * DragController — arrasta componentes com snap-to-grid.
 * Usa um overlay de tela cheia para não perder o mouse sobre outros elementos.
 */
BuilderFlex.DragController = class DragController {
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
        
        const isFromPalette = event.target && event.target.closest ? event.target.closest('.builder-palette') !== null : false;
        if (isFromPalette) {
            const canvasRoot = document.getElementById('canvas-root');
            if (canvasRoot) {
                const canvasRect = canvasRoot.getBoundingClientRect();
                const w = parseInt(node.props.width) || 150;
                const h = parseInt(node.props.height) || 50;
                const screenLeft = event.clientX - (w / 2);
                const screenTop = event.clientY - (h / 2);
                
                node.props.left = screenLeft - canvasRect.left + canvasRoot.scrollLeft;
                node.props.top = screenTop - canvasRect.top + canvasRoot.scrollTop;
                this.bus.emit('node:moved', node);
            }
        }

        drag.nodeStartX = parseInt(node.props.left) || 0;
        drag.nodeStartY = parseInt(node.props.top) || 0;
        this.overlay.style.display = 'block';

        drag.isDragging = false;
        
        // Remove immediate class addition
    }

    move(event) {
        const drag = this.state.drag;
        if (!drag.active) return;

        const node = BuilderFlex.NodeTree.find(this.state.rootContainer.children, drag.nodeId);
        if (!node) return;

        const dx = event.clientX - drag.startX;
        const dy = event.clientY - drag.startY;

        if (!drag.isDragging && (Math.abs(dx) > 3 || Math.abs(dy) > 3)) {
            drag.isDragging = true;
            const nodeEl = document.getElementById(node.id);
            if (nodeEl) {
                nodeEl.classList.add('dragging');
            }
        }

        if (!drag.isDragging) return;

        const grid = this.state.gridSize;
        const rawLeft = drag.nodeStartX + (event.clientX - drag.startX);
        const rawTop = drag.nodeStartY + (event.clientY - drag.startY);
        node.props.left = Math.round(rawLeft / grid) * grid;
        node.props.top = Math.round(rawTop / grid) * grid;

        this.bus.emit('node:moved', node);
    }

    end(event) {
        const drag = this.state.drag;
        if (!drag.active) return;

        const node = BuilderFlex.NodeTree.find(this.state.rootContainer.children, drag.nodeId);
        if (!node) return;

        drag.active = false;
        drag.nodeId = null;
        this.overlay.style.display = 'none';
        
        if (!drag.isDragging) {
            return;
        }
        
        if (event) {
            const nodeEl = document.getElementById(node.id);
            if (nodeEl) {
                // Remove the element temporarily to find what's underneath
                const oldDisplay = nodeEl.style.display;
                nodeEl.style.display = 'none';
                
                const elUnderMouse = document.elementFromPoint(event.clientX, event.clientY);
                nodeEl.style.display = oldDisplay;

                if (elUnderMouse) {
                    const canvasRoot = elUnderMouse.closest('#canvas-root');
                    if (canvasRoot) {
                        const isSelfOrDescendant = (parent, potentialChildId) => {
                            if (!parent) return false;
                            if (parent.id === potentialChildId) return true;
                            return BuilderFlex.NodeTree.find(parent.children || [], potentialChildId) !== null;
                        };

                        let targetNode = this.state.rootContainer;
                        let targetEl = canvasRoot;
                        
                        let currentEl = elUnderMouse.closest('.widget-node');
                        while (currentEl) {
                            const potentialTarget = BuilderFlex.NodeTree.find(this.state.rootContainer.children, currentEl.dataset.nodeId);
                            if (potentialTarget && potentialTarget.canHaveChildren() && potentialTarget.canAccept(node) && !isSelfOrDescendant(node, potentialTarget.id)) {
                                targetNode = potentialTarget;
                                targetEl = currentEl;
                                break;
                            }
                            currentEl = currentEl.parentElement ? currentEl.parentElement.closest('.widget-node') : null;
                        }

                        const findParentNode = (tree, id) => {
                            for (const n of tree) {
                                if (n.children && n.children.some(c => c.id === id)) return n;
                                if (n.children) {
                                    const p = findParentNode(n.children, id);
                                    if (p !== undefined) return p;
                                }
                            }
                            return this.state.rootContainer;
                        };
                        
                        const oldParentNode = findParentNode(this.state.rootContainer.children, node.id);
                        
                        // Captura os retângulos ANTES de remover a classe dragging para preservar a posição visual correta
                        const nodeRect = nodeEl.getBoundingClientRect();
                        const targetRect = targetEl.getBoundingClientRect();
                        
                        const dropPoint = {
                            x: event.clientX,
                            y: event.clientY,
                            nodeRect: nodeRect,
                            targetRect: targetRect,
                            scrollLeft: targetEl.scrollLeft || 0,
                            scrollTop: targetEl.scrollTop || 0,
                            style: window.getComputedStyle(targetEl),
                            gridSize: this.state.gridSize || 10
                        };

                        if (oldParentNode !== targetNode) {
                            BuilderFlex.NodeTree.remove(this.state.rootContainer.children, node.id);
                            targetNode.addChild(node);
                        }
                        
                        // Sempre aplica a estratégia do pai
                        const strategy = targetNode.getLayoutStrategy();
                        strategy.place(targetNode, node, dropPoint);
                    }
                }
                
                // Remove a classe dragging após calcular o bounding rect
                nodeEl.classList.remove('dragging');
            }
        }

        this.bus.emit('tree:changed');
    }
};
