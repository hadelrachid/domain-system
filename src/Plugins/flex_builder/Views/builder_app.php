<?php
use DomainSystem\Plugins\flex_builder\src\Widgets\ButtonWidget;
use DomainSystem\Plugins\flex_builder\src\Registry\ComponentRegistry;

// Inicializa o Registry e registra o botão (No futuro isso virá de um Bootstrapper)
$registry = ComponentRegistry::getInstance();
$registry->registerComponent(new ButtonWidget());
$schemaJson = $registry->exportSchemaForFrontend();
?>

<div class="ds-card">
    <div class="ds-card-header">
        <h2><i class="fas fa-object-group" style="color: #38bdf8; margin-right: 10px;"></i> Flex-Builder (VCL)</h2>
        <p style="color: #94a3b8; font-size: 13px;">O Motor de Renderização Orientado a Objetos</p>
    </div>
    
    <div class="ds-card-body" style="display: flex; gap: 20px; height: 600px;">
        
        <!-- Painel Lateral: Paleta de Componentes -->
        <div style="width: 250px; background: #0f172a; border-right: 1px solid #1e293b; padding: 15px; border-radius: 8px;">
            <h3 style="font-size: 14px; margin-top: 0; color: #f8fafc; border-bottom: 1px solid #334155; padding-bottom: 10px;">Paleta de Blocos</h3>
            <div id="component-palette" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <!-- Preenchido via JS -->
            </div>
        </div>

        <!-- Área Central: Palco de Edição -->
        <div style="flex: 1; background: #ffffff; border-radius: 8px; border: 2px dashed #64748b; padding: 20px; overflow-y: auto; position: relative;">
            <div id="builder-stage" style="min-height: 100%;">
                <!-- Área de arrastar e soltar -->
                <div style="text-align: center; color: #94a3b8; margin-top: 200px;">
                    <i class="fas fa-arrows-alt" style="font-size: 30px; margin-bottom: 15px;"></i>
                    <p>Arraste um componente para cá</p>
                </div>
            </div>
        </div>
        
        <!-- Painel Direito: Inspetor de Objetos (Object Inspector) -->
        <div style="width: 300px; background: #0f172a; border-left: 1px solid #1e293b; padding: 15px; border-radius: 8px; overflow-y: auto;">
            <h3 style="font-size: 14px; margin-top: 0; color: #f8fafc; border-bottom: 1px solid #334155; padding-bottom: 10px;">Object Inspector</h3>
            <div id="object-inspector" style="margin-top: 15px;">
                <p style="color: #64748b; font-size: 12px; text-align: center; margin-top: 50px;">Selecione um bloco no palco para editar suas propriedades.</p>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Carrega o Schema injetado pelo PHP
    const componentSchema = <?= $schemaJson ?>;
    const palette = document.getElementById("component-palette");

    // Popula a Paleta com os Blocos disponíveis
    for (const [id, config] of Object.entries(componentSchema)) {
        let btn = document.createElement("button");
        btn.innerHTML = `<i class="fas fa-cube"></i> ${config.name}`;
        btn.style.cssText = "background: #1e293b; color: #38bdf8; border: 1px solid #334155; padding: 10px; border-radius: 4px; cursor: grab; text-align: left; font-size: 12px;";
        
        // Simulação simples de clique para "adicionar" ao palco
        btn.onclick = () => {
            alert("Em breve: Você adicionou o bloco '" + config.name + "' ao palco. O Object Inspector será preenchido com as " + Object.keys(config.properties).length + " propriedades (width, height, color, etc).");
        };

        palette.appendChild(btn);
    }
});
</script>
