<?php
/**
 * View Pública da Vitrine da Loja (/loja)
 *
 * @var array<int, \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto> $products
 * @var array<int, string> $categories
 * @var string $selectedCategory
 * @var string $search
 * @var int $totalCount
 * @var string $baseUrl
 */
?>
<style>
    @media (max-width: 900px) {
        .store-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
    }
    @media (max-width: 640px) {
        .store-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<div class="domain-drop-store">

    <!-- Store Hero Banner (Sem barra de busca redundante - o topo oficial já possui a busca ampla) -->
    <div class="store-hero-banner">
        <!-- Lâmpada de Aladim Dourada Flutuante -->
        <div style="display: inline-block; margin-bottom: 10px; filter: drop-shadow(0 0 20px rgba(212,175,55,0.6));">
            <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="RachidD Lâmpada Mágica" style="height: 56px; width: auto; display: block; margin: 0 auto;">
        </div>
        <br>
        <span style="background: rgba(212,175,55,0.15); color: #F0D060; border: 1px solid rgba(212,175,55,0.3); padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; display: inline-block; margin-bottom: 10px;">
            🔎 Curadoria Oficial • Produtos Físicos Minerados no Brasil
        </span>
        <h1 style="font-size: clamp(24px, 4vw, 38px); font-weight: 800; color: #fff; margin: 0 0 10px 0; letter-spacing: -0.5px;">
            Loja Oficial <span style="background: linear-gradient(135deg, #FFE082 0%, #D4AF37 50%, #B8962E 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">RachidD.com</span>
        </h1>
        <p style="font-size: 14.5px; color: #cbd5e1; max-width: 740px; margin: 0 auto 14px auto; line-height: 1.55;">
            Serviço de curadoria que localiza via SDK oficial do AliExpress os produtos físicos já no Brasil com as melhores <strong>notas, aprovações e termômetro de vendas</strong>. O valor inclui nossa busca especializada e <strong>garantia total de entrega</strong>.
        </p>
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; font-size: 11.5px; font-weight: 700;">
            <span style="background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); color: #4ade80; padding: 4px 11px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;"><?= \DomainSystem\Plugins\domain_drop\Helpers\FlagHelper::br(15, 10) ?> Estoque AliExpress Brasil</span>
            <span style="background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.35); color: #fbbf24; padding: 4px 11px; border-radius: 20px;">⭐ Minerados por Nota &amp; Termômetro</span>
            <span style="background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.35); color: #93c5fd; padding: 4px 11px; border-radius: 20px;">🛡️ Você paga pela Curadoria + Garantia</span>
        </div>
    </div>

    <!-- 24h Countdown Rotation Urgency Banner -->
    <?php
    $remSeconds = isset($cycleInfo['seconds_remaining']) ? (int)$cycleInfo['seconds_remaining'] : 86400;
    $initialH = str_pad((string)floor($remSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $initialM = str_pad((string)floor(($remSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $initialS = str_pad((string)($remSeconds % 60), 2, '0', STR_PAD_LEFT);
    ?>
    <div style="background: linear-gradient(135deg, rgba(20,24,30,0.95) 0%, rgba(35,28,15,0.95) 100%); border: 1px solid rgba(212,175,55,0.4); border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.45); position: relative;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 0 15px rgba(212,175,55,0.4); flex-shrink: 0;">
                🔥
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 800; color: #FFE082; text-transform: uppercase; letter-spacing: 0.5px;">
                        Ciclo Diário de Ofertas Virais • 24 Horas
                    </span>
                    <span style="background: rgba(34,197,94,0.18); color: #4ade80; border: 1px solid rgba(34,197,94,0.35); padding: 2px 7px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                        Ao Vivo
                    </span>
                </div>
                <div style="font-size: 12px; color: #cbd5e1; margin-top: 2px;">
                    Nosso robô autônomo minera e renova tendências mundiais a cada 24 horas. Aproveite antes da próxima rotação!
                </div>
            </div>
        </div>

        <!-- Digital Timer Clocks -->
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="text-align: right; margin-right: 4px;">
                <div style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px;">Próxima Seleção</div>
                <div style="font-size: 11px; color: #F0D060; font-weight: 600;">Renovação Total</div>
            </div>
            <div id="drop24hCountdown" style="display: flex; align-items: center; gap: 4px;">
                <div style="background: rgba(0,0,0,0.7); border: 1px solid rgba(212,175,55,0.4); border-radius: 8px; padding: 5px 8px; min-width: 40px; text-align: center;">
                    <div id="dropTimerH" style="font-size: 18px; font-weight: 800; color: #fff; font-family: monospace;"><?= $initialH ?></div>
                    <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">Horas</div>
                </div>
                <span style="color: #F0D060; font-weight: 800; font-size: 16px;">:</span>
                <div style="background: rgba(0,0,0,0.7); border: 1px solid rgba(212,175,55,0.4); border-radius: 8px; padding: 5px 8px; min-width: 40px; text-align: center;">
                    <div id="dropTimerM" style="font-size: 18px; font-weight: 800; color: #fff; font-family: monospace;"><?= $initialM ?></div>
                    <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">Min</div>
                </div>
                <span style="color: #F0D060; font-weight: 800; font-size: 16px;">:</span>
                <div style="background: rgba(0,0,0,0.7); border: 1px solid rgba(212,175,55,0.4); border-radius: 8px; padding: 5px 8px; min-width: 40px; text-align: center;">
                    <div id="dropTimerS" style="font-size: 18px; font-weight: 800; color: #FFE082; font-family: monospace;"><?= $initialS ?></div>
                    <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">Seg</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    if (window.initRachidDCycleCountdown) {
        window.initRachidDCycleCountdown({
            remainingSeconds: <?= (int)($remSeconds ?? 0) ?>,
            hElementId: 'dropTimerH',
            mElementId: 'dropTimerM',
            sElementId: 'dropTimerS',
            cronUrl: '<?= $baseUrl ?>/dropship/cron-tick'
        });
    }
    </script>

    <!-- Toolbar Responsivo de Seleção via COMBOBOX (Substitui barra de rolagem horizontal!) -->
    <div id="produtos" class="store-combobox-toolbar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(212,175,55,0.15); border: 1px solid rgba(212,175,55,0.4); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                📂
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #F0D060; text-transform: uppercase; letter-spacing: 0.8px;">Filtrar Catálogo</div>
                <div style="font-size: 13px; font-weight: 700; color: #fff;">Escolha a Categoria</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1; justify-content: flex-end;">
            <!-- Combobox Principal de Categorias (Atualiza o Palco Dinâmico sem recarregar a página!) -->
            <select id="storeCategoryCombobox" class="store-combobox-select" onchange="if(this.value) filterCatalogDynamically(this.value);">
                <option value="<?= $baseUrl ?>/loja" <?= empty($selectedCategory) ? 'selected' : '' ?>>
                    🌐 Todas as Categorias (Ver Catálogo Completo)
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Eletrônicos & Telefonia') ?>" <?= (stripos($selectedCategory, 'eletrônic') !== false || stripos($selectedCategory, 'telefonia') !== false || stripos($selectedCategory, 'tecnologia') !== false) ? 'selected' : '' ?>>
                    📱 Eletrônicos & Telefonia
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Relógios & Smartwatches') ?>" <?= (stripos($selectedCategory, 'relógio') !== false || stripos($selectedCategory, 'relogio') !== false || stripos($selectedCategory, 'smartwatch') !== false || stripos($selectedCategory, 'áudio') !== false || stripos($selectedCategory, 'audio') !== false) ? 'selected' : '' ?>>
                    ⌚ Relógios & Smartwatches
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Casa, Iluminação & Eletro') ?>" <?= (stripos($selectedCategory, 'casa') !== false || stripos($selectedCategory, 'iluminação') !== false || stripos($selectedCategory, 'iluminacao') !== false || stripos($selectedCategory, 'eletro') !== false) ? 'selected' : '' ?>>
                    🏠 Casa, Iluminação & Eletro
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Ferramentas & Utilidades') ?>" <?= (stripos($selectedCategory, 'ferramenta') !== false || stripos($selectedCategory, 'utilidade') !== false || stripos($selectedCategory, 'acessorio') !== false || stripos($selectedCategory, 'acessórios') !== false) ? 'selected' : '' ?>>
                    🛠️ Ferramentas & Utilidades
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Beleza, Saúde & Maquiagem') ?>" <?= (stripos($selectedCategory, 'beleza') !== false || stripos($selectedCategory, 'saúde') !== false || stripos($selectedCategory, 'saude') !== false || stripos($selectedCategory, 'maquiagem') !== false) ? 'selected' : '' ?>>
                    ✨ Beleza, Saúde & Maquiagem
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=Pet+Shop" <?= (stripos($selectedCategory, 'pet') !== false) ? 'selected' : '' ?>>
                    🐾 Pet Shop
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Peças & Acessórios Automotivos') ?>" <?= (stripos($selectedCategory, 'automotiv') !== false || stripos($selectedCategory, 'veículo') !== false || stripos($selectedCategory, 'veiculo') !== false) ? 'selected' : '' ?>>
                    🚗 Peças & Acessórios Automotivos
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Roupas, Bolsas & Calçados') ?>" <?= ((stripos($selectedCategory, 'roupa') !== false || stripos($selectedCategory, 'bolsa') !== false || stripos($selectedCategory, 'calçado') !== false || stripos($selectedCategory, 'calcado') !== false || stripos($selectedCategory, 'vestuário') !== false || stripos($selectedCategory, 'vestuario') !== false) && stripos($selectedCategory, 'íntima') === false && stripos($selectedCategory, 'intima') === false) ? 'selected' : '' ?>>
                    👗 Roupas, Bolsas & Calçados
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Roupa Íntima & Pijamas') ?>" <?= (stripos($selectedCategory, 'lingerie') !== false || stripos($selectedCategory, 'íntima') !== false || stripos($selectedCategory, 'intima') !== false || stripos($selectedCategory, 'pijama') !== false) ? 'selected' : '' ?>>
                    👙 Roupa Íntima & Pijamas
                </option>
                <option value="<?= $baseUrl ?>/loja?cat=<?= urlencode('Novidades & Uso Especial') ?>" <?= (stripos($selectedCategory, 'novidade') !== false || stripos($selectedCategory, 'especial') !== false || stripos($selectedCategory, 'sex shop') !== false || stripos($selectedCategory, '+18') !== false) ? 'selected' : '' ?>>
                    🔞 Novidades & Uso Especial
                </option>
            </select>

            <!-- Combobox de Ordenação Dinâmico -->
            <?php $currentSort = (string)($_GET['sort'] ?? ''); ?>
            <select id="storeSortCombobox" class="store-combobox-select" style="min-width: 170px;" onchange="applyStoreSort(this.value);">
                <option value="" <?= empty($currentSort) ? 'selected' : '' ?>>
                    ⚡ Mais Recentes
                </option>
                <option value="sales" <?= $currentSort === 'sales' ? 'selected' : '' ?>>
                    🏆 Mais Vendidos
                </option>
                <option value="price_asc" <?= $currentSort === 'price_asc' ? 'selected' : '' ?>>
                    💰 Menor Preço
                </option>
                <option value="price_desc" <?= $currentSort === 'price_desc' ? 'selected' : '' ?>>
                    💎 Maior Preço
                </option>
                <option value="rating" <?= $currentSort === 'rating' ? 'selected' : '' ?>>
                    ⭐ Mais Bem Avaliados
                </option>
            </select>

            <a id="btnClearStoreFilters" href="<?= $baseUrl ?>/loja" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.35); color: #f87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; display: <?= (!empty($selectedCategory) || !empty($search) || !empty($currentSort)) ? 'inline-block' : 'none' ?>;" title="Limpar todos os filtros">
                ✕ Limpar
            </a>
        </div>
    </div>

    <!-- PALCO DINÂMICO DE PRODUTOS (Sem recarregar página nem pular para cima!) -->
    <div id="storeDynamicStage" class="store-dynamic-stage" style="position: relative; min-height: 350px;">
        <?php include __DIR__ . '/stage.php'; ?>
    </div>

</div>

<script>
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

window.applyStoreSort = function(sortVal) {
    try {
        var current = new URL(window.location.href);
        if (sortVal && sortVal.trim() !== '') {
            current.searchParams.set('sort', sortVal.trim());
        } else {
            current.searchParams.delete('sort');
        }
        current.hash = '#produtos';
        window.filterCatalogDynamically(current.toString(), true, false);
    } catch (e) {
        window.filterCatalogDynamically('<?= $baseUrl ?>/loja' + (sortVal ? '?sort=' + encodeURIComponent(sortVal) : ''), true, false);
    }
};

function normalizeFilterStr(str) {
    return String(str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

window.filterCatalogDynamically = function(url, updateUrl, shouldScroll) {
    if (typeof updateUrl === 'undefined') updateUrl = true;
    if (typeof shouldScroll === 'undefined') shouldScroll = false;
    var stage = document.getElementById('storeDynamicStage');
    if (!stage) {
        window.location.href = url;
        return;
    }

    var targetUrlObj;
    var fetchUrlObj;
    try {
        targetUrlObj = new URL(url, window.location.origin);
        fetchUrlObj = new URL(targetUrlObj.href);
        // Remove qualquer fragmento (#produtos) antes de anexar ajax=1
        fetchUrlObj.hash = '';
        fetchUrlObj.searchParams.set('ajax', '1');
    } catch (e) {
        var cleanUrl = String(url).split('#')[0];
        fetchUrlObj = cleanUrl + (cleanUrl.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
    }

    // Feedback visual suave no próprio palco sem pular a página
    stage.style.opacity = '0.35';
    stage.style.pointerEvents = 'none';

    fetch(fetchUrlObj.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) {
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        return res.json();
    })
    .then(function(data) {
        if (data && typeof data.html === 'string') {
            stage.innerHTML = data.html;
            if (updateUrl && window.history.pushState && targetUrlObj) {
                window.history.pushState(null, '', targetUrlObj.pathname + targetUrlObj.search + targetUrlObj.hash);
            }

            // Sincroniza o combobox de Categorias comparando searchParams decodificados (+ e %20 tratados igual)
            var catSelect = document.getElementById('storeCategoryCombobox');
            var selectedCatNorm = normalizeFilterStr(data.selectedCategory || '');
            if (catSelect) {
                if (!selectedCatNorm) {
                    catSelect.selectedIndex = 0;
                } else {
                    var matchedIndex = 0;
                    for (var i = 0; i < catSelect.options.length; i++) {
                        try {
                            var optUrl = new URL(catSelect.options[i].value, window.location.origin);
                            var optCatNorm = normalizeFilterStr(optUrl.searchParams.get('cat') || '');
                            if (optCatNorm && (optCatNorm === selectedCatNorm || selectedCatNorm.indexOf(optCatNorm) !== -1 || optCatNorm.indexOf(selectedCatNorm) !== -1)) {
                                matchedIndex = i;
                                break;
                            }
                        } catch (err) {}
                    }
                    catSelect.selectedIndex = matchedIndex;
                }
            }

            // Sincroniza o combobox de Ordenação
            var sortSelect = document.getElementById('storeSortCombobox');
            if (sortSelect) {
                sortSelect.value = data.sort || '';
            }

            // Sincroniza o botão Limpar Filtros da barra
            var clearBtn = document.getElementById('btnClearStoreFilters');
            if (clearBtn) {
                var hasActiveFilter = Boolean(data.selectedCategory || data.search || data.sort);
                clearBtn.style.display = hasActiveFilter ? 'inline-block' : 'none';
            }

            // Sincroniza o item ativo dentro do Menu Hambúrguer (Drawer) e Quick Chips do Header
            if (window.syncHeaderFilterActiveState) {
                window.syncHeaderFilterActiveState(data.selectedCategory || '', data.sort || '', data.search || '');
            }

            if (shouldScroll && window.smoothScrollToProducts) {
                setTimeout(window.smoothScrollToProducts, 100);
            }
        }
    })
    .catch(function(err) {
        console.error('Falha no carregamento dinâmico:', err);
        window.location.href = url;
    })
    .finally(function() {
        stage.style.opacity = '1';
        stage.style.pointerEvents = '';
    });
};

window.addEventListener('popstate', function() {
    filterCatalogDynamically(window.location.href, false);
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#produtos' || (window.location.search && window.location.search.indexOf('cat=') !== -1)) {
        setTimeout(function() {
            if (window.smoothScrollToProducts) {
                window.smoothScrollToProducts();
            }
        }, 300);
    }
});
</script>

</div>
