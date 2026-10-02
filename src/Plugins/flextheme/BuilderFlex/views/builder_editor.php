<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder Flex Engine (Standalone)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #121212;
            --bg-panel: #1e1e1e;
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-strong: rgba(255, 255, 255, 0.15);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --accent-neon: #00e5ff;
            --danger: #ff4444;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            overflow: hidden;
        }

        .top-navbar {
            height: 60px;
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border-glass-strong);
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
        }

        .btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn:hover { background: rgba(255, 255, 255, 0.15); }
        .btn-activate { background: rgba(0, 229, 255, 0.1); color: var(--accent-neon); border-color: rgba(0, 229, 255, 0.3); }

        /* Base Layout */
        .builder-layout { display: flex; height: calc(100vh - 60px); gap: 0; overflow: hidden; user-select: none; }
        
        /* Palette (Left) */
        .builder-palette { width: 250px; background: var(--bg-panel); border-right: 1px solid var(--border-glass-strong); display: flex; flex-direction: column; z-index: 10; }
        .palette-header { padding: 15px; font-weight: bold; border-bottom: 1px solid var(--border-glass); color: var(--text-primary); }
        .palette-item { padding: 10px 15px; cursor: grab; border-bottom: 1px solid var(--border-glass); color: var(--text-secondary); display: flex; align-items: center; gap: 10px; transition: 0.2s; }
        .palette-item:hover { background: rgba(0, 229, 255, 0.05); color: var(--accent-neon); }
        
        /* Canvas Workspace (Center) */
        .builder-canvas-wrapper { flex: 1; display: flex; flex-direction: column; position: relative; overflow: hidden; background: #2b2b2b; }
        .canvas-toolbar { height: 50px; border-bottom: 1px solid #1a1a1a; display: flex; align-items: center; padding: 0 15px; gap: 10px; background: #1f1f1f; z-index: 10; justify-content: center; }
        
        /* THE FORM/CANVAS (Absolute Dotted Grid) */
        #canvas-root { 
            position: absolute; 
            top: 50px; left: 0; right: 0; bottom: 0;
            background-color: #f0f0f0;
            background-image: radial-gradient(#d0d0d0 1px, transparent 1px);
            background-size: 10px 10px;
            overflow: auto;
            /* Transição para modo mobile */
            transition: width 0.3s ease, margin 0.3s ease;
        }

        /* Responsive View Classes */
        #canvas-root.view-mobile {
            width: 375px;
            margin: 0 auto;
            position: relative;
            height: calc(100% - 50px);
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            border-left: 1px solid #444;
            border-right: 1px solid #444;
        }

        /* Widgets visual clues */
        .widget-node { position: absolute; box-sizing: border-box; cursor: pointer; }
        .widget-node:hover { outline: 1px solid rgba(0, 229, 255, 0.5); }
        .widget-node.active { outline: 2px solid var(--accent-neon) !important; z-index: 1000 !important; }
        
        /* Object Inspector (Right) */
        .builder-inspector { width: 300px; background: var(--bg-panel); border-left: 1px solid var(--border-glass-strong); display: flex; flex-direction: column; z-index: 10; }
        .inspector-header { padding: 15px; font-weight: bold; border-bottom: 1px solid var(--border-glass); color: var(--text-primary); }
        .inspector-body { flex: 1; overflow-y: auto; padding: 15px; }
        
        .prop-group { margin-bottom: 15px; }
        .prop-group label { display: block; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 5px; }
        .prop-input { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--border-glass); background: rgba(0,0,0,0.2); color: var(--text-primary); font-family: monospace; font-size: 12px; }
        
        /* Drag Overlay */
        #drag-overlay { position: fixed; top:0; left:0; width:100%; height:100%; z-index:9999; display:none; }
    </style>
</head>
<body>

    <div class="top-navbar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-paint-brush" style="color: var(--accent-neon); font-size: 20px;"></i>
            <h2 style="margin: 0; font-size: 18px;">Builder Flex <span style="font-size: 12px; color: var(--text-secondary);">by FlexTheme</span></h2>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php $baseUrl = defined('BASE_URL') ? BASE_URL : ''; ?>
            <a href="<?= $baseUrl ?>/admin" class="btn" style="background: rgba(255, 68, 68, 0.1); border-color: rgba(255, 68, 68, 0.3); color: #ff4444; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-sign-out-alt"></i> Sair do Editor
            </a>
            <button type="button" class="btn btn-activate" onclick="Engine.exportJSON()"><i class="fas fa-code"></i> Exportar JSON</button>
        </div>
    </div>

    <div class="builder-layout">
        <!-- PALETTE -->
        <div class="builder-palette">
            <div class="palette-header"><i class="fas fa-cubes"></i> VCL Components</div>
            <div style="flex: 1; overflow-y: auto;">
                <div class="palette-item" onmousedown="Engine.paletteDragStart(event, 'ContainerWidget')">
                    <i class="fas fa-box"></i> TPanel (Container)
                </div>
                <div class="palette-item" onmousedown="Engine.paletteDragStart(event, 'TextWidget')">
                    <i class="fas fa-font"></i> TLabel (Texto)
                </div>
                <div class="palette-item" onmousedown="Engine.paletteDragStart(event, 'ButtonWidget')">
                    <i class="fas fa-mouse-pointer"></i> TButton (Botão)
                </div>
                <div class="palette-item" onmousedown="Engine.paletteDragStart(event, 'ImageWidget')">
                    <i class="fas fa-image"></i> TImage (Imagem)
                </div>
            </div>
        </div>

        <!-- CANVAS -->
        <div class="builder-canvas-wrapper">
            <div class="canvas-toolbar">
                <button class="btn" style="padding: 5px 10px;" onclick="document.getElementById('canvas-root').className=''"><i class="fas fa-desktop"></i> Desktop</button>
                <button class="btn" style="padding: 5px 10px;" onclick="document.getElementById('canvas-root').className='view-mobile'"><i class="fas fa-mobile-alt"></i> Mobile</button>
                <div style="margin-left: auto;">
                    <span style="color: #999; font-size: 12px; margin-right: 15px;"><i class="fas fa-th"></i> Snap: 10px</span>
                    <button class="btn" style="padding: 5px 10px; background: rgba(239, 68, 68, 0.2); color: #ff6b6b; font-size: 12px;" onclick="Engine.clearCanvas()">Limpar Form</button>
                </div>
            </div>
            
            <div id="canvas-root">
                <!-- DOM renderizado aqui -->
            </div>
            <div id="drag-overlay" onmousemove="Engine.canvasMouseMove(event)" onmouseup="Engine.canvasMouseUp(event)"></div>
        </div>

        <!-- OBJECT INSPECTOR -->
        <div class="builder-inspector">
            <div class="inspector-header"><i class="fas fa-sliders-h"></i> Object Inspector</div>
            <div class="inspector-body" id="inspector-body">
                <div style="text-align: center; color: var(--text-secondary); padding: 20px; font-size: 13px;">
                    Selecione um objeto no form.
                </div>
            </div>
        </div>
    </div>

    <!-- Modais para Exportação -->
    <div id="modalExport" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div style="background: var(--bg-panel); padding: 30px; border-radius: 12px; width: 600px; border: 1px solid var(--border-glass);">
            <h3 style="margin-top: 0; color: var(--text-primary);">Código JSON Exportado</h3>
            <textarea id="exportJsonOutput" style="width: 100%; height: 300px; background: #121212; color: #00ff00; font-family: monospace; padding: 10px; border: 1px solid #333;" readonly></textarea>
            <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
                <button type="button" class="btn" onclick="document.getElementById('modalExport').style.display='none'">Fechar</button>
            </div>
        </div>
    </div>

    <!-- OOP JS ENGINE (VCL Abstraction) -->
    <script>
        class BaseWidget {
            constructor(id) {
                this.id = id || 'comp_' + Math.random().toString(36).substr(2, 9);
                this.type = 'BaseWidget';
                this.isContainer = false; 
                this.props = {
                    name: 'BaseControl', left: 20, top: 20, width: 150, height: 50,
                    margin: '0px', padding: '0px', display: 'block', flexDirection: 'row',
                    justifyContent: 'flex-start', alignItems: 'stretch', gap: '0px',
                    backgroundColor: 'transparent', border: 'none', borderRadius: 0,
                    boxShadow: 'none', opacity: 1, zIndex: 1
                };
                this.children = [];
            }
            getBaseStyles() {
                return `
                    position: absolute;
                    left: ${this.props.left}px;
                    top: ${this.props.top}px;
                    width: ${this.props.width}${typeof this.props.width === 'number' ? 'px' : ''};
                    height: ${this.props.height}${typeof this.props.height === 'number' ? 'px' : ''};
                    margin: ${this.props.margin};
                    padding: ${this.props.padding};
                    display: ${this.props.display};
                    flex-direction: ${this.props.flexDirection};
                    justify-content: ${this.props.justifyContent};
                    align-items: ${this.props.alignItems};
                    gap: ${this.props.gap};
                    background-color: ${this.props.backgroundColor};
                    border: ${this.props.border};
                    border-radius: ${this.props.borderRadius}px;
                    box-shadow: ${this.props.boxShadow};
                    opacity: ${this.props.opacity};
                    z-index: ${this.props.zIndex};
                `;
            }
            renderHTML(innerContent = '') {
                return `<div id="${this.id}" class="widget-node" style="${this.getBaseStyles()}" 
                    onmousedown="Engine.nodeMouseDown(event, '${this.id}')">${innerContent}</div>`;
            }
            hydrate(data) {
                this.id = data.id; this.type = data.type; this.props = { ...this.props, ...data.props };
                if (data.children) {
                    for (let childData of data.children) {
                        let childInstance = WidgetRegistry.createInstance(childData.type, childData.id);
                        childInstance.hydrate(childData);
                        this.children.push(childInstance);
                    }
                }
            }
        }

        class ContainerWidget extends BaseWidget {
            constructor(id) {
                super(id);
                this.type = 'ContainerWidget'; this.isContainer = true;
                this.props.name = 'TPanel'; this.props.width = 300; this.props.height = 200;
                this.props.backgroundColor = '#ffffff'; this.props.border = '1px solid #cccccc';
                this.props.padding = '15px'; this.props.display = 'flex';
                this.props.flexDirection = 'column'; this.props.gap = '10px';
            }
        }

        class TextWidget extends BaseWidget {
            constructor(id) {
                super(id);
                this.type = 'TextWidget'; this.props.name = 'TLabel'; this.props.width = 120;
                this.props.height = 30; this.props.text = 'Texto Exemplo'; this.props.color = '#333333';
                this.props.fontSize = 16; this.props.fontFamily = 'Arial, sans-serif';
                this.props.fontWeight = 'normal'; this.props.textAlign = 'left';
            }
            renderHTML() {
                const styles = this.getBaseStyles() + `color: ${this.props.color}; font-size: ${this.props.fontSize}px; font-family: ${this.props.fontFamily}; font-weight: ${this.props.fontWeight}; text-align: ${this.props.textAlign}; display: flex; align-items: center;`;
                return `<div id="${this.id}" class="widget-node" style="${styles}" onmousedown="Engine.nodeMouseDown(event, '${this.id}')">${this.props.text}</div>`;
            }
        }

        class ButtonWidget extends BaseWidget {
            constructor(id) {
                super(id);
                this.type = 'ButtonWidget'; this.props.name = 'TButton'; this.props.width = 150;
                this.props.height = 40; this.props.text = 'Clique Aqui'; this.props.backgroundColor = '#007bff';
                this.props.color = '#ffffff'; this.props.borderRadius = 4; this.props.link = '#'; this.props.cursor = 'pointer';
            }
            renderHTML() {
                const styles = this.getBaseStyles() + `color: ${this.props.color}; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; cursor: ${this.props.cursor};`;
                return `<a href="${this.props.link}" id="${this.id}" class="widget-node" style="${styles}" onmousedown="Engine.nodeMouseDown(event, '${this.id}')">${this.props.text}</a>`;
            }
        }

        class ImageWidget extends BaseWidget {
            constructor(id) {
                super(id);
                this.type = 'ImageWidget'; this.props.name = 'TImage'; this.props.width = 200;
                this.props.height = 150; this.props.src = 'https://via.placeholder.com/200x150?text=Imagem';
                this.props.objectFit = 'cover';
            }
            renderHTML() {
                const styles = this.getBaseStyles() + `object-fit: ${this.props.objectFit};`;
                return `<img src="${this.props.src}" id="${this.id}" class="widget-node" style="${styles}" onmousedown="Engine.nodeMouseDown(event, '${this.id}')" draggable="false">`;
            }
        }

        const WidgetRegistry = {
            classes: { BaseWidget, ContainerWidget, TextWidget, ButtonWidget, ImageWidget },
            createInstance: function(type, id = null) {
                if (this.classes[type]) return new this.classes[type](id);
                return new BaseWidget(id);
            }
        };

        const Engine = {
            state: {
                tree: [], activeNode: null,
                isDragging: false, dragNodeId: null,
                dragStartX: 0, dragStartY: 0, nodeStartX: 0, nodeStartY: 0,
                gridSize: 10
            },

            renderCanvas: function() {
                const root = document.getElementById('canvas-root');
                let html = '';
                this.state.tree.forEach(node => { html += this.renderNodeTree(node); });
                root.innerHTML = html;
                if (this.state.activeNode) {
                    const el = document.getElementById(this.state.activeNode.id);
                    if (el) el.classList.add('active');
                }
            },

            renderNodeTree: function(node) {
                let childrenHtml = '';
                if (node.isContainer && node.children.length > 0) {
                    node.children.forEach(child => { childrenHtml += this.renderNodeTree(child); });
                }
                return node.renderHTML(childrenHtml);
            },

            paletteDragStart: function(ev, type) {
                ev.preventDefault();
                const inst = WidgetRegistry.createInstance(type);
                this.state.tree.push(inst);
                this.state.activeNode = inst;
                this.renderCanvas();
                this.nodeMouseDown(ev, inst.id, true);
            },

            nodeMouseDown: function(ev, id, isNew = false) {
                ev.preventDefault(); ev.stopPropagation();
                const node = this.findNode(id);
                if (!node) return;
                this.state.activeNode = node;
                this.renderInspector();
                this.renderCanvas(); 
                this.state.isDragging = true;
                this.state.dragNodeId = id;
                this.state.dragStartX = ev.clientX; this.state.dragStartY = ev.clientY;
                this.state.nodeStartX = parseInt(node.props.left) || 0;
                this.state.nodeStartY = parseInt(node.props.top) || 0;
                document.getElementById('drag-overlay').style.display = 'block';
            },

            canvasMouseMove: function(ev) {
                if (!this.state.isDragging || !this.state.dragNodeId) return;
                const node = this.findNode(this.state.dragNodeId);
                if (!node) return;
                const deltaX = ev.clientX - this.state.dragStartX;
                const deltaY = ev.clientY - this.state.dragStartY;
                let rawLeft = this.state.nodeStartX + deltaX;
                let rawTop = this.state.nodeStartY + deltaY;
                node.props.left = Math.round(rawLeft / this.state.gridSize) * this.state.gridSize;
                node.props.top = Math.round(rawTop / this.state.gridSize) * this.state.gridSize;
                const el = document.getElementById(node.id);
                if(el) { el.style.left = node.props.left + 'px'; el.style.top = node.props.top + 'px'; }
                const iLeft = document.getElementById('prop_left'); const iTop = document.getElementById('prop_top');
                if(iLeft) iLeft.value = node.props.left; if(iTop) iTop.value = node.props.top;
            },

            canvasMouseUp: function(ev) {
                if (this.state.isDragging) {
                    this.state.isDragging = false;
                    this.state.dragNodeId = null;
                    document.getElementById('drag-overlay').style.display = 'none';
                    this.renderCanvas();
                }
            },

            findNode: function(id, tree = this.state.tree) {
                for (let node of tree) {
                    if (node.id === id) return node;
                    if (node.children) {
                        const found = this.findNode(id, node.children);
                        if (found) return found;
                    }
                }
                return null;
            },

            deleteActiveNode: function() {
                if (!this.state.activeNode) return;
                this.deleteNode(this.state.activeNode.id, this.state.tree);
                this.state.activeNode = null;
                this.renderCanvas(); this.renderInspector();
            },
            
            deleteNode: function(id, tree) {
                for (let i = 0; i < tree.length; i++) {
                    if (tree[i].id === id) { tree.splice(i, 1); return true; }
                    if (tree[i].children && this.deleteNode(id, tree[i].children)) return true;
                }
                return false;
            },

            renderInspector: function() {
                const panel = document.getElementById('inspector-body');
                const node = this.state.activeNode;
                if (!node) { panel.innerHTML = '<div style="text-align: center; color: var(--text-secondary); padding: 20px;">Selecione um objeto no form.</div>'; return; }
                
                let html = `<div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-glass);">
                        <strong style="color: var(--accent-neon);">${node.props.name}</strong> <span style="color: #666; font-size: 10px;">${node.type}</span>
                        <div style="color: #444; font-size: 9px; margin-top:2px;">ID: ${node.id}</div>
                        <button class="btn" style="width: 100%; margin-top: 10px; background: rgba(239, 68, 68, 0.1); color: var(--danger); font-size: 12px; padding: 5px;" onclick="Engine.deleteActiveNode()"><i class="fas fa-trash"></i> Delete (Del)</button>
                    </div>`;
                
                const categories = {
                    "Posição e Tamanho": ['left', 'top', 'width', 'height', 'margin', 'padding'],
                    "Flex e Layout (Grid)": ['display', 'flexDirection', 'justifyContent', 'alignItems', 'gap'],
                    "Estética (Visual)": ['backgroundColor', 'border', 'borderRadius', 'boxShadow', 'opacity', 'zIndex', 'color'],
                    "Tipografia e Conteúdo": ['text', 'fontSize', 'fontFamily', 'fontWeight', 'textAlign', 'src', 'link', 'objectFit']
                };

                for (let catName in categories) {
                    let catHtml = '';
                    categories[catName].forEach(key => {
                        if (node.props[key] !== undefined) catHtml += this.generateInputFor(key, node.props[key]);
                    });
                    if (catHtml !== '') html += `<div style="margin-bottom: 15px;"><div style="font-weight: bold; color: var(--text-primary); font-size: 11px; text-transform: uppercase; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid rgba(255,255,255,0.05);">${catName}</div>${catHtml}</div>`;
                }
                
                let uncategorized = '';
                for (let key of Object.keys(node.props)) {
                    if (key === 'name') continue;
                    let found = false;
                    Object.values(categories).forEach(arr => { if (arr.includes(key)) found = true; });
                    if (!found) uncategorized += this.generateInputFor(key, node.props[key]);
                }
                if (uncategorized !== '') html += `<div style="margin-bottom: 15px;"><div style="font-weight: bold; color: var(--text-primary); font-size: 11px; text-transform: uppercase; margin-bottom: 10px;">Extra</div>${uncategorized}</div>`;
                panel.innerHTML = html;
            },
            
            generateInputFor: function(key, val) {
                let inputType = 'text';
                if (key.toLowerCase().includes('color')) inputType = 'color';
                if (typeof val === 'number') inputType = 'number';
                
                if (key === 'display') return `<div class="prop-group"><label>${key}</label><select class="prop-input" id="prop_${key}" onchange="Engine.updateProp('${key}', this.value)"><option value="block" ${val === 'block'?'selected':''}>block</option><option value="flex" ${val === 'flex'?'selected':''}>flex</option><option value="none" ${val === 'none'?'selected':''}>none</option></select></div>`;
                if (key === 'flexDirection') return `<div class="prop-group"><label>${key}</label><select class="prop-input" id="prop_${key}" onchange="Engine.updateProp('${key}', this.value)"><option value="row" ${val === 'row'?'selected':''}>row (Horizontal)</option><option value="column" ${val === 'column'?'selected':''}>column (Vertical)</option></select></div>`;
                if (key === 'justifyContent' || key === 'alignItems') return `<div class="prop-group"><label>${key}</label><select class="prop-input" id="prop_${key}" onchange="Engine.updateProp('${key}', this.value)"><option value="flex-start" ${val === 'flex-start'?'selected':''}>flex-start (Início)</option><option value="center" ${val === 'center'?'selected':''}>center (Centro)</option><option value="flex-end" ${val === 'flex-end'?'selected':''}>flex-end (Fim)</option><option value="space-between" ${val === 'space-between'?'selected':''}>space-between</option><option value="stretch" ${val === 'stretch'?'selected':''}>stretch (Esticar)</option></select></div>`;
                if (key === 'text' || key === 'src') return `<div class="prop-group"><label>${key}</label><textarea class="prop-input" rows="2" id="prop_${key}" onkeyup="Engine.updateProp('${key}', this.value)">${val}</textarea></div>`;
                return `<div class="prop-group"><label>${key}</label><input type="${inputType}" class="prop-input" id="prop_${key}" value="${val}" oninput="Engine.updateProp('${key}', this.value)"></div>`;
            },

            updateProp: function(key, val) {
                if (!this.state.activeNode) return;
                if (typeof this.state.activeNode.props[key] === 'number') val = parseInt(val) || 0;
                this.state.activeNode.props[key] = val;
                this.renderCanvas();
            },
            
            clearCanvas: function() {
                if(confirm('Tem certeza? Todo o form será apagado.')) {
                    this.state.tree = []; this.state.activeNode = null;
                    this.renderCanvas(); this.renderInspector();
                }
            },

            exportJSON: function() {
                const serializeNode = (node) => {
                    return { id: node.id, type: node.type, props: node.props, children: node.children.map(c => serializeNode(c)) };
                };
                const finalTree = this.state.tree.map(n => serializeNode(n));
                document.getElementById('exportJsonOutput').value = JSON.stringify(finalTree, null, 2);
                document.getElementById('modalExport').style.display = 'flex';
            }
        };

        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Delete' || ev.key === 'Backspace') {
                if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
                Engine.deleteActiveNode();
            }
        });
        window.addEventListener('DOMContentLoaded', () => { Engine.renderCanvas(); });
    </script>
</body>
</html>

