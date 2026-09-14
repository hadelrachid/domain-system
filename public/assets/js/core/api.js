/**
 * DS API - HTTP Client
 */
window.DS = window.DS || {};

DS.api = {
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : DS.config.baseUrl + endpoint.replace(/^\/+/, '');
        
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        };

        // Se for FormData, o próprio fetch define o Content-Type com os boundaries corretos
        if (config.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);
            const contentType = response.headers.get('content-type');
            let data = null;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json().catch(() => null);
            } else {
                data = await response.text();
            }

            if (!response.ok) {
                const errorMsg = data?.message || (typeof data === 'string' ? data : `Erro HTTP: ${response.status}`);
                throw new Error(errorMsg);
            }

            return data;
        } catch (error) {
            console.error('[DS.api Error]', error);
            if (DS.events) DS.events.emit('api:error', error);
            if (DS.ui && DS.ui.toast) DS.ui.toast.error(error.message || 'Ocorreu um erro inesperado.');
            throw error;
        }
    },

    get(endpoint, options = {}) {
        return this.request(endpoint, { ...options, method: 'GET' });
    },

    post(endpoint, body, options = {}) {
        const isFormData = body instanceof FormData;
        
        const config = { ...options, method: 'POST' };
        
        if (isFormData) {
            config.body = body;
            config.headers = config.headers || {};
            // fetch defines multipart boundary automatically when passing FormData
            delete config.headers['Content-Type']; 
        } else if (typeof body === 'string') {
            config.body = body;
        } else {
            config.body = JSON.stringify(body);
        }

        return this.request(endpoint, config);
    }
};
