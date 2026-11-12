<?php
/**
 * Template Home Oficial da Loja RachidD.com (Estilo AliExpress / Dropshipping Global)
 */
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// 1. Carrega produtos publicados ordenados pela atualização mais recente (ciclo 24h / edições no Admin)
$catalogProducts = [];
$homeCycleInfo = [
    'seconds_remaining'   => 0,
    'formatted_remaining' => '00:00:00',
    'is_due'              => false,
    'last_cycle_date'     => '',
];
try {
    if (isset($GLOBALS['app'])) {
        $container = $GLOBALS['app']->getContainer();
        if ($container->has(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class)) {
            $repo = $container->make(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
            $catalogProducts = $repo->list(['status' => 'published', 'sort' => 'updated_desc'], 12);
        }
        if ($container->has(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class)) {
            $miningSvc = $container->make(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class);
            $homeCycleInfo = $miningSvc->getDailyCycleInfo();
        }
    }
} catch (\Throwable $e) {
    $catalogProducts = [];
}

// 2. Fallback de produtos minerados virais caso o catálogo ainda esteja iniciando
if (empty($catalogProducts)) {
    $fallbackItems = [
        [
            'id' => 1,
            'slug' => 'smartwatch-ultra-series-9-titanium',
            'title' => 'Smartwatch Ultra Series 9 Titanium AMOLED NFC Monitor Cardíaco',
            'category' => 'Smartwatches & Áudio',
            'image' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=600&auto=format&fit=crop&q=80',
            'price' => 189.90,
            'ref_price' => 289.90,
            'rating' => 4.9,
            'sales' => 1420
        ],
        [
            'id' => 2,
            'slug' => 'fone-tws-pro-noise-cancelling-bluetooth-53',
            'title' => 'Fone TWS Pro ANC Cancelamento Ativo de Ruído Bluetooth 5.3',
            'category' => 'Smartwatches & Áudio',
            'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80',
            'price' => 129.50,
            'ref_price' => 210.00,
            'rating' => 4.8,
            'sales' => 980
        ],
        [
            'id' => 3,
            'slug' => 'mini-projetor-portatil-4k-smart-cinema-wifi',
            'title' => 'Mini Projetor Portátil 4K Smart Cinema Android 11 Wi-Fi 6',
            'category' => 'Tecnologia & Gadgets',
            'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&auto=format&fit=crop&q=80',
            'price' => 379.00,
            'ref_price' => 599.00,
            'rating' => 4.9,
            'sales' => 640
        ],
        [
            'id' => 4,
            'slug' => 'luminaria-lua-mistica-aladim-led-rgb-touch',
            'title' => 'Luminária Lua Mística Led 3D Touch 16 Cores com Controle Remoto',
            'category' => 'Casa Inteligente',
            'image' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=600&auto=format&fit=crop&q=80',
            'price' => 89.90,
            'ref_price' => 149.90,
            'rating' => 4.7,
            'sales' => 850
        ],
    ];
}
?>

<!-- HERO SECTION: As Mil e Uma Noites ✨ Lâmpada de Aladim -->
<section style="position: relative; padding: 70px 0 60px; text-align: center; overflow: hidden; background: linear-gradient(180deg, rgba(8,12,20,0.6) 0%, rgba(17,23,34,0.95) 100%);">
    <!-- Efeito de Estrelas Cintilantes e Cadentes -->
    <div class="hero-stars-container" style="position: absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:1;">
        <div class="hero-shooting-star" style="top: 20%; left: 15%; animation: heroShoot 6s infinite 1s;"></div>
        <div class="hero-shooting-star" style="top: 45%; left: 55%; animation: heroShoot 7s infinite 3s;"></div>
    </div>

    <div class="container" style="position: relative; z-index: 2;">
        <!-- Lâmpada Mágica Flutuante com Fumaça Mística -->
        <div style="margin-bottom: 20px; display: inline-block; filter: drop-shadow(0 0 24px rgba(212,175,55,0.6)); animation: floatLamp 3s ease-in-out infinite;">
            <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="Lâmpada Mágica de Aladim RachidD" style="height: 100px; width: auto; display: block;">
        </div>

        <style>
            @keyframes floatLamp {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-8px); }
            }
        </style>

        <div style="display: inline-block; background: rgba(212, 175, 55, 0.15); border: 1px solid rgba(212, 175, 55, 0.35); padding: 6px 18px; border-radius: 50px; margin-bottom: 18px;">
            <span style="color: #F0D060; font-weight: 700; font-size: 13px; letter-spacing: 1px; text-transform: uppercase;">
                🔎 Curadoria de Produtos Físicos Minerados • AliExpress Brasil
            </span>
        </div>

        <h1 style="font-size: clamp(32px, 5vw, 54px); font-weight: 800; color: #fff; margin-bottom: 16px; letter-spacing: -1px; line-height: 1.15;">
            Produtos Minerados com <br>
            <span class="text-gold">Curadoria Especializada</span>
        </h1>

        <p style="font-size: 17px; color: #cbd5e1; max-width: 760px; margin: 0 auto 32px auto; line-height: 1.65;">
            Somos uma curadoria especializada em localizar, via SDK oficial do AliExpress, produtos físicos já disponíveis no Brasil com as melhores <strong>notas, aprovações e termômetro de vendas</strong>. O valor exibido remunera nossa busca técnica, filtragem de fornecedores confiáveis e cobertura integral com <strong>garantia de entrega</strong>.
        </p>

        <!-- CTA Buttons -->
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 45px;">
            <a href="<?= $baseUrl ?>/loja" class="gold-button" style="font-size: 15px; padding: 14px 32px;">
                <span>✨ Explorar Catálogo Minerado</span>
                <span>➔</span>
            </a>
            <a href="#desejos" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 14px 28px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <span>🔥 Ver Desejos do Dia</span>
            </a>
        </div>

        <!-- Trust Stats (Mil e Uma Noites) -->
        <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.08); max-width: 840px; margin: 0 auto;">
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">4.7★+</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Notas &amp; Termômetro Real</div>
            </div>
            <div style="opacity: 0.3; align-self: center;">|</div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">100%</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Garantia de Entrega</div>
            </div>
            <div style="opacity: 0.3; align-self: center;">|</div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">🇧🇷 3 a 7 Dias</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Curadoria AliExpress Brasil</div>
            </div>
        </div>
    </div>
</section>

<!-- SEÇÃO: ✨ Desejos do Dia (Daily Flash Deals estilo Choice AliExpress) -->
<section id="desejos" style="padding: 70px 0 40px; background: #0b0f17;">
    <div class="container">
        
        <!-- Header da Seção com Timer Regressivo -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(212, 175, 55, 0.12); color: #F0D060; border: 1px solid rgba(212, 175, 55, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 8px;">
                    <span>⚡ OFERTAS RELÂMPAGO DO DIA</span>
                </div>
                <h2 style="font-size: clamp(24px, 3.5vw, 36px); font-weight: 800; color: #fff; margin: 0;">
                    ✨ Desejos do Dia: <span class="text-gold">Seleção do Gênio</span>
                </h2>
                <p style="color: #94a3b8; font-size: 14px; margin: 6px 0 0 0;">
                    Uma seleção especial sorteada diariamente com os maiores descontos da fábrica.
                </p>
            </div>

            <!-- Countdown Real sincronizado com o Ciclo 24h do DomainDrop -->
            <?php
            $homeRemSeconds = max(0, (int)($homeCycleInfo['seconds_remaining'] ?? 0));
            $homeTimerInitial = (string)($homeCycleInfo['formatted_remaining'] ?? '00:00:00');
            if ($homeTimerInitial === '') {
                $homeTimerInitial = '00:00:00';
            }
            ?>
            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212,175,55,0.3); padding: 8px 16px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 13px; flex-wrap: wrap;">
                <span style="color: #94a3b8;">Acaba em:</span>
                <span style="color: #F0D060; font-weight: 800; font-family: 'Fira Code', monospace; font-size: 15px;" id="flash-timer"><?= htmlspecialchars($homeTimerInitial, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <script>
            (function() {
                var homeCycleSeconds = <?= $homeRemSeconds ?>;
                var el = document.getElementById('flash-timer');
                var homeTimerInt = null;
                var autoTickFired = false;

                function triggerAutoCycleTick() {
                    if (autoTickFired) return;
                    autoTickFired = true;
                    fetch('<?= $baseUrl ?>/dropship/cron-tick', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data && typeof data.seconds_remaining === 'number' && data.seconds_remaining > 0) {
                            homeCycleSeconds = data.seconds_remaining;
                            if (data.rotated) {
                                window.location.reload();
                                return;
                            }
                            if (homeTimerInt === null) {
                                homeTimerInt = setInterval(updateHomeCycleTimer, 1000);
                            }
                        }
                    })
                    .catch(function() {});
                }

                function updateHomeCycleTimer() {
                    if (!el) return;
                    if (homeCycleSeconds <= 0) {
                        el.textContent = '00:00:00';
                        if (homeTimerInt !== null) {
                            clearInterval(homeTimerInt);
                            homeTimerInt = null;
                        }
                        triggerAutoCycleTick();
                        return;
                    }
                    homeCycleSeconds--;
                    var h = String(Math.floor(homeCycleSeconds / 3600)).padStart(2, '0');
                    var m = String(Math.floor((homeCycleSeconds % 3600) / 60)).padStart(2, '0');
                    var s = String(homeCycleSeconds % 60).padStart(2, '0');
                    el.textContent = h + ':' + m + ':' + s;
                }

                if (homeCycleSeconds > 0) {
                    homeTimerInt = setInterval(updateHomeCycleTimer, 1000);
                } else {
                    triggerAutoCycleTick();
                }

                window.addEventListener('pageshow', function (evt) {
                    if (evt && evt.persisted) {
                        window.location.reload();
                    }
                });
            })();
        </script>

        <style>
            .home-products-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(265px, 1fr));
                gap: 20px;
            }
            .home-card-body {
                padding: 16px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .home-card-actions {
                display: flex;
                gap: 6px;
                margin-top: 6px;
            }
            .home-btn-details {
                flex: 1;
                background: rgba(255,255,255,0.06);
                border: 1.5px solid rgba(212,175,55,0.4);
                color: #F0D060 !important;
                text-decoration: none;
                padding: 0 6px;
                height: 36px;
                border-radius: 6px;
                font-size: 11.5px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                white-space: nowrap;
                box-sizing: border-box;
                transition: all 0.2s ease;
            }
            .home-btn-details:hover {
                background: rgba(212,175,55,0.18);
                border-color: #F0D060;
            }
            .home-btn-buy {
                flex: 1;
                background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
                color: #0b0f17 !important;
                text-decoration: none;
                padding: 0 6px;
                height: 36px;
                border-radius: 6px;
                font-size: 11.5px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                white-space: nowrap;
                box-sizing: border-box;
                transition: all 0.2s ease;
            }
            .home-btn-buy:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(212, 175, 55, 0.55);
            }
            @media (max-width: 580px) {
                .home-products-grid {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }
            }
        </style>

        <!-- Product Grid -->
        <div class="home-products-grid">
            <?php if (!empty($catalogProducts)): ?>
                <?php foreach ($catalogProducts as $p): ?>
                    <?php 
                    $productUrl = !empty($p->slug) ? $baseUrl . '/produto/' . urlencode($p->slug) : $baseUrl . '/produto/' . (int)$p->id;
                    $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$p->id;
                    $refPrice = $p->salePriceBrl * 1.4;
                    ?>
                    <article class="store-card">
                        <div style="position: relative; width: 100%; height: 230px; background: #070a0e; overflow: hidden;">
                            <a href="<?= htmlspecialchars($productUrl) ?>">
                                <img src="<?= htmlspecialchars($p->mainImageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <span style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 10px; padding: 3px 7px; border-radius: 4px;">
                                -40% OFF
                            </span>
                            <?php if (!empty($p->category)): ?>
                                <span style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.75); color: #fff; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 5px;">
                                    <?= htmlspecialchars($p->category) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="home-card-body">
                            <div>
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; line-height: 1.4; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <a href="<?= htmlspecialchars($productUrl) ?>" style="color: #fff; text-decoration: none;">
                                        <?= htmlspecialchars($p->title) ?>
                                    </a>
                                </h3>
                                <?php $isNational = (!empty($p->attributes['origem_brasil']) || (isset($p->attributes['ship_from']) && $p->attributes['ship_from'] === 'BR')); ?>
                                <div style="margin-bottom: 14px;">
                                    <div style="font-size: 11px; color: #94a3b8; text-decoration: line-through;">
                                        R$ <?= number_format($refPrice, 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                        R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 10.5px; color: <?= $isNational ? '#4ade80' : '#F0D060' ?>; margin-top: 2px; font-weight: <?= $isNational ? '600' : 'normal' ?>;">
                                        <?= $isNational ? '🇧🇷 Estoque no Brasil • Envio Rápido 3 a 7 dias' : '⚡ Frete Internacional Grátis & Rastreado' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="home-card-actions">
                                <a href="<?= htmlspecialchars($productUrl) ?>" class="home-btn-details">
                                    <span>🔍</span>
                                    <span>Ver Detalhes</span>
                                </a>
                                <a href="<?= htmlspecialchars($checkoutUrl) ?>" class="home-btn-buy">
                                    Comprar ➔
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback de demonstração caso ainda não tenha minerado itens -->
                <?php foreach ($fallbackItems as $item): ?>
                    <article class="store-card">
                        <div style="position: relative; width: 100%; height: 230px; background: #070a0e; overflow: hidden;">
                            <a href="<?= $baseUrl ?>/loja">
                                <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <span style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 10px; padding: 3px 7px; border-radius: 4px;">
                                DESTAQUE
                            </span>
                            <span style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.75); color: #fff; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 5px;">
                                <?= $item['category'] ?>
                            </span>
                        </div>
                        <div class="home-card-body">
                            <div>
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; line-height: 1.4; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <a href="<?= $baseUrl ?>/loja" style="color: #fff; text-decoration: none;">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>
                                <div style="margin-bottom: 14px;">
                                    <div style="font-size: 11px; color: #94a3b8; text-decoration: line-through;">
                                        R$ <?= number_format($item['ref_price'], 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                        R$ <?= number_format($item['price'], 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 10.5px; color: #F0D060; margin-top: 2px;">
                                        ★ <?= $item['rating'] ?> (<?= $item['sales'] ?> vendidos)
                                    </div>
                                </div>
                            </div>
                            <div class="home-card-actions">
                                <a href="<?= $baseUrl ?>/loja" class="home-btn-details">
                                    <span>🔍</span>
                                    <span>Ver Detalhes</span>
                                </a>
                                <a href="<?= $baseUrl ?>/loja" class="home-btn-buy">
                                    Ver Oferta ➔
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="<?= $baseUrl ?>/loja" class="gold-button" style="padding: 14px 40px; font-size: 14px;">
                Ver Todas as Ofertas da Loja Oficial ➔
            </a>
        </div>
    </div>
</section>

<!-- SEÇÃO: Cadastro de Cliente na Home (Pedidos, Rastreamento e Devoluções) -->
<?php
$homeCustomerId = 0;
$homeCustomerName = '';
$homeCsrf = '';
try {
    if (isset($GLOBALS['app'])) {
        $hc = $GLOBALS['app']->getContainer();
        if ($hc->has(\DomainSystem\Core\Contracts\SessionManagerInterface::class)) {
            $hs = $hc->make(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
            $homeCustomerId = (int)$hs->get('customer_id', 0);
            $homeCustomerName = (string)$hs->get('customer_name', '');
            $homeCsrf = (string)$hs->getCsrfToken();
        }
    }
} catch (\Throwable $e) {
}
?>
<style>
.home-signup-section {
    padding: 55px 0;
    background: linear-gradient(135deg, rgba(16,23,36,0.98) 0%, rgba(11,15,23,1) 100%);
    border-top: 1px solid rgba(212, 175, 55, 0.2);
}
.home-signup-card {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
    gap: 32px;
    align-items: center;
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(212,175,55,0.28);
    border-radius: 18px;
    padding: 32px;
    box-sizing: border-box;
    width: 100%;
    overflow: hidden;
}
.home-signup-title {
    font-size: 26px;
    font-weight: 800;
    color: #fff;
    margin: 14px 0 10px 0;
    line-height: 1.25;
}
.home-signup-form {
    background: rgba(7, 10, 16, 0.85);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 11px;
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
}
.home-signup-row-2 {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 10px;
}
.home-signup-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    margin-top: 4px;
    align-items: stretch;
}
@media (max-width: 960px) {
    .home-signup-section {
        padding: 38px 0;
    }
    .home-signup-card {
        grid-template-columns: 1fr;
        gap: 22px;
        padding: 24px 20px;
    }
}
@media (max-width: 600px) {
    .home-signup-section {
        padding: 28px 0;
    }
    .home-signup-card {
        padding: 18px 14px;
        gap: 18px;
        border-radius: 14px;
    }
    .home-signup-title {
        font-size: 20px;
    }
    .home-signup-form {
        padding: 15px 12px;
        border-radius: 12px;
    }
    .home-signup-row-2,
    .home-signup-actions {
        grid-template-columns: 1fr;
    }
}
</style>
<section id="cadastro-cliente" class="home-signup-section">
    <div class="container">
        <div class="home-signup-card">
            <div style="min-width: 0;">
                <span style="display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #F0D060; text-transform: uppercase; background: rgba(212,175,55,0.12); padding: 4px 12px; border-radius: 99px; border: 1px solid rgba(212,175,55,0.35);">
                    Conta Gratuita do Comprador
                </span>
                <h2 class="home-signup-title">
                    Cadastre-se para Acompanhar Pedidos e Garantias
                </h2>
                <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0 0 16px 0;">
                    Com seu cadastro na RachidD.com, seus pedidos ficam vinculados ao seu painel com barra de prazo de entrega, checkout agilizado e área dedicada para enviar fotos ou PDF caso precise de reembolso ou intermediação.
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px 14px; font-size: 12.5px; color: #cbd5e1;">
                    <span>✅ Garantia 7 a 15 dias</span>
                    <span>✅ Envio de fotos / PDF</span>
                    <span>✅ Suporte direto com fornecedor</span>
                </div>
            </div>

            <?php if ($homeCustomerId > 0): ?>
                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 14px; padding: 22px 16px; text-align: center; box-sizing: border-box;">
                    <div style="font-size: 32px; margin-bottom: 8px;">👋</div>
                    <h3 style="margin: 0 0 6px 0; color: #fff; font-size: 18px;">Bem-vindo(a), <?= htmlspecialchars($homeCustomerName, ENT_QUOTES, 'UTF-8') ?>!</h3>
                    <p style="margin: 0 0 18px 0; font-size: 13px; color: #cbd5e1;">Sua conta já está ativa para compras, rastreamento e reclamações.</p>
                    <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <a href="<?= $baseUrl ?>/minha-conta" class="gold-button" style="padding: 11px 22px; font-size: 13px;">Acessar Minha Conta e Pedidos ➔</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="<?= $baseUrl ?>/cadastro" class="home-signup-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($homeCsrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="home-signup-row-2">
                        <input type="text" name="name" required placeholder="Nome Completo *" style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.14); border-radius: 8px; color: #fff; font-size: 13px;">
                        <input type="email" name="email" required placeholder="Seu melhor E-mail *" style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.14); border-radius: 8px; color: #fff; font-size: 13px;">
                    </div>
                    <div class="home-signup-row-2">
                        <input type="text" name="cpf" inputmode="numeric" maxlength="14" placeholder="CPF: 000.000.000-00" oninput="homeMaskCpf(this)" style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.14); border-radius: 8px; color: #fff; font-size: 13px;">
                        <input type="tel" name="phone" inputmode="tel" maxlength="15" placeholder="WhatsApp: (11) 99999-9999" oninput="homeMaskPhone(this)" style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.14); border-radius: 8px; color: #fff; font-size: 13px;">
                    </div>
                    <div style="position: relative;">
                        <input id="home-reg-pass" type="password" name="password" required minlength="6" placeholder="Crie uma Senha (mín. 6 dígitos) *" style="width: 100%; box-sizing: border-box; padding: 10px 38px 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.14); border-radius: 8px; color: #fff; font-size: 13px;">
                        <button type="button" onclick="(function(b){var i=document.getElementById('home-reg-pass');if(!i)return;var s=i.type==='password';i.type=s?'text':'password';b.textContent=s?'🙈':'👁️';b.style.color=s?'#F0D060':'#94a3b8';})(this)" aria-label="Mostrar ou ocultar senha" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: inline-flex; align-items: center;" title="Mostrar/ocultar senha">👁️</button>
                    </div>
                    <script>
                    function homeMaskCpf(el) {
                        var v = el.value.replace(/\D/g, '').slice(0, 11);
                        if (v.length > 9) el.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                        else if (v.length > 6) el.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                        else if (v.length > 3) el.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                        else el.value = v;
                    }
                    function homeMaskPhone(el) {
                        var v = el.value.replace(/\D/g, '').slice(0, 11);
                        if (v.length > 10) el.value = v.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
                        else if (v.length > 6) el.value = v.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
                        else if (v.length > 2) el.value = v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
                        else if (v.length > 0) el.value = '(' + v;
                        else el.value = '';
                    }
                    </script>
                    <div class="home-signup-actions">
                        <button type="submit" style="width: 100%; padding: 12px 16px; border: none; border-radius: 9px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 14px; cursor: pointer;">
                            Criar Minha Conta Grátis ➔
                        </button>
                        <a href="<?= $baseUrl ?>/entrar" style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 18px; border-radius: 9px; background: rgba(212,175,55,0.12); border: 1px solid rgba(212,175,55,0.45); color: #F0D060; font-weight: 800; font-size: 13.5px; text-decoration: none; white-space: nowrap;">
                            🔐 Acessar
                        </a>
                    </div>
                    <div style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 2px;">
                        Já é cadastrado? <a href="<?= $baseUrl ?>/entrar" style="color: #F0D060; font-weight: 700; text-decoration: none;">Entrar na Minha Conta</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- SEÇÃO: Garantias de Compra Internacional -->
<section style="padding: 50px 0; background: #080c14; border-top: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06);">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 24px;">
            <div style="display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 18px; border-radius: 10px;">
                <span style="font-size: 32px;">🚚</span>
                <div>
                    <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; font-weight: 700;">Envio Rastreado</h4>
                    <p style="color: #94a3b8; margin: 0; font-size: 12px;">Código de rastreio ponto a ponto</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 18px; border-radius: 10px;">
                <span style="font-size: 32px;">🛡️</span>
                <div>
                    <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; font-weight: 700;">Garantia de 7 Dias</h4>
                    <p style="color: #94a3b8; margin: 0; font-size: 12px;">Reembolso garantido por lei</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 18px; border-radius: 10px;">
                <span style="font-size: 32px;">🔒</span>
                <div>
                    <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; font-weight: 700;">Checkout Protegido</h4>
                    <p style="color: #94a3b8; margin: 0; font-size: 12px;">Mercado Pago, Pix e PayPal</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 18px; border-radius: 10px;">
                <span style="font-size: 32px;">💬</span>
                <div>
                    <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; font-weight: 700;">Suporte Dedicado</h4>
                    <p style="color: #94a3b8; margin: 0; font-size: 12px;">Atendimento em português</p>
                </div>
            </div>
        </div>
    </div>
</section>
