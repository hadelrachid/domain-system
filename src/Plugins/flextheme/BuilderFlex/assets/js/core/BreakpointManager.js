/**
 * BreakpointManager — Fonte única de verdade sobre qual breakpoint está
 * sendo editado no momento. Centraliza a lógica para evitar que ela fique
 * espalhada entre Editor, CanvasRenderer e InspectorPanel (SRP).
 */
BuilderFlex.BreakpointManager = class BreakpointManager {
    constructor(bus) {
        this.bus = bus;
        this.current = 'base';
        this.widths = { base: '100%', tablet: '768px', mobile: '375px' };
    }

    set(breakpoint) {
        if (!['base', 'tablet', 'mobile'].includes(breakpoint)) {
            console.warn(`Breakpoint inválido: ${breakpoint}`);
            return;
        }
        if (this.current === breakpoint) return;
        this.current = breakpoint;
        this.bus.emit('breakpoint:changed', breakpoint);
    }

    get() { return this.current; }

    getWidth() { return this.widths[this.current]; }

    isMobile() { return this.current === 'mobile'; }
    isTablet() { return this.current === 'tablet'; }
    isDesktop() { return this.current === 'base'; }
};
