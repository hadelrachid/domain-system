/**
 * WidgetRegistry — catálogo de widgets (VCL) disponíveis no editor.
 *
 * Aberto/Fechado: cada widget se auto-registra no próprio arquivo. A paleta e a
 * hidratação do JSON consultam este registro; nada fica "hard-coded" no editor.
 *
 * meta: { label, icon, palette: bool }  (palette=false => não aparece na paleta)
 */
FlexBuilder.WidgetRegistry = (function () {
    const entries = {};

    return {
        register(type, ctor, meta = {}) {
            entries[type] = { ctor, meta };
        },

        createInstance(type, id = null) {
            const entry = entries[type];
            return entry ? new entry.ctor(id) : new FlexBuilder.BaseWidget(id);
        },

        /** Itens que devem aparecer na paleta de componentes (ordem de registro). */
        paletteItems() {
            return Object.entries(entries)
                .filter(([, entry]) => entry.meta.palette)
                .map(([type, entry]) => ({ type, label: entry.meta.label, icon: entry.meta.icon }));
        }
    };
})();
