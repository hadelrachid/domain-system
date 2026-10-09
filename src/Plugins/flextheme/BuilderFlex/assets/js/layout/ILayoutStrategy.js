/**
 * ILayoutStrategy - Contrato para estratégias de layout (o Conector)
 */
BuilderFlex.ILayoutStrategy = class ILayoutStrategy {
    /**
     * Aplica regras de drop (reposiciona o nó na árvore ou recalcula coords)
     */
    place(container, child, dropPoint) {
        throw new Error('ILayoutStrategy.place() must be implemented');
    }

    /**
     * Retorna o CSS do próprio container, se necessário
     */
    containerStyles(container, bp) {
        return '';
    }

    /**
     * Retorna o CSS de um filho sob esta estratégia
     */
    childStyles(container, child, bp) {
        throw new Error('ILayoutStrategy.childStyles() must be implemented');
    }
};
