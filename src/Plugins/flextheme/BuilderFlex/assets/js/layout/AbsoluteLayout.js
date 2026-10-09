/**
 * AbsoluteLayout - Posição livre estilo Delphi/Photoshop
 */
BuilderFlex.AbsoluteLayout = class AbsoluteLayout extends BuilderFlex.ILayoutStrategy {
    place(container, child, dropPoint) {
        const grid = dropPoint.gridSize || 10;
        const borderLeft = parseFloat(dropPoint.style.borderLeftWidth) || 0;
        const borderTop = parseFloat(dropPoint.style.borderTopWidth) || 0;
        const paddingLeft = parseFloat(dropPoint.style.paddingLeft) || 0;
        const paddingTop = parseFloat(dropPoint.style.paddingTop) || 0;
        
        let rawLeft = dropPoint.nodeRect.left - dropPoint.targetRect.left - borderLeft - paddingLeft + dropPoint.scrollLeft;
        let rawTop = dropPoint.nodeRect.top - dropPoint.targetRect.top - borderTop - paddingTop + dropPoint.scrollTop;
        
        let pW = dropPoint.targetRect.width || 1;
        let pH = dropPoint.targetRect.height || 1;
        let pLeft = (Math.round(rawLeft / grid) * grid / pW) * 100;
        let pTop = (Math.round(rawTop / grid) * grid / pH) * 100;
        
        child.setProp('left', pLeft.toFixed(2) + '%');
        child.setProp('top', pTop.toFixed(2) + '%');
        
        // O tamanho é mantido
        child.setProp('position', 'absolute');
    }

    containerStyles(container, bp) {
        return `
            position: relative;
            display: block; /* sem flex */
            gap: 0;
        `;
    }

    childStyles(container, child, bp) {
        const get = key => child.getProp(key, bp);
        const unit = value => {
            if (value === null || value === undefined || value === '') return '';
            if (!isNaN(value)) return value + 'px';
            return value;
        };
        const leftVal = get('left');
        const topVal = get('top');
        
        return `
            position: absolute;
            left: ${unit(leftVal)};
            top: ${unit(topVal)};
            width: ${unit(get('width'))};
            height: ${unit(get('height'))};
            min-height: ${unit(get('minHeight'))};
            z-index: ${get('zIndex') || 1};
        `;
    }
};
