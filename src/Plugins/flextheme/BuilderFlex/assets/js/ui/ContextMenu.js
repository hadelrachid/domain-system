/**
 * ContextMenu — gerencia o menu de contexto no canvas.
 */
BuilderFlex.ContextMenu = class ContextMenu {
    constructor(element, bus, state) {
        this.element = element;
        this.bus = bus;
        this.state = state;
        this.targetNode = null;

        this.bindDom();
    }

    open(event, node) {
        event.preventDefault();
        this.targetNode = node;
        this.element.style.display = 'block';
        this.element.style.left = `${event.clientX}px`;
        this.element.style.top = `${event.clientY}px`;
    }

    close() {
        this.element.style.display = 'none';
        this.targetNode = null;
    }

    bindDom() {
        // Fechar ao clicar fora
        document.addEventListener('mousedown', (e) => {
            if (!this.element.contains(e.target)) {
                this.close();
            }
        });

        // Ações do menu
        this.element.addEventListener('click', (e) => {
            const item = e.target.closest('[data-action]');
            if (!item || !this.targetNode) return;
            
            const action = item.dataset.action;
            if (action === 'ctx-bring-front') {
                this.targetNode.props.zIndex = (this.targetNode.props.zIndex || 1) + 1;
                this.bus.emit('tree:changed');
            } else if (action === 'ctx-send-back') {
                this.targetNode.props.zIndex = Math.max(0, (this.targetNode.props.zIndex || 1) - 1);
                this.bus.emit('tree:changed');
            } else if (action === 'ctx-duplicate') {
                // To do: duplicar node
                this.bus.emit('node:duplicate', this.targetNode);
            } else if (action === 'ctx-delete') {
                BuilderFlex.NodeTree.remove(this.state.tree, this.targetNode.id);
                if (this.state.activeNode === this.targetNode) {
                    this.state.activeNode = null;
                    this.bus.emit('selection:changed');
                }
                this.bus.emit('tree:changed');
            }
            this.close();
        });
    }
};
