/**
 * JsonExporter — serializa a árvore de componentes para JSON.
 */
BuilderFlex.JsonExporter = {
    serialize(tree) {
        const serializeNode = node => ({
            id: node.id,
            type: node.type,
            props: node.props,
            tablet: node.tablet,
            mobile: node.mobile,
            children: node.children.map(serializeNode)
        });
        return JSON.stringify(tree.map(serializeNode), null, 2);
    }
};
