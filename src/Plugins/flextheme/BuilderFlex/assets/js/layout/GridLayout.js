/**
 * GridLayout - Posição de grid CSS
 */
BuilderFlex.GridLayout = class GridLayout extends BuilderFlex.FlowLayout {
    containerStyles(container, bp) {
        return '';
    }

    // herda childStyles do FlowLayout (position relative)
};
