<?php
/**
 * Template Home Oficial da Loja RachidD.com (Estilo AliExpress / Dropshipping Global)
 */
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// 1. Tenta carregar os produtos reais do catálogo minerado do DomainDrop
$catalogProducts = [];
try {
    if (isset($GLOBALS['app'])) {
        $container = $GLOBALS['app']->getContainer();
        if ($container->has(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class)) {
            $repo = $container->make(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
            $catalogProducts = $repo->list(['status' => 'published'], 12);
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
                🧞 Faça um Desejo... As Melhores Ofertas do Mercado
            </span>
        </div>

        <h1 style="font-size: clamp(32px, 5vw, 54px); font-weight: 800; color: #fff; margin-bottom: 16px; letter-spacing: -1px; line-height: 1.15;">
            Descubra os Tesouros Virais da <br>
            <span class="text-gold">RachidD.com Store</span>
        </h1>

        <p style="font-size: 18px; color: #cbd5e1; max-width: 720px; margin: 0 auto 32px auto; line-height: 1.6;">
            Curadoria internacional com preços direto das fábricas do AliExpress. Envio com seguro, código de rastreamento e pagamento em até 12x ou Pix com desconto.
        </p>

        <!-- CTA Buttons -->
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 45px;">
            <a href="<?= $baseUrl ?>/loja" class="gold-button" style="font-size: 15px; padding: 14px 32px;">
                <span>✨ Explorar Catálogo Completo</span>
                <span>➔</span>
            </a>
            <a href="#desejos" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 14px 28px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <span>🔥 Ver Desejos do Dia</span>
            </a>
        </div>

        <!-- Trust Stats (Mil e Uma Noites) -->
        <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.08); max-width: 800px; margin: 0 auto;">
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">+5.000</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Desejos Realizados</div>
            </div>
            <div style="opacity: 0.3; align-self: center;">|</div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">100%</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Garantia de Entrega</div>
            </div>
            <div style="opacity: 0.3; align-self: center;">|</div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #F0D060;">10 a 15 Dias</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Frete Rastreável</div>
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

            <!-- Fake Countdown Timer para Urgência -->
            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212,175,55,0.3); padding: 8px 16px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 13px;">
                <span style="color: #94a3b8;">As ofertas terminam em:</span>
                <span style="color: #F0D060; font-weight: 800; font-family: 'Fira Code', monospace; font-size: 15px;" id="flash-timer">07:42:19</span>
            </div>
        </div>

        <script>
            // Timer regressivo dinâmico para urgência e conversão
            function updateTimer() {
                const now = new Date();
                const midnight = new Date();
                midnight.setHours(24, 0, 0, 0);
                const diff = midnight - now;
                const h = String(Math.floor((diff / (1000 * 60 * 60)) % 24)).padStart(2, '0');
                const m = String(Math.floor((diff / (1000 * 60)) % 60)).padStart(2, '0');
                const s = String(Math.floor((diff / 1000) % 60)).padStart(2, '0');
                const el = document.getElementById('flash-timer');
                if (el) el.innerText = `${h}:${m}:${s}`;
            }
            setInterval(updateTimer, 1000);
            updateTimer();
        </script>

        <!-- Product Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            <?php if (!empty($catalogProducts)): ?>
                <?php foreach ($catalogProducts as $p): ?>
                    <?php 
                    $productUrl = !empty($p->slug) ? $baseUrl . '/produto/' . urlencode($p->slug) : $baseUrl . '/produto/' . (int)$p->id;
                    $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$p->id;
                    $refPrice = $p->salePriceBrl * 1.4;
                    ?>
                    <article class="store-card">
                        <div style="position: relative; width: 100%; height: 250px; background: #070a0e; overflow: hidden;">
                            <a href="<?= htmlspecialchars($productUrl) ?>">
                                <img src="<?= htmlspecialchars($p->mainImageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <span style="position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 11px; padding: 4px 8px; border-radius: 4px;">
                                -40% OFF
                            </span>
                            <?php if (!empty($p->category)): ?>
                                <span style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.75); color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                    <?= htmlspecialchars($p->category) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <a href="<?= htmlspecialchars($productUrl) ?>" style="color: #fff; text-decoration: none;">
                                        <?= htmlspecialchars($p->title) ?>
                                    </a>
                                </h3>
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">
                                        R$ <?= number_format($refPrice, 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 22px; font-weight: 800; color: #5cd67d; font-family: monospace;">
                                        R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 11px; color: #F0D060; margin-top: 3px;">
                                        ⚡ Frete Internacional Grátis & Rastreado
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 6px;">
                                <a href="<?= htmlspecialchars($productUrl) ?>" style="flex: 1; background: rgba(255,255,255,0.06); border: 1.5px solid rgba(212,175,55,0.4); color: #F0D060; text-decoration: none; padding: 11px 8px; border-radius: 8px; font-size: 13px; font-weight: 700; text-align: center; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 5px; box-sizing: border-box;" onmouseover="this.style.background='rgba(212,175,55,0.18)'; this.style.borderColor='#F0D060';" onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='rgba(212,175,55,0.4)';">
                                    <span>🔍</span>
                                    <span>Ver Detalhes</span>
                                </a>
                                <a href="<?= htmlspecialchars($checkoutUrl) ?>" class="gold-button" style="flex: 1.15; box-sizing: border-box; font-size: 13px; padding: 11px 8px; border-radius: 8px; text-align: center; text-transform: uppercase;">
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
                        <div style="position: relative; width: 100%; height: 250px; background: #070a0e; overflow: hidden;">
                            <a href="<?= $baseUrl ?>/loja">
                                <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <span style="position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 11px; padding: 4px 8px; border-radius: 4px;">
                                DESTAQUE
                            </span>
                            <span style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.75); color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                <?= $item['category'] ?>
                            </span>
                        </div>
                        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; line-height: 1.4;">
                                    <a href="<?= $baseUrl ?>/loja" style="color: #fff; text-decoration: none;">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">
                                        R$ <?= number_format($item['ref_price'], 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 22px; font-weight: 800; color: #5cd67d; font-family: monospace;">
                                        R$ <?= number_format($item['price'], 2, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 11px; color: #F0D060; margin-top: 3px;">
                                        ★ <?= $item['rating'] ?> (<?= $item['sales'] ?> vendidos)
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 6px;">
                                <a href="<?= $baseUrl ?>/loja" style="flex: 1; background: rgba(255,255,255,0.06); border: 1.5px solid rgba(212,175,55,0.4); color: #F0D060; text-decoration: none; padding: 11px 8px; border-radius: 8px; font-size: 13px; font-weight: 700; text-align: center; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 5px; box-sizing: border-box;">
                                    <span>🔍</span>
                                    <span>Ver Detalhes</span>
                                </a>
                                <a href="<?= $baseUrl ?>/loja" class="gold-button" style="flex: 1.15; box-sizing: border-box; font-size: 13px; padding: 11px 8px; border-radius: 8px; text-align: center;">
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
