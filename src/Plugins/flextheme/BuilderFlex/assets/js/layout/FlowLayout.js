/**
 * FlowLayout - Posição de fluxo da web (Elementor/WordPress)
 */
BuilderFlex.FlowLayout = class FlowLayout extends BuilderFlex.ILayoutStrategy {
    place(container, child, dropPoint) {
        // Se veio do absolute, apagamos top/left? Na verdade, deixamos lá, mas alteramos posição para fluxo
        child.setProp('position', 'relative');
        // Não apagamos left/top nem width/height como especificado no Contrato (Regras 3): 
        // "Proibido gravar width='100%' ou apagar left/top."
        // Apenas a ordem em children[] dita a posição. A ordenação será baseada em Y.
        
        // Ordenar os filhos do flow baseados no dropPoint.y
        // Vamos colocar no final por enquanto, mas podemos ordenar depois se for o caso
    }

    containerStyles(container, bp) {
        // Container mantém seu próprio CSS flexível (display: flex, flexDirection, etc)
        return '';
    }

    childStyles(container, child, bp) {
        const get = key => child.getProp(key, bp);
        const unit = value => {
            if (value === null || value === undefined || value === '') return '';
            if (!isNaN(value)) return value + 'px';
            return value;
        };
        
        let w = get('width');
        // Se a largura for inválida ou vazia, ou 100% (auto no absolute?), manter width para preservar
        // Mas se for flow, queremos que a width siga o fluxo se não especificado
        // A regra é: width de props.width. Só usa 100% se width for 'auto'.
        if (!w || w === 'auto') w = '100%';

        return `
            position: relative;
            width: ${unit(w)};
            height: ${get('height') === 'auto' ? 'auto' : unit(get('height'))};
            min-height: ${unit(get('minHeight'))};
            flex: ${get('flex')};
        `;
    }
};
