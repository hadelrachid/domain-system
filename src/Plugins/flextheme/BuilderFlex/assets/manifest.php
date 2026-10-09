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
        'responsive.css',
        'paragraph.css',
    ],

    'js' => [
        // Núcleo
        'core/namespace.js',
        'core/escape.js',
        'core/EventBus.js',
        'core/BreakpointManager.js',
        'core/IViewportResolver.js',

        // Layout Strategies
        'layout/ILayoutStrategy.js',
        'layout/AbsoluteLayout.js',
        'layout/FlowLayout.js',
        'layout/GridLayout.js',

        // Widgets (VCL): Registry -> Base -> filhos (cada filho se auto-registra)
        'widgets/WidgetRegistry.js',
        'widgets/BaseWidget.js',
        'widgets/AbstractContainerWidget.js',
        'widgets/ContainerWidget.js',
        'widgets/TextWidget.js',
        'widgets/ButtonWidget.js',
        'widgets/ImageWidget.js',
        'widgets/LayoutWidgets.js',
        'widgets/GridWidget.js',
        'widgets/SectionWidget.js',
        'widgets/ShortcodeWidget.js',
        'widgets/ParagraphWidget.js',

        // Estado
        'state/EditorState.js',
        'state/NodeTree.js',

        // Interface
        'ui/CanvasRenderer.js',
        'ui/PalettePanel.js',
        'ui/ExportModal.js',
        'ui/ContextMenu.js',
        'ui/LayerNavigator.js',

        // Object Inspector
        'inspector/FieldFactory.js',
        'inspector/InspectorPanel.js',

        // Interação e exportação
        'interaction/DragController.js',
        'interaction/ResizeController.js',
        'export/JsonExporter.js',
        'core/ParagraphEditor.js',

        // Composição e bootstrap
        'core/Editor.js',
        'main.js',
    ],
];
