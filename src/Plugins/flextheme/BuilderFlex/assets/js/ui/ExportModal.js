/**
 * ExportModal — janela que exibe o JSON exportado.
 */
BuilderFlex.ExportModal = class ExportModal {
    constructor(modalElement, outputElement) {
        this.modal = modalElement;
        this.output = outputElement;
    }

    open(jsonText) {
        this.output.value = jsonText;
        this.modal.style.display = 'flex';
    }

    close() {
        this.modal.style.display = 'none';
    }
};
