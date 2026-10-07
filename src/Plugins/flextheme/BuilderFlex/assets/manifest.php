<?php

/**
 * Manifesto dos módulos front-end do Builder Flex.
 *
 * A ORDEM IMPORTA (dependências primeiro). Para criar um novo módulo/widget:
 * 1. Crie o arquivo em assets/js/... ou assets/css/...
 * 2. Adicione UMA linha aqui. Nenhum outro arquivo precisa mudar.
 */
return [
    'css' => [
        'base.css',
        'layout.css',
        'inspector.css',
        'modal.css',
    ],

    'js' => [
        // Núcleo
        'core/namespace.js',
        'core/escape.js',
        'core/EventBus.js',

        // Widgets (VCL): Registry -> Base -> filhos (cada filho se auto-registra)
        'widgets/WidgetRegistry.js',
        'widgets/BaseWidget.js',
        'widgets/ContainerWidget.js',
        'widgets/TextWidget.js',
        'widgets/ButtonWidget.js',
        'widgets/ImageWidget.js',

        // Estado
        'state/EditorState.js',
        'state/NodeTree.js',

        // Interface
        'ui/CanvasRenderer.js',
        'ui/PalettePanel.js',
        'ui/ExportModal.js',

        // Object Inspector
        'inspector/FieldFactory.js',
        'inspector/InspectorPanel.js',

        // Interação e exportação
        'interaction/DragController.js',
        'export/JsonExporter.js',

        // Composição e bootstrap
        'core/Editor.js',
        'main.js',
    ],
];
