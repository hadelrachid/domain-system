<?php
/**
 * View Pública da Página Dedicada do Produto (/produto/{slug})
 *
 * @var \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto $product
 * @var string $checkoutUrl
 * @var string $baseUrl
 */
$refPrice = $product->salePriceBrl * 1.35;
?>
<div class="domain-drop-product-page" style="max-width: 1200px; margin: 0 auto; padding: 30px 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #cbd5e1;">

    <!-- Breadcrumbs -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #94a3b8; margin-bottom: 28px; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>/" style="color: #94a3b8; text-decoration: none;">Início</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/loja" style="color: #94a3b8; text-decoration: none;">Loja</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/loja?cat=<?= urlencode($product->category) ?>" style="color: #ff6b6b; text-decoration: none;">
            <?= htmlspecialchars($product->category) ?>
        </a>
        <span>/</span>
        <span style="color: #fff; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            <?= htmlspecialchars($product->title) ?>
        </span>
    </nav>

    <!-- Product Layout: Gallery + Info -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 40px; margin-bottom: 50px;">
        
        <!-- Gallery Column -->
        <div>
            <div style="background: #0c0f12; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden; height: 420px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <?php if (!empty($product->mainImageUrl)): ?>
                    <img id="main-product-img" src="<?= htmlspecialchars($product->mainImageUrl) ?>" alt="<?= htmlspecialchars($product->title) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80';" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <div style="font-size: 50px;">📦</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($product->images) && count($product->images) > 1): ?>
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px;">
                    <?php foreach ($product->images as $thumb): ?>
                        <div onclick="document.getElementById('main-product-img').src = '<?= htmlspecialchars($thumb) ?>';" style="width: 72px; height: 72px; background: #0c0f12; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; overflow: hidden; cursor: pointer; flex-shrink: 0;">
                            <img src="<?= htmlspecialchars($thumb) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Buy Box Column -->
        <div style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <?php $isAdult = str_contains($product->category, '+18') || stripos($product->category, 'sex shop') !== false; ?>
                <span style="background: <?= $isAdult ? 'rgba(239,68,68,0.2)' : 'rgba(255,71,71,0.15)' ?>; color: <?= $isAdult ? '#f87171' : '#ff6b6b' ?>; border: <?= $isAdult ? '1px solid rgba(239,68,68,0.4)' : 'none' ?>; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-bottom: 12px;">
                    <?= $isAdult ? '🔞 ' : '' ?><?= htmlspecialchars($product->category) ?>
                </span>

                <h1 style="font-size: clamp(22px, 3vw, 28px); font-weight: 700; color: #fff; margin: 0 0 14px 0; line-height: 1.3;">
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
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; margin-bottom: 24px;">
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

    <!-- Full Description & Persuasive Copy -->
    <div style="background: #171d22; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 32px; margin-bottom: 40px;">
        <h2 style="font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
            Descrição do Produto
        </h2>
        <div style="font-size: 15px; line-height: 1.8; color: #cbd5e1;">
            <?= $product->description ?>
        </div>
    </div>

</div>
