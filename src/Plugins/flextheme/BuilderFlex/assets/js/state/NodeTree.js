/**
 * NodeTree — operações puras sobre a árvore de componentes (busca/remoção).
 */
BuilderFlex.NodeTree = {
    find(tree, id) {
        for (const node of tree) {
            if (node.id === id) return node;
            const found = this.find(node.children || [], id);
            if (found) return found;
        }
        return null;
    },

    remove(tree, id) {
        for (let i = 0; i < tree.length; i++) {
            if (tree[i].id === id) {
                tree.splice(i, 1);
                return true;
            }
            if (this.remove(tree[i].children || [], id)) return true;
        }
        return false;
    }
};
