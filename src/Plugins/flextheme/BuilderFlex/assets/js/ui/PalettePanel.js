/**
 * PalettePanel — monta a paleta de componentes a partir do WidgetRegistry.
 * Registrou um widget novo? Ele aparece aqui sozinho.
 */
BuilderFlex.PalettePanel = class PalettePanel {
    constructor(containerElement) {
        this.container = containerElement;
    }

    render() {
        const esc = BuilderFlex.escape;
        this.container.innerHTML = BuilderFlex.WidgetRegistry.paletteItems().map(item => `
            <div class="palette-item" data-palette-type="${esc(item.type)}">
                <i class="fas ${esc(item.icon)}"></i> ${esc(item.label)}
            </div>`).join('');
    }
};
