/**
 * IViewportResolver - Contrato de visualização
 * Uma só fonte de verdade para como os nós resolvem suas propriedades baseadas em breakpoint.
 */
BuilderFlex.IViewportResolver = class IViewportResolver {
    static getBreakpoints() {
        return [
            { id: 'base', name: 'Desktop', maxWidth: null, canvasWidth: '100%' },
            { id: 'tablet', name: 'Tablet', maxWidth: 1024, canvasWidth: '768px' },
            { id: 'mobile', name: 'Mobile', maxWidth: 767, canvasWidth: '375px' }
        ];
    }

    static getResponsiveProps() {
        return [
            'width', 'height', 'minHeight', 'padding', 'margin', 
            'gap', 'fontSize', 'textAlign', 'display', 'layout', 
            'hidden', 'order'
        ];
    }

    static resolve(node, breakpoint) {
        const result = { props: {}, layout: 'absolute', hidden: false, order: 0 };
        const bps = this.getBreakpoints();
        
        // Cascata: base -> tablet -> mobile
        const chain = ['base'];
        if (breakpoint === 'tablet') chain.push('tablet');
        if (breakpoint === 'mobile') chain.push('tablet', 'mobile');

        for (const prop of this.getResponsiveProps()) {
            let val = undefined;
            for (const bp of chain) {
                const nodeVal = node.getRawProp(prop, bp);
                if (nodeVal !== undefined && nodeVal !== null && nodeVal !== '') {
                    val = nodeVal;
                }
            }
            result.props[prop] = val;
        }

        result.layout = result.props.layout || 'absolute';
        result.hidden = result.props.hidden === true || result.props.hidden === 'true';
        result.order = parseInt(result.props.order) || 0;

        return result;
    }
};
