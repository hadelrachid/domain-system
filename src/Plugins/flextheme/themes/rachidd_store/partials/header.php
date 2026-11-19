<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentCat = trim((string)($_GET['cat'] ?? ''));
$currentQ = trim((string)($_GET['q'] ?? ''));
$currentSort = trim((string)($_GET['sort'] ?? ''));

$customerLoggedId = 0;
$customerLoggedName = '';
try {
    if (isset($GLOBALS['app'])) {
        $c = $GLOBALS['app']->getContainer();
        if ($c->has(\DomainSystem\Core\Contracts\SessionManagerInterface::class)) {
            $sess = $c->make(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
            $customerLoggedId = (int)$sess->get('customer_id', 0);
            $customerLoggedName = (string)$sess->get('customer_name', '');
        }
    }
} catch (\Throwable $e) {
}
if ($customerLoggedId <= 0 && isset($_SESSION['customer_id'])) {
    $customerLoggedId = (int)$_SESSION['customer_id'];
    $customerLoggedName = (string)($_SESSION['customer_name'] ?? '');
}
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
            <a href="<?= $baseUrl ?>/rastrear" style="color: #cbd5e1; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#cbd5e1'">Rastrear</a>
            <a href="<?= $baseUrl ?>/admin/dropship" style="color: #D4AF37; text-decoration: none; font-weight: 600;">Seller</a>
        </div>
    </div>
</div>

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
                <?php if ($customerLoggedId > 0): ?>
                    <a href="<?= $baseUrl ?>/minha-conta" class="btn-desejos" title="Meus Pedidos e Reclamações">
                        <span>👤</span>
                        <span class="btn-label-text"><?= htmlspecialchars(explode(' ', trim($customerLoggedName))[0] ?: 'Conta', ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/cadastro" class="btn-desejos" title="Criar Conta Grátis">
                        <span>👤</span>
                        <span class="btn-label-text">Cadastrar</span>
                    </a>
                    <a href="<?= $baseUrl ?>/entrar" class="btn-desejos" title="Acessar Minha Conta">
                        <span>🔐</span>
                        <span class="btn-label-text">Acessar</span>
                    </a>
                <?php endif; ?>
                <a href="<?= $baseUrl ?>/loja" class="btn-loja">
                    <span>🛍️</span>
                    <span class="btn-label-text">Ver Loja</span>
                </a>
            </div>

            <!-- Toggle Hambúrguer Mobile / Tablet (substitui os botões laterais para não vazar da tela) -->
            <button type="button" id="btnMobileHeaderToggle" class="btn-mobile-actions-toggle" aria-label="Abrir menu e conta" aria-expanded="false">
                <span style="font-size: 16px; line-height: 1;">☰</span>
                <span>Menu</span>
            </button>
        </div>

        <!-- Row 2: Barra de Busca Oficial Ampla e Responsiva (Sem encurtamento!) -->
        <div class="header-search-row">
            <form action="<?= $baseUrl ?>/loja" method="GET" class="header-search-form">
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
                <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Eletrônicos & Telefonia') ?>" class="<?= (stripos($currentCat, 'eletrônic') !== false || stripos($currentCat, 'telefonia') !== false || stripos($currentCat, 'tecnologia') !== false) ? 'active' : '' ?>">Eletrônicos & Telefonia</a>
                <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Relógios & Smartwatches') ?>" class="<?= (stripos($currentCat, 'relógio') !== false || stripos($currentCat, 'relogio') !== false || stripos($currentCat, 'smartwatch') !== false) ? 'active' : '' ?>">Relógios & Smartwatches</a>
                <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Casa, Iluminação & Eletro') ?>" class="<?= (stripos($currentCat, 'casa') !== false || stripos($currentCat, 'iluminação') !== false || stripos($currentCat, 'eletro') !== false) ? 'active' : '' ?>">Casa, Iluminação & Eletro</a>
                <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Ferramentas & Utilidades') ?>" class="<?= (stripos($currentCat, 'ferramenta') !== false || stripos($currentCat, 'utilidade') !== false) ? 'active' : '' ?>">Ferramentas & Utilidades</a>
                <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Beleza, Saúde & Maquiagem') ?>" class="<?= (stripos($currentCat, 'beleza') !== false || stripos($currentCat, 'saúde') !== false || stripos($currentCat, 'maquiagem') !== false) ? 'active' : '' ?>">Beleza & Saúde</a>
                <a href="<?= $baseUrl ?>/loja?cat=Pet+Shop" class="<?= (stripos($currentCat, 'pet') !== false) ? 'active' : '' ?>">Pet Shop</a>
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

    <!-- Acesso Rápido: Loja & Conta do Cliente (visível no menu toggle/hambúrguer) -->
    <div class="drawer-account-block">
        <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 2px;">
            Conta &amp; Navegação Rápida
        </div>
        <div class="drawer-account-grid">
            <a href="<?= $baseUrl ?>/loja" class="drawer-action-btn primary">🛍️ Ver Loja Oficial</a>
            <?php if ($customerLoggedId > 0): ?>
                <a href="<?= $baseUrl ?>/minha-conta" class="drawer-action-btn">👤 <?= htmlspecialchars(explode(' ', trim($customerLoggedName))[0] ?: 'Minha Conta', ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= $baseUrl ?>/sair" class="drawer-action-btn">🚪 Sair</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/cadastro" class="drawer-action-btn">👤 Cadastrar</a>
                <a href="<?= $baseUrl ?>/entrar" class="drawer-action-btn">🔐 Acessar</a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/#desejos" class="drawer-action-btn">✨ Desejos</a>
            <a href="<?= $baseUrl ?>/rastrear" class="drawer-action-btn">📦 Rastrear</a>
        </div>
    </div>

    <div class="cat-drawer-list">
        <!-- Todas as Categorias -->
        <a href="<?= $baseUrl ?>/loja#produtos" class="cat-drawer-item <?= (empty($currentCat) && empty($currentSort)) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🌐</span>
                <span>Todas as Categorias (Ver Tudo)</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Mais Vendidos -->
        <a href="<?= $baseUrl ?>/loja?sort=sales#produtos" class="cat-drawer-item <?= ($currentSort === 'sales' && empty($currentCat)) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🏆</span>
                <span>Mais Vendidos (Best Sellers)</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Eletrônicos & Telefonia -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Eletrônicos & Telefonia') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'eletrônic') !== false || stripos($currentCat, 'telefonia') !== false || stripos($currentCat, 'tecnologia') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">📱</span>
                <span>Eletrônicos & Telefonia</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Relógios & Smartwatches -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Relógios & Smartwatches') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'relógio') !== false || stripos($currentCat, 'relogio') !== false || stripos($currentCat, 'smartwatch') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">⌚</span>
                <span>Relógios & Smartwatches</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Casa, Iluminação & Eletro -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Casa, Iluminação & Eletro') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'casa') !== false || stripos($currentCat, 'iluminação') !== false || stripos($currentCat, 'eletro') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🏠</span>
                <span>Casa, Iluminação & Eletro</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Ferramentas & Utilidades -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Ferramentas & Utilidades') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'ferramenta') !== false || stripos($currentCat, 'utilidade') !== false || stripos($currentCat, 'acessorio') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🛠️</span>
                <span>Ferramentas & Utilidades</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Beleza, Saúde & Maquiagem -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Beleza, Saúde & Maquiagem') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'beleza') !== false || stripos($currentCat, 'saúde') !== false || stripos($currentCat, 'maquiagem') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">✨</span>
                <span>Beleza, Saúde & Maquiagem</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Pet Shop -->
        <a href="<?= $baseUrl ?>/loja?cat=Pet+Shop#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'pet') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🐾</span>
                <span>Pet Shop</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Peças & Acessórios Automotivos -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Peças & Acessórios Automotivos') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'automotiv') !== false || stripos($currentCat, 'veículo') !== false || stripos($currentCat, 'veiculo') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🚗</span>
                <span>Peças & Acessórios Automotivos</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Roupas, Bolsas & Calçados -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Roupas, Bolsas & Calçados') ?>#produtos" class="cat-drawer-item <?= ((stripos($currentCat, 'roupa') !== false || stripos($currentCat, 'bolsa') !== false || stripos($currentCat, 'calçado') !== false || stripos($currentCat, 'vestuário') !== false) && stripos($currentCat, 'íntima') === false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">👗</span>
                <span>Roupas, Bolsas & Calçados</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Roupa Íntima & Pijamas -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Roupa Íntima & Pijamas') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'lingerie') !== false || stripos($currentCat, 'íntima') !== false || stripos($currentCat, 'intima') !== false || stripos($currentCat, 'pijama') !== false) ? 'active' : '' ?>">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">👙</span>
                <span>Roupa Íntima & Pijamas</span>
            </div>
            <span>➔</span>
        </a>

        <!-- Novidades & Uso Especial -->
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode('Novidades & Uso Especial') ?>#produtos" class="cat-drawer-item <?= (stripos($currentCat, 'novidade') !== false || stripos($currentCat, 'especial') !== false || stripos($currentCat, 'sex shop') !== false || stripos($currentCat, '+18') !== false) ? 'active' : '' ?>" style="border-color: rgba(239,68,68,0.3); color: #fca5a5;">
            <div style="display: flex; align-items: center;">
                <span class="item-icon">🔞</span>
                <span>Novidades & Uso Especial</span>
            </div>
            <span>➔</span>
        </a>
    </div>
</aside>
