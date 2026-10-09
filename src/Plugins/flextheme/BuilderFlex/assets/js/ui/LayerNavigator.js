/**
 * LayerNavigator - Renderiza a árvore de hierarquia dos componentes.
 */
BuilderFlex.LayerNavigator = class LayerNavigator {
    constructor(dom, state, bus) {
        this.dom = dom;
        this.state = state;
        this.bus = bus;
        this._itemsById = {};

        // Delegação de eventos para seleção e ações de camada
        this.dom.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.btn-layer-action');
            if (btn) {
                ev.stopPropagation();
                const item = btn.closest('.layer-item');
                const nodeId = item.dataset.nodeId;
                const action = btn.dataset.action;
                this._reorderNode(nodeId, action);
                return;
            }

            const item = ev.target.closest('.layer-item');
            if (!item) return;

            const nodeId = item.dataset.nodeId;
            const node = BuilderFlex.NodeTree.find(this.state.rootContainer.children, nodeId);
            if (!node) return;

            this.state.activeNode = node;
            this.bus.emit('selection:changed'); // Dispara após atualizar estado
        });
    }

    _reorderNode(nodeId, action) {
        const findParentAndIndex = (tree, id) => {
            for (const n of tree) {
                if (n.children) {
                    const idx = n.children.findIndex(c => c.id === id);
                    if (idx !== -1) return { parent: n, index: idx };
                    const found = findParentAndIndex(n.children, id);
                    if (found) return found;
                }
            }
            // Root check
            const rootIdx = this.state.rootContainer.children.findIndex(c => c.id === id);
            if (rootIdx !== -1) return { parent: this.state.rootContainer, index: rootIdx };
            return null;
        };

        const info = findParentAndIndex(this.state.rootContainer.children, nodeId);
        if (!info) return;

        const { parent, index } = info;
        const arr = parent.children;
        
        if (action === 'up' && index > 0) { // Enviar para trás (início do array) -> renderiza antes
            const temp = arr[index];
            arr[index] = arr[index - 1];
            arr[index - 1] = temp;
        } else if (action === 'down' && index < arr.length - 1) { // Trazer para frente (fim do array) -> renderiza depois
            const temp = arr[index];
            arr[index] = arr[index + 1];
            arr[index + 1] = temp;
        } else {
            return; // No change
        }

        this.bus.emit('tree:changed');
    }

    render() {
        this.dom.innerHTML = this.state.rootContainer.children.map(node => this._renderNode(node)).join('');
        this._itemsById = {};
        this.dom.querySelectorAll('.layer-item').forEach(el => {
            this._itemsById[el.dataset.nodeId] = el;
        });
        this.highlight();
    }

    _renderNode(node) {
        const isActive = this.state.activeNode && this.state.activeNode.id === node.id;
        const meta = BuilderFlex.WidgetRegistry.get(node.type);
        const icon = (meta && meta.icon) ? meta.icon : 'fa-cube';
        
        let html = `
            <div class="layer-node">
                <div class="layer-item ${isActive ? 'active' : ''}" data-node-id="${node.id}">
                    <i class="fas ${icon} layer-icon"></i>
                    <span class="layer-name">${BuilderFlex.escape(node.props.name)}</span>
                    <div class="layer-actions">
                        <button type="button" class="btn-layer-action" data-action="down" title="Trazer para Frente"><i class="fas fa-arrow-up"></i></button>
                        <button type="button" class="btn-layer-action" data-action="up" title="Enviar para Trás"><i class="fas fa-arrow-down"></i></button>
                    </div>
                </div>
        `;

        if (node.canHaveChildren() && node.children.length > 0) {
            html += `<div class="layer-children">`;
            html += node.children.map(child => this._renderNode(child)).join('');
            html += `</div>`;
        }

        html += `</div>`;
        return html;
    }

    highlight() {
        const activeId = this.state.activeNode?.id;
        Object.values(this._itemsById || {}).forEach(el => el.classList.remove('active'));
        if (activeId && this._itemsById[activeId]) {
            this._itemsById[activeId].classList.add('active');
            // Scroll para ficar visível (suave)
            this._itemsById[activeId].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
};
