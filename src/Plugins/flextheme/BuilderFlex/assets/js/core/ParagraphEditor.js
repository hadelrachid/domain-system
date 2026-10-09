/**
 * ParagraphEditor — Gerencia a interação WYSIWYG
 */
BuilderFlex.ParagraphEditor = class ParagraphEditor {
    constructor(bus, state, canvasRoot) {
        this.bus = bus;
        this.state = state;
        this.canvasRoot = canvasRoot;
        this._debounceTimer = null;
        this.bindEvents();
    }

    bindEvents() {
        // Usa mousedown e preventDefault para não perder o foco/seleção do texto!
        this.canvasRoot.addEventListener('mousedown', (ev) => {
            const btn = ev.target.closest('.paragraph-toolbar [data-cmd]');
            if (!btn) return;
            
            ev.preventDefault(); // <-- CRÍTICO: Impede que o botão roube o foco do contenteditable!
            ev.stopPropagation();

            const cmd = btn.dataset.cmd;
            const value = btn.dataset.value;

            if (cmd === 'createLink') {
                const url = prompt('Digite a URL do link:', 'https://');
                if (url && url !== 'https://') {
                    document.execCommand('createLink', false, url);
                }
            } else if (cmd === 'formatBlock') {
                document.execCommand('formatBlock', false, `<${value}>`);
            } else {
                document.execCommand(cmd, false, value || null);
            }

            this._syncContentFromDom(btn.closest('.widget-node'));
        });

        this.canvasRoot.addEventListener('input', (ev) => {
            if (!ev.target.classList.contains('paragraph-content')) return;
            this._debouncedSync(ev.target);
        });

        this.canvasRoot.addEventListener('keydown', (ev) => {
            if (!ev.target.classList.contains('paragraph-content')) return;
            ev.stopPropagation(); // Previne deletar o widget ao dar backspace
        });
    }

    _debouncedSync(contentEl) {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
            this._syncContentFromDom(contentEl.closest('.widget-node'));
        }, 400);
    }

    _syncContentFromDom(widgetEl) {
        if (!widgetEl) return;
        const nodeId = widgetEl.dataset.nodeId;
        const node = BuilderFlex.NodeTree.find(this.state.tree, nodeId);
        if (!node) return;

        const contentEl = widgetEl.querySelector('.paragraph-content');
        if (!contentEl) return;

        node.props.html = contentEl.innerHTML;
        // Atualiza silenciosamente para não recriar o DOM (pois perderia o foco do cursor)
        this.bus.emit('node:silent-update', node); 
    }
};
