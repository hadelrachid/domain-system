/**
 * Theme RachidD Store — JavaScript Oficial Consolidado
 * Otimizado com carregamento não-bloqueante (defer) pelo DomainSEO AssetPipeline
 */
(function() {
    'use strict';

    /* ==========================================================================
       1. PROTEÇÃO DE IMAGENS E CONTEÚDO
       ========================================================================== */
    document.addEventListener('contextmenu', function(e) {
        if (e.target && (e.target.tagName === 'IMG' || (e.target.closest && e.target.closest('img')))) {
            e.preventDefault();
            return false;
        }
    }, false);

    document.addEventListener('dragstart', function(e) {
        if (e.target && (e.target.tagName === 'IMG' || (e.target.closest && e.target.closest('img')))) {
            e.preventDefault();
            return false;
        }
    }, false);

    /* ==========================================================================
       2. DRAWER LATERAL DE DEPARTAMENTOS & HAMBÚRGUER MOBILE
       ========================================================================== */
    var btnToggle = document.getElementById('btnToggleCatDrawer');
    var btnMobileToggle = document.getElementById('btnMobileHeaderToggle');
    var btnClose = document.getElementById('btnCloseCatDrawer');
    var overlay = document.getElementById('catDrawerOverlay');
    var panel = document.getElementById('catDrawerPanel');

    function openDrawer() {
        if (overlay && panel) {
            overlay.classList.add('active');
            panel.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (btnToggle) btnToggle.setAttribute('aria-expanded', 'true');
            if (btnMobileToggle) btnMobileToggle.setAttribute('aria-expanded', 'true');
        }
    }

    function closeDrawer() {
        if (overlay && panel) {
            overlay.classList.remove('active');
            panel.classList.remove('active');
            document.body.style.overflow = '';
            if (btnToggle) btnToggle.setAttribute('aria-expanded', 'false');
            if (btnMobileToggle) btnMobileToggle.setAttribute('aria-expanded', 'false');
        }
    }

    window.closeCatDrawer = closeDrawer;

    if (btnToggle) btnToggle.addEventListener('click', openDrawer);
    if (btnMobileToggle) btnMobileToggle.addEventListener('click', openDrawer);
    if (btnClose) btnClose.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDrawer();
    });

    /* ==========================================================================
       3. SINCRONIZAÇÃO DE FILTROS E ROLAGEM SUAVE
       ========================================================================== */
    function normalizeText(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function matchesCategoryFilter(linkCat, currentCat) {
        var a = normalizeText(linkCat);
        var b = normalizeText(currentCat);
        if (!a || !b) return false;
        if (a === b) return true;

        // 1. Eletrônicos & Telefonia
        if ((a.indexOf('eletronic') !== -1 || a.indexOf('telefonia') !== -1 || a.indexOf('tecnologia') !== -1) &&
            (b.indexOf('eletronic') !== -1 || b.indexOf('telefonia') !== -1 || b.indexOf('tecnologia') !== -1 || b.indexOf('gadget') !== -1)) return true;

        // 2. Relógios & Smartwatches
        if ((a.indexOf('relogio') !== -1 || a.indexOf('smartwatch') !== -1) &&
            (b.indexOf('relogio') !== -1 || b.indexOf('smartwatch') !== -1 || b.indexOf('pulseira') !== -1)) return true;

        // 3. Casa, Iluminação & Eletro
        if ((a.indexOf('casa') !== -1 || a.indexOf('iluminacao') !== -1 || a.indexOf('eletro') !== -1) &&
            (b.indexOf('casa') !== -1 || b.indexOf('iluminacao') !== -1 || b.indexOf('eletro') !== -1)) return true;

        // 4. Ferramentas & Utilidades
        if ((a.indexOf('ferramenta') !== -1 || a.indexOf('utilidade') !== -1 || a.indexOf('acessorio') !== -1) &&
            (b.indexOf('ferramenta') !== -1 || b.indexOf('utilidade') !== -1 || b.indexOf('acessorio') !== -1)) return true;

        // 5. Beleza, Saúde & Maquiagem
        if ((a.indexOf('beleza') !== -1 || a.indexOf('saude') !== -1 || a.indexOf('maquiagem') !== -1) &&
            (b.indexOf('beleza') !== -1 || b.indexOf('saude') !== -1 || b.indexOf('maquiagem') !== -1 || b.indexOf('cuidado') !== -1)) return true;

        // 6. Pet Shop
        if (a.indexOf('pet') !== -1 && b.indexOf('pet') !== -1) return true;

        // 7. Peças & Acessórios Automotivos
        if ((a.indexOf('automotiv') !== -1 || a.indexOf('veiculo') !== -1) &&
            (b.indexOf('automotiv') !== -1 || b.indexOf('veiculo') !== -1 || b.indexOf('carro') !== -1)) return true;

        // 8. Roupas, Bolsas & Calçados
        if ((a.indexOf('roupa') !== -1 || a.indexOf('bolsa') !== -1 || a.indexOf('calcado') !== -1 || a.indexOf('sapato') !== -1 || a.indexOf('vestuario') !== -1) &&
            (b.indexOf('roupa') !== -1 || b.indexOf('bolsa') !== -1 || b.indexOf('calcado') !== -1 || b.indexOf('sapato') !== -1 || b.indexOf('vestuario') !== -1) &&
            b.indexOf('intima') === -1 && a.indexOf('intima') === -1) return true;

        // 9. Roupa Íntima & Pijamas
        if ((a.indexOf('intima') !== -1 || a.indexOf('lingerie') !== -1 || a.indexOf('pijama') !== -1) &&
            (b.indexOf('intima') !== -1 || b.indexOf('lingerie') !== -1 || b.indexOf('pijama') !== -1)) return true;

        // 10. Novidades & Uso Especial
        if ((a.indexOf('novidade') !== -1 || a.indexOf('especial') !== -1 || a.indexOf('sex shop') !== -1 || a.indexOf('+18') !== -1) &&
            (b.indexOf('novidade') !== -1 || b.indexOf('especial') !== -1 || b.indexOf('sex shop') !== -1 || b.indexOf('+18') !== -1)) return true;

        return b.indexOf(a) !== -1 || a.indexOf(b) !== -1;
    }

    window.syncHeaderFilterActiveState = function(selectedCat, selectedSort, searchVal) {
        var cat = String(selectedCat || '').trim();
        var sort = String(selectedSort || '').trim();

        var searchInput = document.querySelector('.header-search-input');
        if (searchInput && typeof searchVal === 'string') {
            searchInput.value = searchVal;
        }

        document.querySelectorAll('.cat-drawer-item, .header-quick-chips a').forEach(function(el) {
            var isMatch = false;
            try {
                var parsed = new URL(el.href, window.location.origin);
                var linkCat = (parsed.searchParams.get('cat') || '').trim();
                var linkSort = (parsed.searchParams.get('sort') || '').trim();

                if (cat !== '') {
                    isMatch = matchesCategoryFilter(linkCat, cat);
                } else if (sort === 'sales') {
                    isMatch = (linkSort === 'sales' && linkCat === '');
                } else if (linkCat === '' && linkSort === '') {
                    isMatch = true;
                }
            } catch (err) {
                isMatch = false;
            }
            el.classList.toggle('active', isMatch);
        });
    };

    window.smoothScrollToProducts = function() {
        var target = document.getElementById('produtos') || document.getElementById('storeDynamicStage') || document.querySelector('.store-combobox-toolbar');
        if (!target) return;
        var header = document.querySelector('.site-header');
        var headerHeight = header ? header.offsetHeight : 80;
        var targetTop = target.getBoundingClientRect().top + window.pageYOffset - (headerHeight + 15);
        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: 'smooth'
        });
    };

    // Busca dinâmica instantânea no Palco quando já estiver na /loja
    var searchForm = document.querySelector('.header-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            var stage = document.getElementById('storeDynamicStage');
            if (stage && window.filterCatalogDynamically) {
                e.preventDefault();
                var input = searchForm.querySelector('input[name="q"]');
                var qVal = input ? input.value.trim() : '';
                var targetUrl = searchForm.action + (qVal !== '' ? '?q=' + encodeURIComponent(qVal) : '') + '#produtos';
                window.filterCatalogDynamically(targetUrl, true, true);
            }
        });
    }

    // Filtragem dinâmica instantânea ao clicar em itens da gaveta
    document.addEventListener('click', function(e) {
        var link = e.target.closest('.cat-drawer-item, .header-quick-chips a');
        if (link && link.href) {
            var stage = document.getElementById('storeDynamicStage');
            if (stage && window.filterCatalogDynamically) {
                e.preventDefault();
                closeDrawer();
                window.filterCatalogDynamically(link.href, true, true);
            }
        }
    });

    /* ==========================================================================
       4. MÁSCARAS DE ENTRADA (CPF & TELEFONE)
       ========================================================================== */
    window.homeMaskCpf = function(el) {
        if (!el) return;
        var v = el.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 9) el.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        else if (v.length > 6) el.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        else if (v.length > 3) el.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        else el.value = v;
    };

    window.homeMaskPhone = function(el) {
        if (!el) return;
        var v = el.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) el.value = v.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
        else if (v.length > 6) el.value = v.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
        else if (v.length > 2) el.value = v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
        else if (v.length > 0) el.value = '(' + v;
        else el.value = '';
    };

})();
