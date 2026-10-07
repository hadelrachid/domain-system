/**
 * EditorState — fonte única da verdade do editor (somente dados, sem DOM).
 */
FlexBuilder.EditorState = class EditorState {
    constructor() {
        this.tree = [];          // Componentes raiz do formulário
        this.activeNode = null;  // Componente selecionado no Object Inspector
        this.gridSize = 10;      // Snap-to-grid (px)
        this.drag = { active: false, nodeId: null, startX: 0, startY: 0, nodeStartX: 0, nodeStartY: 0 };
    }
};
