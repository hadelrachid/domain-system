<?php
/**
 * View Pública da Página Dedicada do Produto (/produto/{slug})
 *
 * @var \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto $product
 * @var string $checkoutUrl
 * @var string $baseUrl
 */
$refPrice = $product->salePriceBrl * 1.35;
$allImages = !empty($product->images) ? array_values(array_filter($product->images)) : [];
if (empty($allImages) && !empty($product->mainImageUrl)) {
    $allImages = [$product->mainImageUrl];
}
$isAdult = str_contains($product->category, '+18') || stripos($product->category, 'sex shop') !== false;
?>
<div class="domain-drop-product-page" style="max-width: 1200px; margin: 0 auto; padding: 24px 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #cbd5e1; box-sizing: border-box;">

    <!-- Breadcrumbs -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #94a3b8; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>/" style="color: #94a3b8; text-decoration: none;">Início</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/loja" style="color: #94a3b8; text-decoration: none;">Loja</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode($product->category) ?>" style="color: #ff6b6b; text-decoration: none;">
            <?= htmlspecialchars($product->category) ?>
        </a>
        <span>/</span>
        <span style="color: #fff; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            <?= htmlspecialchars($product->title) ?>
        </span>
    </nav>

    <!-- Product Layout: Gallery + Buy Box -->
    <div class="product-top-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 32px; margin-bottom: 45px;">
        
        <!-- Gallery Column -->
        <div>
            <!-- Main Image Stage with Zoom trigger -->
            <div id="main-product-stage" onclick="openVisualBookLightbox(currentActiveImageIndex)" class="main-stage-box" style="background: #0c0f12; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; overflow: hidden; height: 420px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; position: relative; cursor: zoom-in; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                <?php if (!empty($product->mainImageUrl)): ?>
                    <img id="main-product-img" src="<?= htmlspecialchars($product->mainImageUrl) ?>" alt="<?= htmlspecialchars($product->title) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80';" style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.25s ease;">
                    <div style="position: absolute; bottom: 12px; right: 12px; background: rgba(11,15,23,0.85); color: #F0D060; border: 1px solid rgba(212,175,55,0.4); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 5px; pointer-events: none; backdrop-filter: blur(4px);">
                        <span>🔍</span> <span>Toque para Ampliar</span>
                    </div>
                <?php else: ?>
                    <div style="font-size: 50px;">📦</div>
                <?php endif; ?>
            </div>

            <!-- Horizontal Thumbnails Strip -->
            <?php if (count($allImages) > 1): ?>
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px;" class="custom-scroll">
                    <?php foreach ($allImages as $idx => $thumb): ?>
                        <div class="product-thumb-item <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>" onclick="selectProductImage(<?= $idx ?>, '<?= htmlspecialchars($thumb, ENT_QUOTES) ?>')" style="width: 72px; height: 72px; background: #0c0f12; border: 2px solid <?= $idx === 0 ? '#D4AF37' : 'rgba(255,255,255,0.12)' ?>; border-radius: 8px; overflow: hidden; cursor: pointer; flex-shrink: 0; transition: border-color 0.2s ease, transform 0.2s ease;">
                            <img src="<?= htmlspecialchars($thumb) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Buy Box Column -->
        <div style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <?php $isNational = (!empty($product->attributes['origem_brasil']) || (isset($product->attributes['ship_from']) && $product->attributes['ship_from'] === 'BR')); ?>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                    <span style="background: <?= $isAdult ? 'rgba(239,68,68,0.2)' : 'rgba(255,71,71,0.15)' ?>; color: <?= $isAdult ? '#f87171' : '#ff6b6b' ?>; border: <?= $isAdult ? '1px solid rgba(239,68,68,0.4)' : 'none' ?>; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
                        <?= $isAdult ? '🔞 ' : '' ?><?= htmlspecialchars($product->category) ?>
                    </span>
                    <?php if ($isNational): ?>
                        <span style="background: rgba(34,197,94,0.14); border: 1px solid rgba(34,197,94,0.4); color: #4ade80; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                            <span>🇧🇷</span> <span>Estoque no Brasil (Envio 3 a 7 dias)</span>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 style="font-size: clamp(20px, 3vw, 28px); font-weight: 700; color: #fff; margin: 0 0 14px 0; line-height: 1.3;">
                    <?= htmlspecialchars($product->title) ?>
                </h1>

                <?php if ($product->rating > 0 || $product->ordersCount > 0): ?>
                    <div style="display: flex; align-items: center; gap: 14px; font-size: 13px; color: #94a3b8; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <?php if ($product->rating > 0): ?>
                            <span style="color: #fbbf24; font-weight: 600;">★ <?= number_format($product->rating, 1) ?> de avaliação</span>
                        <?php endif; ?>
                        <?php if ($product->ordersCount > 0): ?>
                            <span>• <?= number_format($product->ordersCount, 0, ',', '.') ?> unidades vendidas</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Price Card -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; margin-bottom: 22px;">
                    <div style="font-size: 13px; color: #94a3b8; text-decoration: line-through; margin-bottom: 4px;">
                        De: R$ <?= number_format($refPrice, 2, ',', '.') ?>
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #5cd67d; font-family: monospace;">
                        R$ <?= number_format($product->salePriceBrl, 2, ',', '.') ?>
                    </div>
                    <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">
                        em até <strong>12x sem juros</strong> no cartão ou Pix à vista
                    </div>
                </div>

                <!-- CTA Button -->
                <a href="<?= htmlspecialchars($checkoutUrl) ?>" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; box-sizing: border-box; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; text-decoration: none; padding: 16px 24px; border-radius: 8px; font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4); margin-bottom: 24px; transition: transform 0.2s ease;">
                    <span>Comprar Agora</span>
                    <span>➔</span>
                </a>

                <!-- Trust Badges -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; color: #94a3b8;">
                    <div style="background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
                        <span>🚚</span> <span>Envio Internacional com Rastreio</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
                        <span>🛡️</span> <span>Garantia de Satisfação ou Reembolso</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
                        <span>🔒</span> <span>Pagamento 100% Criptografado</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
                        <span>💬</span> <span>Suporte em Português</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- 📸 VISUAL BOOK OFICIAL DO PRODUTO (Lookbook de Ângulos & Detalhes) -->
    <div class="domain-drop-visual-book-box" style="background: #171d22; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; margin-bottom: 40px; box-shadow: 0 12px 30px rgba(0,0,0,0.3);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 22px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <span style="font-size: 20px;">📸</span>
                    <h2 style="font-size: 20px; font-weight: 700; color: #fff; margin: 0;">
                        Visual Book & Galeria de Detalhes
                    </h2>
                </div>
                <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                    Fotos oficiais em alta resolução do fabricante internacional. Toque em qualquer ângulo para inspecionar em tela cheia.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="background: rgba(212, 175, 55, 0.12); color: #F0D060; border: 1px solid rgba(212, 175, 55, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <span>✨</span> <?= count($allImages) ?> <?= count($allImages) === 1 ? 'Foto Full HD' : 'Fotos em Full HD' ?>
                </span>
            </div>
        </div>

        <!-- Visual Book Flex Container: 1 por linha no Mobile, 2 no Tablet, 4 no Desktop -->
        <div class="visual-book-flex">
            <?php foreach ($allImages as $idx => $imgUrl): ?>
                <div class="visual-book-card" onclick="openVisualBookLightbox(<?= $idx ?>)">
                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Ângulo <?= $idx + 1 ?> - <?= htmlspecialchars($product->title) ?>" loading="lazy">
                    <div class="vb-card-overlay">
                        <span class="vb-card-badge">
                            🔍 Ampliar Ângulo #<?= $idx + 1 ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Full Description & Persuasive Copy -->
    <div style="background: #171d22; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 28px; margin-bottom: 40px;">
        <h2 style="font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
            Descrição do Produto
        </h2>
        <div style="font-size: 15px; line-height: 1.8; color: #cbd5e1;">
            <?= $product->description ?>
        </div>
    </div>

</div>

<!-- LIGHTBOX MODAL FULLSCREEN FOR VISUAL BOOK -->
<div id="visualBookLightbox" style="display: none; position: fixed; inset: 0; z-index: 999999; background: rgba(0, 0, 0, 0.94); backdrop-filter: blur(8px); align-items: center; justify-content: center; flex-direction: column;">
    <!-- Close button -->
    <button onclick="closeVisualBookLightbox()" aria-label="Fechar" style="position: absolute; top: 18px; right: 20px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #fff; font-size: 24px; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s ease; z-index: 10;">
        ✕
    </button>

    <!-- Navigation Chevrons -->
    <?php if (count($allImages) > 1): ?>
        <button onclick="navigateLightbox(-1)" aria-label="Anterior" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #fff; font-size: 28px; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s ease; z-index: 10;">
            ❮
        </button>
        <button onclick="navigateLightbox(1)" aria-label="Próximo" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #fff; font-size: 28px; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s ease; z-index: 10;">
            ❯
        </button>
    <?php endif; ?>

    <!-- Main Lightbox Image Container -->
    <div style="max-width: 92vw; max-height: 80vh; display: flex; align-items: center; justify-content: center;">
        <img id="lightboxActiveImg" src="" alt="Visual Book" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 50px rgba(0,0,0,0.85);">
    </div>

    <!-- Image Counter & Title Info -->
    <div style="margin-top: 16px; text-align: center; color: #94a3b8; font-size: 13px; max-width: 80vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
        <span id="lightboxCounter" style="color: #F0D060; font-weight: 700; margin-right: 8px;"></span>
        <span><?= htmlspecialchars($product->title) ?></span>
    </div>
</div>

<style>
/* Visual Book Container em Modo Flex */
.visual-book-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    width: 100%;
    box-sizing: border-box;
}

/* Card base do Visual Book */
.visual-book-card {
    background: #0c0f12;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.visual-book-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    display: block;
}

.visual-book-card .vb-card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(11,15,23,0.55);
    opacity: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: opacity 0.2s ease;
}

.visual-book-card .vb-card-badge {
    background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
    color: #0b0f17;
    font-size: 11px;
    font-weight: 800;
    padding: 6px 14px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

.visual-book-card:hover {
    transform: translateY(-4px);
    border-color: rgba(212, 175, 55, 0.6) !important;
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.18);
}

.visual-book-card:hover img {
    transform: scale(1.05);
}

.visual-book-card:hover .vb-card-overlay {
    opacity: 1 !important;
}

/* DESKTOP (>= 992px): 4 Imagens por Linha */
@media (min-width: 992px) {
    .visual-book-card {
        flex: 0 0 calc(25% - 12px);
        max-width: calc(25% - 12px);
        aspect-ratio: 1 / 1;
    }
}

/* TABLET (601px até 991px): EXATAMENTE 2 IMAGENS POR LINHA */
@media (min-width: 601px) and (max-width: 991px) {
    .visual-book-card {
        flex: 0 0 calc(50% - 8px);
        max-width: calc(50% - 8px);
        aspect-ratio: 1 / 1;
    }
}

/* MOBILE / SMARTPHONE (<= 600px): EXATAMENTE 1 IMAGEM POR LINHA */
@media (max-width: 600px) {
    .visual-book-flex {
        gap: 14px;
    }
    .visual-book-card {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        width: 100% !important;
        aspect-ratio: 4 / 3 !important;
        min-height: 250px;
    }
    .visual-book-card .vb-card-overlay {
        opacity: 1 !important;
        background: linear-gradient(180deg, transparent 55%, rgba(11,15,23,0.75) 100%) !important;
        justify-content: flex-end !important;
        padding-bottom: 14px !important;
    }
    .main-stage-box {
        height: 320px !important;
    }
}

/* Thumbnails da capa */
.product-thumb-item:hover {
    border-color: rgba(212, 175, 55, 0.6) !important;
    transform: scale(1.05);
}
.product-thumb-item.active {
    border-color: #D4AF37 !important;
    box-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
}
</style>

<script>
var productGalleryImages = <?= json_encode($allImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var currentActiveImageIndex = 0;

function selectProductImage(index, url) {
    currentActiveImageIndex = index;
    var mainImg = document.getElementById('main-product-img');
    if (mainImg) {
        mainImg.style.opacity = '0.3';
        setTimeout(function() {
            mainImg.src = url;
            mainImg.style.opacity = '1';
        }, 120);
    }
    document.querySelectorAll('.product-thumb-item').forEach(function(el, i) {
        if (i === index) {
            el.classList.add('active');
            el.style.borderColor = '#D4AF37';
        } else {
            el.classList.remove('active');
            el.style.borderColor = 'rgba(255,255,255,0.12)';
        }
    });
}

function openVisualBookLightbox(index) {
    if (!productGalleryImages || productGalleryImages.length === 0) return;
    currentActiveImageIndex = (index >= 0 && index < productGalleryImages.length) ? index : 0;
    updateLightbox();
    var lb = document.getElementById('visualBookLightbox');
    if (lb) {
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeVisualBookLightbox() {
    var lb = document.getElementById('visualBookLightbox');
    if (lb) {
        lb.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function navigateLightbox(step) {
    if (!productGalleryImages || productGalleryImages.length <= 1) return;
    currentActiveImageIndex = (currentActiveImageIndex + step + productGalleryImages.length) % productGalleryImages.length;
    updateLightbox();
}

function updateLightbox() {
    var imgEl = document.getElementById('lightboxActiveImg');
    var counterEl = document.getElementById('lightboxCounter');
    if (imgEl && productGalleryImages[currentActiveImageIndex]) {
        imgEl.src = productGalleryImages[currentActiveImageIndex];
    }
    if (counterEl) {
        counterEl.textContent = 'Foto ' + (currentActiveImageIndex + 1) + ' de ' + productGalleryImages.length;
    }
}

// Atalhos de teclado no Lightbox (ESC fecha, Setas navegam)
document.addEventListener('keydown', function(e) {
    var lb = document.getElementById('visualBookLightbox');
    if (lb && lb.style.display === 'flex') {
        if (e.key === 'Escape') closeVisualBookLightbox();
        if (e.key === 'ArrowLeft') navigateLightbox(-1);
        if (e.key === 'ArrowRight') navigateLightbox(1);
    }
});
</script>
