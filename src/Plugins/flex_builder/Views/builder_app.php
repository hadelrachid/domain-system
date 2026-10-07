<?php
use DomainSystem\Plugins\flex_builder\src\Widgets\ButtonWidget;
use DomainSystem\Plugins\flex_builder\src\Widgets\ContainerWidget;
use DomainSystem\Plugins\flex_builder\src\Registry\ComponentRegistry;

// Inicializa o Registry e registra o botão e o painel (No futuro isso virá de um Bootstrapper)
$registry = ComponentRegistry::getInstance();
$registry->registerComponent(new ButtonWidget());
$registry->registerComponent(new ContainerWidget());
$schemaJson = $registry->exportSchemaForFrontend();
?>

<style>
    /* Resetando o layout do painel admin para o construtor ocupar a tela inteira de verdade (Escondendo o menu lateral do OS) */
    .ds-builder-layout {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 99999; /* Sobrepõe tudo */
        display: flex;
        flex-direction: column;
        background-color: #0f172a;
        color: #f8fafc;
        overflow: hidden;
    }
    
    /* Barra Horizontal Superior (Navegação/Ações) */
    .ds-builder-topbar {
        height: 50px;
        background-color: #020617;
        border-bottom: 1px solid #1e293b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
    }

    .ds-builder-topbar-tools {
        display: flex;
        gap: 15px;
    }

    .ds-builder-btn {
        background: #1e293b;
        color: #94a3b8;
        border: 1px solid #334155;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        transition: 0.2s;
    }
    .ds-builder-btn:hover { background: #334155; color: #fff; }
    .ds-builder-btn-primary { background: #38bdf8; color: #0f172a; border: none; font-weight: bold; }
    .ds-builder-btn-primary:hover { background: #0284c7; }

    /* Área Inferior (Paletas e Palco) */
    .ds-builder-workspace {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    /* Barra Vertical Esquerda (Componentes) */
    .ds-builder-sidebar-left {
        width: 260px;
        background-color: #0f172a;
        border-right: 1px solid #1e293b;
        display: flex;
        flex-direction: column;
        overflow-y: auto; /* Rolagem na paleta */
    }

    /* Palco Central (Onde o tema ganha vida) */
    .ds-builder-canvas {
        flex: 1;
        background-color: #e2e8f0; 
        background-image: linear-gradient(45deg, #cbd5e1 25%, transparent 25%, transparent 75%, #cbd5e1 75%, #cbd5e1), 
                          linear-gradient(45deg, #cbd5e1 25%, transparent 25%, transparent 75%, #cbd5e1 75%, #cbd5e1);
        background-size: 20px 20px;
        background-position: 0 0, 10px 10px;
        overflow: auto; /* Rolagem horizontal e vertical ativada */
        padding: 40px;
        /* Usando block e margin auto no filho para evitar corte de scroll do flexbox */
        display: block;
    }

    .ds-canvas-paper {
        background: #ffffff;
        margin: 0 auto; /* Centraliza a folha horizontalmente */
        width: 100%;
        min-width: 800px; /* Garante rolagem horizontal se a tela for pequena */
        max-width: 1200px;
        min-height: 1200px; /* Garante rolagem vertical abundante para o stage */
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border: 1px solid #94a3b8;
        position: relative;
    }

    /* Barra Vertical Direita (Object Inspector) */
    .ds-builder-sidebar-right {
        width: 300px;
        background-color: #0f172a;
        border-left: 1px solid #1e293b;
        display: flex;
        flex-direction: column;
        overflow-y: auto; /* Rolagem no inspetor de objetos */
    }

    .ds-sidebar-header {
        padding: 15px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #1e293b;
        color: #94a3b8;
        background: #020617;
    }
</style>

<div class="ds-builder-layout">
    
    <!-- Barra Horizontal de Navegação -->
    <div class="ds-builder-topbar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/domain-system/admin" style="color: #ef4444; text-decoration: none; font-size: 20px; margin-right: 10px;" title="Sair do Construtor"><i class="fas fa-times-circle"></i></a>
            <i class="fas fa-layer-group" style="color: #38bdf8; font-size: 20px;"></i>
            <strong style="font-size: 15px;">Flex Theme Builder</strong>
            
            <div style="border-left: 1px solid #334155; margin-left: 10px; padding-left: 20px; display: flex; gap: 10px;">
                <button class="ds-builder-btn"><i class="fas fa-desktop"></i></button>
                <button class="ds-builder-btn"><i class="fas fa-tablet-alt"></i></button>
                <button class="ds-builder-btn"><i class="fas fa-mobile-alt"></i></button>
            </div>
        </div>

        <div class="ds-builder-topbar-tools">
            <select class="ds-builder-btn" style="appearance: auto;">
                <option style="background: #0f172a; color: #f8fafc;">Editando: Cabeçalho (Header)</option>
                <option style="background: #0f172a; color: #f8fafc;">Editando: Rodapé (Footer)</option>
                <option style="background: #0f172a; color: #f8fafc;">Editando: Página Inicial</option>
            </select>
            <button class="ds-builder-btn"><i class="fas fa-cog"></i> Configurações do Tema</button>
            <button class="ds-builder-btn ds-builder-btn-primary"><i class="fas fa-save"></i> Salvar Tema</button>
        </div>
    </div>

    <!-- Espaço de Trabalho (Workspace) -->
    <div class="ds-builder-workspace">
        
        <!-- Barra Vertical Esquerda: Paleta Delphi -->
        <div class="ds-builder-sidebar-left">
            <div class="ds-sidebar-header"><i class="fas fa-cubes"></i> VCL Components</div>
            <div id="component-palette" style="padding: 15px; display: flex; flex-direction: column; gap: 10px; overflow-y: auto;">
                <!-- Preenchido via JS -->
            </div>
        </div>

        <!-- Palco Central -->
        <div class="ds-builder-canvas">
            <div class="ds-canvas-paper" id="builder-stage">
                <!-- Dropzone de montagem visual -->
                <div class="empty-stage-msg" style="text-align: center; color: #94a3b8; margin-top: 200px;">
                    <i class="fas fa-tools" style="font-size: 40px; margin-bottom: 20px;"></i>
                    <h3 style="margin:0;">Canvas do Tema</h3>
                    <p style="font-size: 13px;">Arraste os componentes VCL para construir o layout.</p>
                </div>
            </div>
        </div>

        <!-- Barra Vertical Direita: Object Inspector -->
        <div class="ds-builder-sidebar-right">
            <div class="ds-sidebar-header"><i class="fas fa-sliders-h"></i> Object Inspector</div>
            <div id="object-inspector" style="padding: 15px; overflow-y: auto;">
                <div style="text-align: center; color: #475569; margin-top: 50px;">
                    <i class="fas fa-mouse-pointer" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p style="font-size: 12px;">Selecione um bloco no palco para editar (Cores, Fontes, Margens).</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
/**
 * Flex-Builder JS Engine
 * O Cérebro Front-End do Construtor de Layouts
 */
class BuilderEngine {
    constructor(schema) {
        this.schema = schema;
        this.virtualDom = [];
        this.selectedWidgetId = null;
        
        this.stageElement = document.getElementById("builder-stage");
        this.paletteElement = document.getElementById("component-palette");
        
        this.init();
    }

    init() {
        console.log("🚀 Flex-Builder JS Engine Iniciada!");
        this.renderPalette();
        this.setupDragAndDrop();
    }

    renderPalette() {
        this.paletteElement.innerHTML = "";

        for (const [widgetId, config] of Object.entries(this.schema)) {
            let btn = document.createElement("button");
            btn.innerHTML = `<i class="fas fa-cube" style="margin-right:8px; color: #38bdf8;"></i> ${config.name}`;
            btn.className = "vcl-palette-item";
            
            btn.style.cssText = "background: #1e293b; color: #f8fafc; border: 1px solid #334155; padding: 12px; border-radius: 6px; cursor: grab; text-align: left; font-size: 13px; display: flex; align-items: center; transition: background 0.2s;";
            btn.onmouseover = () => btn.style.background = "#334155";
            btn.onmouseout = () => btn.style.background = "#1e293b";

            btn.draggable = true;
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', widgetId);
                btn.style.opacity = '0.5';
            });
            btn.addEventListener('dragend', (e) => {
                btn.style.opacity = '1';
            });

            this.paletteElement.appendChild(btn);
        }
    }

    setupDragAndDrop() {
        this.stageElement.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.stageElement.style.border = "2px dashed #38bdf8";
        });

        this.stageElement.addEventListener('dragleave', (e) => {
            this.stageElement.style.border = "1px solid #94a3b8";
        });

        this.stageElement.addEventListener('drop', (e) => {
            e.preventDefault();
            this.stageElement.style.border = "1px solid #94a3b8";
            
            const droppedWidgetId = e.dataTransfer.getData('text/plain');
            if (this.schema[droppedWidgetId]) {
                this.addComponentToStage(droppedWidgetId);
            }
        });
    }

    addComponentToStage(widgetId) {
        const config = this.schema[widgetId];
        
        let fakeNode = document.createElement("div");
        fakeNode.style.cssText = "padding: 20px; background: #f1f5f9; border: 1px solid #cbd5e1; margin-bottom: 10px; cursor: pointer;";
        fakeNode.innerHTML = `<strong>${config.name}</strong> [Simulação]`;
        
        const emptyMsg = this.stageElement.querySelector('.empty-stage-msg');
        if (emptyMsg) emptyMsg.remove();

        this.stageElement.appendChild(fakeNode);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const componentSchema = <?= $schemaJson ?>;
    window.Builder = new BuilderEngine(componentSchema);
});
</script>
