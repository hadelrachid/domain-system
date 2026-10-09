/**
 * EditorState - fonte única da verdade do editor (somente dados, sem DOM).
 */
BuilderFlex.RootContainer = class RootContainer {
    constructor(state) {
        this.id = 'root';
        this.type = 'RootContainer';
        this.state = state;
    }
    
    get children() { return this.state.tree; }
    
    canHaveChildren() { return true; }
    canAccept(child) { return true; }
    
    getLayoutStrategy() { return new BuilderFlex.FlowLayout(); }
    
    addChild(widget, index = null) {
        if (index !== null && index >= 0 && index <= this.state.tree.length) {
            this.state.tree.splice(index, 0, widget);
        } else {
            this.state.tree.push(widget);
        }
    }
};

BuilderFlex.EditorState = class EditorState {
    constructor() {
        this.tree = [];          // Componentes raiz do formulário
        this.activeNode = null;  // Componente selecionado no Object Inspector
        this.gridSize = 10;      // Snap-to-grid (px)
        this.drag = { active: false, nodeId: null, startX: 0, startY: 0, nodeStartX: 0, nodeStartY: 0 };
        this.rootContainer = new BuilderFlex.RootContainer(this);
    }
};
