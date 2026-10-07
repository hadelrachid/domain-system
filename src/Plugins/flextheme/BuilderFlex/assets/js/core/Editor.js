/**
 * Editor — raiz de composição (Composition Root) do Builder Flex.
 *
 * Instancia os módulos, liga-os pelo EventBus e traduz eventos de DOM em ações.
 * Não renderiza nada diretamente: delega para CanvasRenderer, InspectorPanel,
 * PalettePanel, DragController, JsonExporter e ExportModal.
 */
FlexBuilder.Editor = class Editor {
    constructor(dom) {
        this.dom = dom;
        this.bus = new FlexBuilder.EventBus();
        this.state = new FlexBuilder.EditorState();

        this.canvas = new FlexBuilder.CanvasRenderer(dom.canvasRoot, this.state);
        this.palette = new FlexBuilder.PalettePanel(dom.paletteList);
        this.inspector = new FlexBuilder.InspectorPanel(dom.inspectorBody);
        this.drag = new FlexBuilder.DragController(this.state, this.bus, dom.dragOverlay);
        this.exportModal = new FlexBuilder.ExportModal(dom.exportModal, dom.exportOutput);
    }

    start() {
        this.subscribe();
        this.palette.render();
        this.canvas.render();
        this.inspector.render(null);
        this.bindDom();
    }

    // ── Reações a eventos do domínio ─────────────────────────────────────
    subscribe() {
        this.bus
            .on('tree:changed', () => this.canvas.render())
            .on('selection:changed', () => {
                this.inspector.render(this.state.activeNode);
                this.canvas.highlight();
            })
            .on('node:moved', node => {
                this.canvas.moveElement(node);
                this.inspector.syncField('left', node.props.left);
                this.inspector.syncField('top', node.props.top);
            });
    }

    // ── Ações ────────────────────────────────────────────────────────────
    addWidget(type) {
        const widget = FlexBuilder.WidgetRegistry.createInstance(type);
        this.state.tree.push(widget);
        this.state.activeNode = widget;
        this.bus.emit('tree:changed');
        this.bus.emit('selection:changed');
        return widget;
    }

    select(node) {
        this.state.activeNode = node;
        this.bus.emit('selection:changed');
    }

    deleteActive() {
        const node = this.state.activeNode;
        if (!node) return;
        FlexBuilder.NodeTree.remove(this.state.tree, node.id);
        this.state.activeNode = null;
        this.bus.emit('tree:changed');
        this.bus.emit('selection:changed');
    }

    updateProp(key, rawValue) {
        const node = this.state.activeNode;
        if (!node) return;

        let value = rawValue;
        if (typeof node.props[key] === 'number') {
            value = parseFloat(rawValue);
            if (Number.isNaN(value)) value = 0;
        }
        node.props[key] = value;
        this.bus.emit('tree:changed');
    }

    clear() {
        if (!confirm('Tem certeza? Todo o form será apagado.')) return;
        this.state.tree = [];
        this.state.activeNode = null;
        this.bus.emit('tree:changed');
        this.bus.emit('selection:changed');
    }

    exportJson() {
        this.exportModal.open(FlexBuilder.JsonExporter.serialize(this.state.tree));
    }

    // ── Ligação com o DOM (event delegation) ─────────────────────────────
    bindDom() {
        const { canvasRoot, paletteList, inspectorBody, dragOverlay } = this.dom;

        // Paleta: criar widget e já começar a arrastá-lo
        paletteList.addEventListener('mousedown', ev => {
            const item = ev.target.closest('[data-palette-type]');
            if (!item) return;
            ev.preventDefault();
            this.drag.begin(this.addWidget(item.dataset.paletteType), ev);
        });

        // Canvas: selecionar e arrastar o componente mais interno sob o mouse
        canvasRoot.addEventListener('mousedown', ev => {
            const el = ev.target.closest('.widget-node');
            if (!el) return;
            ev.preventDefault();
            ev.stopPropagation();
            const node = FlexBuilder.NodeTree.find(this.state.tree, el.dataset.nodeId);
            if (!node) return;
            this.select(node);
            this.drag.begin(node, ev);
        });

        dragOverlay.addEventListener('mousemove', ev => this.drag.move(ev));
        dragOverlay.addEventListener('mouseup', () => this.drag.end());

        // Inspetor: editar propriedades
        inspectorBody.addEventListener('input', ev => {
            const key = ev.target.dataset.prop;
            if (key) this.updateProp(key, ev.target.value);
        });

        // Botões com data-action
        const actions = {
            'delete-node':   () => this.deleteActive(),
            'clear-canvas':  () => this.clear(),
            'export-json':   () => this.exportJson(),
            'close-export':  () => this.exportModal.close(),
            'view-desktop':  () => this.canvas.setViewMode('desktop'),
            'view-mobile':   () => this.canvas.setViewMode('mobile')
        };
        document.addEventListener('click', ev => {
            const trigger = ev.target.closest('[data-action]');
            const action = trigger && actions[trigger.dataset.action];
            if (action) action();
        });

        // Teclado: Delete remove o componente (exceto ao digitar em campos)
        document.addEventListener('keydown', ev => {
            if (ev.key !== 'Delete' && ev.key !== 'Backspace') return;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
            this.deleteActive();
        });
    }
};
