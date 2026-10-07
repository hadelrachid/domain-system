/**
 * JsonExporter — serializa a árvore de componentes para JSON.
 */
FlexBuilder.JsonExporter = {
    serialize(tree) {
        const serializeNode = node => ({
            id: node.id,
            type: node.type,
            props: node.props,
            children: node.children.map(serializeNode)
        });
        return JSON.stringify(tree.map(serializeNode), null, 2);
    }
};
