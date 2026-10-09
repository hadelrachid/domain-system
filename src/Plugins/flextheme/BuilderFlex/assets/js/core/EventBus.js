/**
 * EventBus — Observer mínimo.
 * Desacopla os módulos: quem muda o estado apenas emite um evento,
 * quem precisa reagir (canvas, inspetor...) assina. Ninguém conhece ninguém.
 *
 * Eventos usados:
 *  - tree:changed       a árvore de componentes mudou (re-renderizar o canvas)
 *  - selection:changed  outro componente foi selecionado
 *  - node:moved         um componente foi arrastado (payload: node)
 */
BuilderFlex.EventBus = class EventBus {
    constructor() {
        this.handlers = {};
    }

    on(eventName, handler) {
        (this.handlers[eventName] = this.handlers[eventName] || []).push(handler);
        return this;
    }

    emit(eventName, payload) {
        (this.handlers[eventName] || []).forEach(handler => handler(payload));
    }
};
