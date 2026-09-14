/**
 * DS Kernel - Global Namespace
 */
window.DS = window.DS || {};

// Configurações globais expostas pelo backend
DS.config = {
    baseUrl: window.DS_BASE_URL || (document.querySelector('base') ? document.querySelector('base').getAttribute('href') : '/'),
};
