/**
 * DS Events - Event Bus
 */
window.DS = window.DS || {};

DS.events = {
    _listeners: {},

    on(event, callback) {
        if (!this._listeners[event]) {
            this._listeners[event] = [];
        }
        this._listeners[event].push(callback);
    },

    off(event, callback) {
        if (!this._listeners[event]) return;
        this._listeners[event] = this._listeners[event].filter(cb => cb !== callback);
    },

    emit(event, data = {}) {
        if (!this._listeners[event]) return;
        this._listeners[event].forEach(callback => {
            try {
                callback(data);
            } catch (err) {
                console.error(`[DS.events] Erro no listener do evento '${event}':`, err);
            }
        });
    }
};
