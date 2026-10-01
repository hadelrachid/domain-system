/**
 * DS UI Toast
 */
window.DS = window.DS || {};
window.DS.ui = window.DS.ui || {};

DS.ui.toast = {
    _container: null,

    _init() {
        if (!this._container) {
            this._container = document.createElement('div');
            this._container.id = 'ds-toast-container';
            Object.assign(this._container.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: '9999',
                display: 'flex',
                flexDirection: 'column',
                gap: '10px'
            });
            document.body.appendChild(this._container);
        }
    },

    show(message, type = 'info') {
        this._init();

        const toast = document.createElement('div');
        toast.className = `ds-toast ds-toast-${type}`;
        
        const colors = {
            success: { bg: '#d1e7dd', color: '#0f5132', border: '#badbcc' },
            error: { bg: '#f8d7da', color: '#842029', border: '#f5c2c7' },
            warning: { bg: '#fff3cd', color: '#664d03', border: '#ffecb5' },
            info: { bg: '#cff4fc', color: '#055160', border: '#b6effb' }
        };

        const theme = colors[type] || colors.info;

        Object.assign(toast.style, {
            backgroundColor: theme.bg,
            color: theme.color,
            border: `1px solid ${theme.border}`,
            padding: '12px 18px',
            borderRadius: '4px',
            fontSize: '14px',
            boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
            minWidth: '250px',
            maxWidth: '350px',
            transition: 'opacity 0.3s ease-in-out',
            fontFamily: 'system-ui, -apple-system, sans-serif'
        });

        toast.textContent = message;
        this._container.appendChild(toast);

        // Auto remove after 3s
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg) { this.show(msg, 'info'); }
};

// Escuta eventos globais de toast para disparar UI
if (window.DS && window.DS.events) {
    window.DS.events.on('ui:toast', (data) => {
        DS.ui.toast.show(data.message || data, data.type || 'info');
    });
}
