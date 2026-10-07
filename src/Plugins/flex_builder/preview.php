<?php
/**
 * Área de Visualização (Live Preview) do Flex Builder
 */
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/src/Contracts/VisualComponentInterface.php';
require_once __DIR__ . '/src/Widgets/AbstractVisualWidget.php';
require_once __DIR__ . '/src/Widgets/ButtonWidget.php';
require_once __DIR__ . '/src/Registry/ComponentRegistry.php';

use DomainSystem\Plugins\flex_builder\src\Widgets\ButtonWidget;
use DomainSystem\Plugins\flex_builder\src\Registry\ComponentRegistry;

$registry = ComponentRegistry::getInstance();
$registry->registerComponent(new ButtonWidget());

// Simula propriedades enviadas pelo banco
$jsonSalvoNoBanco = '{
    "widget": "basic_button",
    "props": {
        "text": "COMPRAR AGORA",
        "bg_color": "#10b981",
        "border_radius": "50px",
        "padding": "15px 30px",
        "text_color": "#ffffff",
        "action_url": "https://google.com",
        "width": "300px",
        "margin": "20px auto"
    }
}';
$dados = json_decode($jsonSalvoNoBanco, true);
$widget = $registry->getComponent($dados['widget']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Laboratório de Visualização - Flex Builder</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .monitor { background: #1e293b; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 80%; max-width: 800px; text-align: center; }
        .code-box { background: #020617; padding: 15px; border-radius: 8px; font-family: monospace; text-align: left; margin-bottom: 30px; font-size: 13px; color: #38bdf8;}
    </style>
</head>
<body>
    <div class="monitor">
        <h2>🛠️ Laboratório de Renderização VCL</h2>
        <p style="color: #94a3b8; font-size: 14px;">Este é o resultado do JSON gerando HTML nativo (sem iframe):</p>
        
        <div class="code-box">
            <?= nl2br(htmlspecialchars($jsonSalvoNoBanco)) ?>
        </div>

        <div style="background: #ffffff; padding: 50px; border-radius: 8px; border: 2px dashed #64748b;">
            <?php 
                if ($widget) {
                    echo $widget->render($dados['props']);
                }
            ?>
        </div>
    </div>
</body>
</html>
