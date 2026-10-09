/**
 * CanvasRenderer — desenha a árvore de componentes dentro do canvas.
 * Só sabe renderizar; não altera estado nem trata eventos.
 */
BuilderFlex.CanvasRenderer = class CanvasRenderer {
    constructor(rootElement, state, breakpoints) {
        this.root = rootElement;
        this.state = state;
        this.breakpoints = breakpoints;
    }

    render() {
        const rootStrategy = new BuilderFlex.FlowLayout();
        this.root.innerHTML = this.state.tree.map(node => this.renderNode(node, rootStrategy)).join('');
        this.highlight();
    }

    renderNode(node, parentStrategy) {
        const bp = this.breakpoints ? this.breakpoints.get() : 'base';
        const myStrategy = node.canHaveChildren() ? node.getLayoutStrategy(bp) : null;
        const inner = node.canHaveChildren()
            ? node.children.map(child => this.renderNode(child, myStrategy)).join('')
            : '';
        return node.render(inner, bp, parentStrategy);
    }

    /** Marca visualmente o componente ativo, sem recriar o DOM. */
    highlight() {
        this.root.querySelectorAll('.widget-node.active').forEach(el => el.classList.remove('active'));
        this.root.querySelectorAll('.resize-handle').forEach(el => el.remove());
        
        const active = this.state.activeNode;
        if (!active) return;
        const el = this.root.querySelector(`[data-node-id="${active.id}"]`);
        if (el) {
            el.classList.add('active');
            
            const handles = ['se', 'sw', 'ne', 'nw', 'e', 's', 'w', 'n'];
            handles.forEach(dir => {
                const handle = document.createElement('div');
                handle.className = `resize-handle resize-${dir}`;
                handle.dataset.dir = dir;
                el.appendChild(handle);
            });
        }
    }

    /** Atualiza só a posição (usado durante o arraste, para ser fluido). */
    moveElement(node) {
        const el = this.root.querySelector(`[data-node-id="${node.id}"]`);
        if (!el) return;
        el.style.left = node.props.left + 'px';
        el.style.top = node.props.top + 'px';
    }

    setViewMode(mode) {
        this.root.className = mode === 'mobile' ? 'view-mobile' : '';
        this.state.viewMode = mode;
        if (this.bus) this.bus.emit('view:changed', mode);
        this.render();
    }
};
