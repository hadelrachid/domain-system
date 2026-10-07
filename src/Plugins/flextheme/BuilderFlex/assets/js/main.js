/**
 * Bootstrap — localiza os elementos da página e liga o Editor.
 */
(function () {
    const boot = () => {
        const byId = id => document.getElementById(id);

        FlexBuilder.editor = new FlexBuilder.Editor({
            canvasRoot:    byId('canvas-root'),
            dragOverlay:   byId('drag-overlay'),
            paletteList:   byId('palette-list'),
            inspectorBody: byId('inspector-body'),
            exportModal:   byId('modalExport'),
            exportOutput:  byId('exportJsonOutput')
        });
        FlexBuilder.editor.start();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
