/**
 * CanvasRenderer — desenha a árvore de componentes dentro do canvas.
 * Só sabe renderizar; não altera estado nem trata eventos.
 */
FlexBuilder.CanvasRenderer = class CanvasRenderer {
    constructor(rootElement, state) {
        this.root = rootElement;
        this.state = state;
    }

    render() {
        this.root.innerHTML = this.state.tree.map(node => this.renderNode(node)).join('');
        this.highlight();
    }

    renderNode(node) {
        const inner = node.isContainer
            ? node.children.map(child => this.renderNode(child)).join('')
            : '';
        return node.render(inner);
    }

    /** Marca visualmente o componente ativo, sem recriar o DOM. */
    highlight() {
        this.root.querySelectorAll('.widget-node.active').forEach(el => el.classList.remove('active'));
        const active = this.state.activeNode;
        if (!active) return;
        const el = this.root.querySelector(`[data-node-id="${active.id}"]`);
        if (el) el.classList.add('active');
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
    }
};
