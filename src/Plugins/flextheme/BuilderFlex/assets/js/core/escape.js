/**
 * Utilitário de escape HTML (valores de props entram em atributos e conteúdo).
 */
BuilderFlex.escape = function escape(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};
