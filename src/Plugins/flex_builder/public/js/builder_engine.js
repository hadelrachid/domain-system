/**
 * Flex-Builder JS Engine
 * O Cérebro Front-End do Construtor de Layouts
 */

class BuilderEngine {
    constructor(schema) {
        this.schema = schema; // O catálogo completo de VCLs vindos do PHP
        this.virtualDom = []; // A Árvore de componentes que está no Stage
        this.selectedWidgetId = null; // Qual componente está focado no Object Inspector
        
        this.stageElement = document.getElementById("builder-stage");
        this.paletteElement = document.getElementById("component-palette");
        
        this.init();
    }

    init() {
        console.log("🚀 Flex-Builder JS Engine Iniciada!");
        this.renderPalette();
        this.setupDragAndDrop();
    }

    /**
     * Pega o JSON do PHP e cria os botões arrastáveis na paleta esquerda
     */
    renderPalette() {
        this.paletteElement.innerHTML = ""; // Limpa

        for (const [widgetId, config] of Object.entries(this.schema)) {
            let btn = document.createElement("button");
            btn.innerHTML = `<i class="fas fa-cube" style="margin-right:8px; color: #38bdf8;"></i> ${config.name}`;
            btn.className = "vcl-palette-item";
            
            // Estilização
            btn.style.cssText = "background: #1e293b; color: #f8fafc; border: 1px solid #334155; padding: 12px; border-radius: 6px; cursor: grab; text-align: left; font-size: 13px; display: flex; align-items: center; transition: background 0.2s;";
            btn.onmouseover = () => btn.style.background = "#334155";
            btn.onmouseout = () => btn.style.background = "#1e293b";

            // Ativa o DRAG (Arrastar) nativo do HTML5
            btn.draggable = true;
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', widgetId);
                btn.style.opacity = '0.5'; // Deixa o botão translúcido enquanto arrasta
            });
            btn.addEventListener('dragend', (e) => {
                btn.style.opacity = '1';
            });

            this.paletteElement.appendChild(btn);
        }
    }

    /**
     * Configura o Stage (Papel Branco) para ACEITAR itens arrastados (DROP)
     */
    setupDragAndDrop() {
        // Quando um item está "sobreando" o papel branco
        this.stageElement.addEventListener('dragover', (e) => {
            e.preventDefault(); // Necessário para permitir o Drop
            this.stageElement.style.border = "2px dashed #38bdf8"; // Feedback visual verde/azul
        });

        // Quando o item sai do papel branco sem soltar
        this.stageElement.addEventListener('dragleave', (e) => {
            this.stageElement.style.border = "1px solid #94a3b8"; // Volta ao normal
        });

        // Quando o usuário SOLTA o mouse em cima do papel branco
        this.stageElement.addEventListener('drop', (e) => {
            e.preventDefault();
            this.stageElement.style.border = "1px solid #94a3b8"; // Volta ao normal
            
            // Qual ID de componente ele soltou?
            const droppedWidgetId = e.dataTransfer.getData('text/plain');
            
            if (this.schema[droppedWidgetId]) {
                this.addComponentToStage(droppedWidgetId);
            }
        });
    }

    /**
     * Injeta fisicamente o componente no Stage
     */
    addComponentToStage(widgetId) {
        const config = this.schema[widgetId];
        
        // No futuro, isso será substituído por uma requisição Ajax que pega o HTML real do PHP
        let fakeNode = document.createElement("div");
        fakeNode.style.cssText = "padding: 20px; background: #f1f5f9; border: 1px solid #cbd5e1; margin-bottom: 10px; cursor: pointer;";
        fakeNode.innerHTML = `<strong>${config.name}</strong> [Simulação]`;
        
        // Remove a mensagem vazia central, se existir
        const emptyMsg = this.stageElement.querySelector('.empty-stage-msg');
        if (emptyMsg) emptyMsg.remove();

        // Adiciona ao DOM
        this.stageElement.appendChild(fakeNode);
        
        console.log(`📦 Instância de [${config.name}] criada com sucesso!`);
        alert(`O motor JS identificou que você soltou um ${config.name}!`);
    }
}
