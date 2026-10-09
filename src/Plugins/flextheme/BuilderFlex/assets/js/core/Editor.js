/**
 * Editor - raiz de composição (Composition Root) do BuilderFlex.
 */
BuilderFlex.Editor = class Editor {
    constructor(dom) {
        this.dom = dom;
        this.bus = new BuilderFlex.EventBus();
        this.state = new BuilderFlex.EditorState();
        this.breakpoints = new BuilderFlex.BreakpointManager(this.bus);

        this.canvas = new BuilderFlex.CanvasRenderer(dom.canvasRoot, this.state, this.breakpoints);
        this.palette = new BuilderFlex.PalettePanel(dom.paletteList);
        this.inspector = new BuilderFlex.InspectorPanel(dom.inspectorBody, this.state, this.breakpoints);
        this.drag = new BuilderFlex.DragController(this.state, this.bus, dom.dragOverlay);
        this.resize = new BuilderFlex.ResizeController(this.state, this.bus, dom.dragOverlay);
        this.exportModal = new BuilderFlex.ExportModal(dom.exportModal, dom.exportOutput);
        this.contextMenu = new BuilderFlex.ContextMenu(dom.contextMenu, this.bus, this.state);
        this.paragraphEditor = new BuilderFlex.ParagraphEditor(this.bus, this.state, dom.canvasRoot);
        
        // Novo Layer Navigator
        this.layers = new BuilderFlex.LayerNavigator(dom.layerList, this.state, this.bus);
        
    }

    start() {
        window.BuilderFlex._editingMode = true;
        this.subscribe();
        this.palette.render();
        this.canvas.render();
        this.inspector.render(null);
        this.bindDom();
    }

    subscribe() {
        this.bus
            .on('tree:changed', () => {
                this.canvas.render();
                this.layers.render();
            })
            .on('selection:changed', () => {
                this.inspector.render(this.state.activeNode);
                this.canvas.highlight();
                this.layers.highlight();
            })
            .on('node:moved', node => {
                this.canvas.moveElement(node);
                this.inspector.syncField('left', node.props.left);
                this.inspector.syncField('top', node.props.top);
            })
            .on('breakpoint:changed', () => {
                this.canvas.render();
                this.inspector.render(this.state.activeNode);
            });
    }

    findParent(tree, childId) {
        for (const node of tree) {
            if (node.children && node.children.some(c => c.id === childId)) return node;
            const found = this.findParent(node.children || [], childId);
            if (found) return found;
        }
        return null;
    }

    addWidget(type) {
        const widget = BuilderFlex.WidgetRegistry.createInstance(type);
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
        BuilderFlex.NodeTree.remove(this.state.tree, node.id);
        this.state.activeNode = null;
        this.bus.emit('tree:changed');
        this.bus.emit('selection:changed');
    }

    updateProp(rawKey, rawValue) {
        const node = this.state.activeNode;
        if (!node) return;

        const bp = this.breakpoints.get();
        let value = rawValue;

        if (rawKey.startsWith('attrs.')) {
            const attrKey = rawKey.split('.')[1];
            node.props.attrs = node.props.attrs || {};
            node.props.attrs[attrKey] = value;
            this.bus.emit('tree:changed');
            return;
        }

        if (typeof node.props[rawKey] === 'number') {
            value = parseFloat(rawValue);
            if (Number.isNaN(value)) value = 0;
        }

        if (rawKey === 'columnsDesktop' || rawKey === 'columnsTablet' || rawKey === 'columnsMobile') {
            node.setColumns(parseInt(value), bp);
        } else {
            node.setProp(rawKey, value, bp);
        }

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
        this.exportModal.open(BuilderFlex.JsonExporter.serialize(this.state.tree));
    }

    bindDom() {
        const { canvasRoot, paletteList, layerList, inspectorBody, dragOverlay } = this.dom;

        // Abas da Paleta
        const tabs = document.querySelectorAll('.palette-tabs .btn-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (ev) => {
                tabs.forEach(t => t.classList.remove('active'));
                ev.target.classList.add('active');

                const targetId = ev.target.dataset.target;
                if (paletteList) paletteList.hidden = (targetId !== 'palette-list');
                if (layerList) layerList.hidden = (targetId !== 'layer-list');
            });
        });

        paletteList.addEventListener('mousedown', ev => {
            const item = ev.target.closest('[data-palette-type]');
            if (!item) return;
            ev.preventDefault();
            this.drag.begin(this.addWidget(item.dataset.paletteType), ev);
        });

        canvasRoot.addEventListener('mousedown', ev => {
            if (ev.button === 2) return;

            const resizeHandle = ev.target.closest('.resize-handle');
            if (resizeHandle && this.state.activeNode) {
                ev.preventDefault();
                ev.stopPropagation();
                this.resize.begin(this.state.activeNode, resizeHandle.dataset.dir, ev);
                return;
            }
            
            const el = ev.target.closest('.widget-node');
            if (!el) {
                // Clicou fora (no fundo vazio do stage) -> Limpa a seleção
                this.state.activeNode = null;
                this.bus.emit('selection:changed');
                return;
            }
            
            const node = BuilderFlex.NodeTree.find(this.state.tree, el.dataset.nodeId);
            if (!node) return;
            
            this.select(node);

            // Se for editável, NÃO inicia o arraste e não previne default (para o cursor focar)
            if (ev.target.closest('.paragraph-content') || ev.target.closest('.paragraph-toolbar')) {
                return; 
            }

            ev.preventDefault();
            ev.stopPropagation();
            this.drag.begin(node, ev);
        });

        canvasRoot.addEventListener('contextmenu', ev => {
            const el = ev.target.closest('.widget-node');
            if (!el) return;
            ev.preventDefault();
            ev.stopPropagation();
            const node = BuilderFlex.NodeTree.find(this.state.tree, el.dataset.nodeId);
            if (!node) return;
            this.select(node);
            this.contextMenu.open(ev, node);
        });

        dragOverlay.addEventListener('mousemove', ev => {
            this.drag.move(ev);
            this.resize.move(ev);
        });
        dragOverlay.addEventListener('mouseup', ev => {
            this.drag.end(ev);
            this.resize.end(ev);
        });

        inspectorBody.addEventListener('input', ev => {
            // Se for o color picker mudando
            if (ev.target.matches('.prop-color-picker')) {
                const key = ev.target.dataset.sync;
                const textInput = inspectorBody.querySelector(`[data-prop="${key}"]`);
                if (textInput) {
                    textInput.value = ev.target.value;
                    this.updateProp(key, ev.target.value);
                }
                return;
            }

            // Se for o campo de texto normal (ou select, ou checkbox)
            const key = ev.target.dataset.prop;
            if (key) {
                const value = ev.target.type === 'checkbox' ? ev.target.checked : ev.target.value;
                this.updateProp(key, value);
                
                // Two-way binding visual pro color picker
                if (ev.target.matches('.prop-input')) {
                    const colorPicker = ev.target.parentElement.querySelector('.prop-color-picker');
                    if (colorPicker && ev.target.value.startsWith('#') && ev.target.value.length === 7) {
                        colorPicker.value = ev.target.value;
                    }
                }
            }
        });

        const toggleBtn = document.getElementById('toggle-inspector');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const panel = document.getElementById('builder-inspector');
                panel.classList.toggle('collapsed');
            });
        }

        const togglePaletteBtn = document.getElementById('toggle-palette');
        if (togglePaletteBtn) {
            togglePaletteBtn.addEventListener('click', () => {
                const panel = document.getElementById('builder-palette');
                panel.classList.toggle('collapsed');
            });
        }

        const actions = {
            'delete-node':   () => this.deleteActive(),
            'clear-canvas':  () => this.clear(),
            'export-json':   () => this.exportJson(),
            'close-export':  () => this.exportModal.close(),
            'view-desktop':  () => { this.breakpoints.set('base'); this.canvas.setViewMode('desktop'); },
            'view-mobile':   () => { this.breakpoints.set('mobile'); this.canvas.setViewMode('mobile'); },
            'switch-breakpoint': (trigger) => {
                const bp = trigger.dataset.breakpoint;
                if (bp) {
                    this.breakpoints.set(bp);
                    this.canvas.setViewMode(bp === 'base' ? 'desktop' : bp);
                }
            }
        };

        document.addEventListener('click', ev => {
            const trigger = ev.target.closest('[data-action]');
            const action = trigger && actions[trigger.dataset.action];
            if (action) action(trigger);
        });

        document.addEventListener('keydown', ev => {
            if (ev.key !== 'Delete' && ev.key !== 'Backspace') return;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
            this.deleteActive();
        });
    }
};
