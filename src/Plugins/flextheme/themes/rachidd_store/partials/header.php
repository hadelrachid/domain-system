<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentCat = trim((string)($_GET['cat'] ?? ''));
$currentQ = trim((string)($_GET['q'] ?? ''));
$currentSort = trim((string)($_GET['sort'] ?? ''));
?>
<!-- Top Utility Bar (Marketplace Internacional) -->
<div style="background: #06090e; border-bottom: 1px solid rgba(212, 175, 55, 0.15); font-size: 11px; color: #94a3b8; padding: 6px 0;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="color: #F0D060; font-weight: 600;">✨ O Gênio das Ofertas Internacionais</span>
            <span style="opacity: 0.4;">|</span>
            <span class="hide-mobile">🚚 Envio com código rastreado para todo o Brasil</span>
        </div>
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);" title="Moeda e Região">
                <span>🌐</span>
                <span style="color: #fff; font-weight: 600;">BRL (R$)</span>
            </div>
            <a href="<?= $baseUrl ?>/loja" style="color: #cbd5e1; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#cbd5e1'">Rastrear</a>
            <a href="<?= $baseUrl ?>/admin/dropship" style="color: #D4AF37; text-decoration: none; font-weight: 600;">Seller</a>
        </div>
    </div>
</div>

<style>
    .site-header {
        padding: 12px 0 10px 0;
        border-bottom: 1px solid rgba(212, 175, 55, 0.25);
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(11, 15, 23, 0.96);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 6px 24px rgba(0,0,0,0.55);
    }
    .header-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
    }
    .header-logo-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        flex-shrink: 0;
    }
    .header-logo-img {
        height: 44px;
        width: auto;
        filter: drop-shadow(0 0 12px rgba(212, 175, 55, 0.5));
        transition: transform 0.25s ease;
    }
    .header-logo-wrap:hover .header-logo-img {
        transform: scale(1.06);
    }
    .header-logo-title {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: #fff;
        line-height: 1.1;
    }
    .header-logo-sub {
        font-size: 9.5px;
        color: #D4AF37;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }

    /* Botões Topo 100% Responsivos */
    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .btn-desejos {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #F0D060;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        background: rgba(212, 175, 55, 0.12);
        border: 1px solid rgba(212, 175, 55, 0.35);
        padding: 8px 14px;
        border-radius: 20px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-desejos:hover {
        background: rgba(212, 175, 55, 0.22);
        border-color: #F0D060;
    }
    .btn-loja {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
        color: #0b0f17 !important;
        padding: 8px 16px;
        border-radius: 20px;
        box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-loja:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(212, 175, 55, 0.5);
    }

    /* Barra de Busca Ampla e Acessível */
    .header-search-row {
        margin-top: 10px;
        width: 100%;
    }
    .header-search-form {
        display: flex;
        width: 100%;
        position: relative;
        box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        border-radius: 10px;
    }
    .header-search-input {
        flex: 1;
        background: rgba(0,0,0,0.65);
        border: 2px solid rgba(212, 175, 55, 0.4);
        border-right: none;
        color: #fff;
        padding: 12px 18px;
        border-radius: 10px 0 0 10px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s ease;
    }
    .header-search-input:focus {
        border-color: #F0D060;
        background: rgba(0,0,0,0.85);
        box-shadow: 0 0 12px rgba(212,175,55,0.3);
    }
    .header-search-btn {
        background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
        border: 2px solid #D4AF37;
        color: #0b0f17;
        padding: 0 24px;
        border-radius: 0 10px 10px 0;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .header-search-btn:hover {
        background: linear-gradient(135deg, #FFE082 0%, #E6C34E 100%);
    }

    /* Barra de Filtro com Botão Toggle / Hambúrguer */
    .header-filter-strip {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid rgba(255,255,255,0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-toggle-hamburguer {
        background: linear-gradient(135deg, rgba(212,175,55,0.18) 0%, rgba(20,25,35,0.9) 100%);
        border: 1.5px solid rgba(212,175,55,0.5);
        color: #F0D060;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
        outline: none;
    }
    .btn-toggle-hamburguer:hover {
        background: rgba(212,175,55,0.28);
        border-color: #F0D060;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(212,175,55,0.25);
    }
    .btn-toggle-hamburguer .icon-bar {
        font-size: 16px;
        line-height: 1;
    }

    .header-quick-chips {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .header-quick-chips a {
        color: #cbd5e1;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        padding: 5px 12px;
        border-radius: 15px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .header-quick-chips a:hover,
    .header-quick-chips a.active {
        color: #0b0f17;
        background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
        border-color: #F0D060;
        font-weight: 700;
    }

    /* LAPELA / POPUP DRAWER MODAL DE CATEGORIAS */
    .cat-drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(3, 5, 8, 0.78);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 99999;
        display: none;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .cat-drawer-overlay.active {
        display: block;
        opacity: 1;
    }
    .cat-drawer-panel {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        max-width: 440px;
        height: 100%;
        background: #0d121a;
        border-right: 1px solid rgba(212,175,55,0.35);
        box-shadow: 10px 0 40px rgba(0,0,0,0.85);
        z-index: 100000;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    .cat-drawer-panel.active {
        transform: translateX(0);
    }
    .cat-drawer-header {
        padding: 20px 22px;
        border-bottom: 1px solid rgba(212,175,55,0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(180deg, rgba(212,175,55,0.1) 0%, transparent 100%);
    }
    .cat-drawer-close {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        color: #fff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .cat-drawer-close:hover {
        background: #ef4444;
        border-color: #ef4444;
    }
    .cat-drawer-list {
        padding: 16px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .cat-drawer-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 10px;
        color: #e2e8f0;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .cat-drawer-item:hover,
    .cat-drawer-item.active {
        background: linear-gradient(135deg, rgba(212,175,55,0.2) 0%, rgba(212,175,55,0.05) 100%);
        border-color: rgba(212,175,55,0.5);
        color: #F0D060;
        transform: translateX(4px);
    }
    .cat-drawer-item .item-icon {
        font-size: 18px;
        margin-right: 12px;
    }

    /* REGRAS RESPONSIVAS MOBILE E TABLET */
    @media (max-width: 768px) {
        .header-quick-chips {
            display: none; /* No mobile, o botão hambúrguer é o mestre da filtragem */
        }
        .header-filter-strip {
            justify-content: center;
        }
        .btn-toggle-hamburguer {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
            font-size: 14px;
        }
    }
    @media (max-width: 580px) {
        .hide-mobile {
            display: none;
        }
        .header-logo-title {
            font-size: 1.25rem;
        }
        .header-logo-img {
            height: 38px;
        }
        .btn-desejos {
            padding: 6px 10px;
            font-size: 11px;
        }
        .btn-loja {
            padding: 6px 12px;
            font-size: 11px;
        }
        .header-search-btn {
            padding: 0 16px;
            font-size: 13px;
        }
        .header-search-input {
            padding: 10px 14px;
            font-size: 13.5px;
        }
        .cat-drawer-panel {
            max-width: 85%;
        }
    }
</style>

<!-- Main Header -->
<header class="site-header">
    <div class="container">
        
        <!-- Row 1: Logo & Brand + Right Action Buttons (Totalmente responsivos) -->
        <div class="header-top-row">
            <a href="<?= $baseUrl ?>/" class="header-logo-wrap">
                <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="RachidD Lâmpada de Aladim" class="header-logo-img">
                <div>
                    <div class="header-logo-title">
                        Rachid<span style="background: linear-gradient(135deg, #FFE082 0%, #D4AF37 50%, #B8962E 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">D.com</span>
                    </div>
                    <div class="header-logo-sub">
                        Marketplace & Dropship
                    </div>
                </div>
            </a>

            <!-- Right Quick Actions: Responsivos e compactos para nunca quebrar a linha -->
            <div class="header-actions">
                <a href="<?= $baseUrl ?>/#desejos" class="btn-desejos">
                    <span>✨</span>
                    <span class="btn-label-text">Desejos do Dia</span>
                </a>
                <a href="<?= $baseUrl ?>/loja" class="btn-loja">
                    <span>🛍️</span>
                    <span class="btn-label-text">Ver Loja</span>
                </a>
            </div>
        </div>

        <!-- Row 2: Barra de Busca Oficial Ampla e Responsiva (Sem encurtamento!) -->
        <div class="header-search-row">
            <form action="<?= $baseUrl ?>/loja" method="GET" class="header-search-form">
                <?php if (!empty($currentCat)): ?>
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($currentCat) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= htmlspecialchars($currentQ) ?>" placeholder="O que o Gênio pode encontrar para você hoje? Digite o produto..." class="header-search-input">
                <button type="submit" class="header-search-btn">
                    <span>🔍</span>
                    <span>Buscar</span>
                </button>
            </form>
        </div>

        <!-- Row 3: Barra de Filtragem com Botão Hambúrguer / Lapela Popup -->
        <div class="header-filter-strip">
            <!-- Botão Toggle / Hambúrguer que abre a Lapela de Categorias -->
            <button type="button" id="btnToggleCatDrawer" class="btn-toggle-hamburguer" aria-label="Abrir Categorias e Filtros">
                <span class="icon-bar">☰</span>
                <span>Departamentos & Categorias</span>
                <span style="font-size: 11px; opacity: 0.8;">▾</span>
            </button>

            <!-- Quick Chips Rápidos para Desktop / Tablet -->
            <div class="header-quick-chips">
                <a href="<?= $baseUrl ?>/loja" class="<?= (empty($currentCat) && empty($currentSort)) ? 'active' : '' ?>">🔥 Super Ofertas</a>
                <a href="<?= $baseUrl ?>/loja?cat=Tecnologia" class="<?= (stripos($currentCat, 'tecnologia') !== false || stripos($currentCat, 'eletrônicos') !== false) ? 'active' : '' ?>">Tecnologia & Gadgets</a>
                <a href="<?= $baseUrl ?>/loja?cat=Smartwatches" class="<?= (stripos($currentCat, 'smartwatch') !== false || stripos($currentCat, 'áudio') !== false) ? 'active' : '' ?>">Smartwatches & Áudio</a>
                <a href="<?= $baseUrl ?>/loja?cat=Casa" class="<?= (stripos($currentCat, 'casa') !== false) ? 'active' : '' ?>">Casa Inteligente</a>
                <a href="<?= $baseUrl ?>/loja?cat=Acessorios" class="<?= (stripos($currentCat, 'acessórios') !== false || stripos($currentCat, 'ferramentas') !== false) ? 'active' : '' ?>">Acessórios Virais</a>
                <a href="<?= $baseUrl ?>/loja?cat=Beleza" class="<?= (stripos($currentCat, 'beleza') !== false || stripos($currentCat, 'saúde') !== false) ? 'active' : '' ?>">Saúde & Cuidados</a>
                <a href="<?= $baseUrl ?>/loja?sort=sales" class="<?= ($currentSort === 'sales') ? 'active' : '' ?>">🏆 Mais Vendidos</a>
            </div>
        </div>
    </div>
</header>

<!-- LAPELA / POPUP DRAWER LATERAL DE CATEGORIAS & DEPARTAMENTOS -->
<div id="catDrawerOverlay" class="cat-drawer-overlay"></div>
<aside id="catDrawerPanel" class="cat-drawer-panel" aria-label="Menu de Departamentos e Categorias">
    <div class="cat-drawer-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 22px;">🧞</span>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #F0D060; text-transform: uppercase; letter-spacing: 1px;">Menu de Ofertas</div>
                <div style="font-size: 15px; font-weight: 800; color: #fff;">Departamentos & Filtros</div>
            </div>
        </div>
        <button type="button" id="btnCloseCatDrawer" class="cat-drawer-close" aria-label="Fechar Menu">✕</button>
    </div>

    <div class="cat-drawer-list">
        <!-- Todas as Categorias -->
        <a href="<?= $baseUrl ?>/loja<?= !empty($currentQ) ? '?q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= empty($currentCat) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🌐</span>
                <span>Todas as Categorias (Ver Tudo)</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Mais Vendidos -->
        <a href="<?= $baseUrl ?>/loja?sort=sales<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= ($currentSort === 'sales') ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🏆</span>
                <span>Mais Vendidos (Best Sellers)</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Tecnologia & Gadgets -->
        <a href="<?= $baseUrl ?>/loja?cat=Tecnologia<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'tecnologia') !== false || stripos($currentCat, 'eletrônicos') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">📱</span>
                <span>Tecnologia & Gadgets</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Smartwatches & Áudio -->
        <a href="<?= $baseUrl ?>/loja?cat=Smartwatches<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'smartwatch') !== false || stripos($currentCat, 'áudio') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">⌚</span>
                <span>Smartwatches & Áudio</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Casa Inteligente & Organização -->
        <a href="<?= $baseUrl ?>/loja?cat=Casa<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'casa') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🏠</span>
                <span>Casa Inteligente & Organização</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Acessórios Virais & Ferramentas -->
        <a href="<?= $baseUrl ?>/loja?cat=Acessorios<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'acessórios') !== false || stripos($currentCat, 'ferramentas') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🛠️</span>
                <span>Acessórios Virais & Ferramentas</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Saúde & Cuidados (Beleza) -->
        <a href="<?= $baseUrl ?>/loja?cat=Beleza<?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'beleza') !== false || stripos($currentCat, 'saúde') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">✨</span>
                <span>Saúde & Cuidados (Beleza)</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Pet Shop & Pets Virais -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Pet Shop & Pets Virais') ?><?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'pet') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🐾</span>
                <span>Pet Shop & Pets Virais</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Acessórios para Veículos -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Acessórios para Veículos') ?><?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'veículo') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🚗</span>
                <span>Acessórios para Veículos</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Moda, Roupas & Vestuário -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Moda, Roupas & Vestuário') ?><?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'roupa') !== false || stripos($currentCat, 'vestuário') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">👗</span>
                <span>Moda, Roupas & Vestuário</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Moda Íntima, Lingeries & Pijamas -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Moda Íntima, Lingeries & Pijamas') ?><?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'lingerie') !== false || stripos($currentCat, 'íntima') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">👙</span>
                <span>Moda Íntima, Lingeries & Pijamas</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Sex Shop & Adultos (+18) -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Sex Shop & Produtos Adultos (+18)') ?><?= !empty($currentQ) ? '&q=' . urlencode($currentQ) : '' ?>" class="cat-drawer-item <?= (stripos($currentCat, 'sex shop') !== false || stripos($currentCat, '+18') !== false) ? 'active' : '' ?>" style="border-color: rgba(239,68,68,0.3); color: #fca5a5;">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🔞</span>
                <span>Sex Shop & Adultos (+18)</span>
            </div>
            <span>➔</span>
        </a>
    </div>
</aside>

<script>
(function() {
    var btnToggle = document.getElementById('btnToggleCatDrawer');
    var btnClose = document.getElementById('btnCloseCatDrawer');
    var overlay = document.getElementById('catDrawerOverlay');
    var panel = document.getElementById('catDrawerPanel');

    function openDrawer() {
        if (overlay && panel) {
            overlay.classList.add('active');
            panel.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (overlay && panel) {
            overlay.classList.remove('active');
            panel.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    window.closeCatDrawer = closeDrawer;

    if (btnToggle) btnToggle.addEventListener('click', openDrawer);
    if (btnClose) btnClose.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // Filtragem dinâmica instantânea no Palco (sem reload e sem pular para cima!)
    document.addEventListener('click', function(e) {
        var link = e.target.closest('.cat-drawer-item, .header-quick-chips a');
        if (link && link.href) {
            var stage = document.getElementById('storeDynamicStage');
            if (stage && window.filterCatalogDynamically) {
                e.preventDefault();
                closeDrawer();
                window.filterCatalogDynamically(link.href);
            }
        }
    });
})();
</script>
