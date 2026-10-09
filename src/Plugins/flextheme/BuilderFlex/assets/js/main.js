/**
 * Bootstrap — localiza os elementos da página e liga o Editor.
 */
(function () {
    const boot = () => {
        const byId = id => document.getElementById(id);

        BuilderFlex.editor = new BuilderFlex.Editor({
            canvasRoot:    byId('canvas-root'),
            dragOverlay:   byId('drag-overlay'),
            paletteList:   byId('palette-list'),
            layerList:     byId('layer-list'),
            inspectorBody: byId('inspector-body'),
            exportModal:   byId('modalExport'),
            exportOutput:  byId('exportJsonOutput'),
            contextMenu:   byId('context-menu')
        });
        BuilderFlex.editor.start();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
