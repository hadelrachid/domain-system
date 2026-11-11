<?php
/**
 * Palco Dinâmico de Produtos da Vitrine (/loja)
 *
 * @var array<int, \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto> $products
 * @var int $totalCount
 * @var string $selectedCategory
 * @var string $search
 * @var string $baseUrl
 */
?>
<!-- Informações e Contagem de Produtos do Palco -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 13px; color: #94a3b8; flex-wrap: wrap; gap: 8px;">
    <div>
        Exibindo <strong style="color: #5cd67d; font-size: 15px;"><?= count($products) ?></strong> de <?= $totalCount ?> produtos
        <?php if (!empty($selectedCategory)): ?>
            na categoria <span style="color: #F0D060; font-weight: 700;">"<?= htmlspecialchars($selectedCategory) ?>"</span>
        <?php endif; ?>
        <?php if (!empty($search)): ?>
            para a busca <span style="color: #60a5fa; font-weight: 700;">"<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
    </div>
    <?php if (!empty($selectedCategory) || !empty($search) || !empty($_GET['sort'])): ?>
        <a href="<?= $baseUrl ?>/loja" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="color: #f87171; text-decoration: none; font-size: 12px; font-weight: 600;">✕ Limpar Filtros</a>
    <?php endif; ?>
</div>

<!-- Grade de Produtos Dinâmica -->
<?php if (empty($products)): ?>
    <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12); border-radius: 12px; padding: 50px 20px; text-align: center; animation: fadeInStage 0.25s ease;">
        <div style="font-size: 40px; margin-bottom: 12px;">🛍️</div>
        <h3 style="color: #fff; font-size: 19px; margin: 0 0 8px 0;">Nenhum produto encontrado</h3>
        <p style="color: #94a3b8; font-size: 14px; margin: 0 0 20px 0;">
            Tente selecionar outra categoria ou limpar a pesquisa para ver mais produtos.
        </p>
        <a href="<?= $baseUrl ?>/loja" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" class="gold-button" style="padding: 10px 24px; font-size: 13px;">
            Ver Todos os Produtos
        </a>
    </div>
<?php else: ?>
    <div class="store-grid" style="animation: fadeInStage 0.25s ease;">
        <?php foreach ($products as $p): ?>
            <?php
            $productUrl = !empty($p->slug) ? $baseUrl . '/produto/' . urlencode($p->slug) : $baseUrl . '/produto/' . (int)$p->id;
            $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$p->id;
            $refPrice = $p->salePriceBrl * 1.35;
            ?>
            <article class="store-card">
                <!-- Image Box -->
                <div class="store-card-img-box" style="position: relative; width: 100%; height: 230px; background: #0c0f12; overflow: hidden;">
                    <a href="<?= htmlspecialchars($productUrl) ?>">
                        <?php if (!empty($p->mainImageUrl)): ?>
                            <img src="<?= htmlspecialchars($p->mainImageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80';" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 36px;">📦</div>
                        <?php endif; ?>
                    </a>

                    <?php if (!empty($p->category)): ?>
                        <?php $isAdult = str_contains($p->category, '+18') || stripos($p->category, 'sex shop') !== false; ?>
                        <span style="position: absolute; top: 10px; left: 10px; background: <?= $isAdult ? 'rgba(185,28,28,0.92)' : 'rgba(0,0,0,0.75)' ?>; backdrop-filter: blur(4px); color: #fff; padding: 3px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; border: <?= $isAdult ? '1px solid rgba(239,68,68,0.6)' : 'none' ?>;">
                            <?= $isAdult ? '🔞 ' : '' ?><?= htmlspecialchars($p->category) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($p->rating > 0): ?>
                        <span style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.8); color: #fbbf24; padding: 3px 7px; border-radius: 5px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            ★ <?= number_format($p->rating, 1) ?>
                        </span>
                    <?php endif; ?>

                    <span style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; padding: 3px 7px; border-radius: 4px; font-size: 10px; font-weight: 800; letter-spacing: 0.5px;">
                        PROMO
                    </span>
                </div>

                <!-- Details -->
                <div class="store-card-body" style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <a href="<?= htmlspecialchars($productUrl) ?>" style="color: #fff; text-decoration: none;" title="<?= htmlspecialchars($p->title) ?>">
                                <?= htmlspecialchars($p->title) ?>
                            </a>
                        </h3>

                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; color: #94a3b8; text-decoration: line-through;">
                                R$ <?= number_format($refPrice, 2, ',', '.') ?>
                            </div>
                            <div class="store-card-price" style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                            </div>
                            <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                                em até 12x ou Pix com desconto
                            </div>
                        </div>
                    </div>

                    <!-- Buy Action -->
                    <div style="display: flex; gap: 6px;">
                        <a href="<?= htmlspecialchars($checkoutUrl) ?>" style="flex: 1; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; text-decoration: none; padding: 10px; border-radius: 6px; font-size: 12px; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35); transition: all 0.2s ease;">
                            Comprar ➔
                        </a>
                        <a href="<?= htmlspecialchars($productUrl) ?>" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; text-decoration: none; padding: 10px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center;" title="Ver Detalhes">
                            ➔
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Age Verification Modal for Adult (+18) / Sex Shop Categories -->
<?php $isAdultCategory = !empty($selectedCategory) && (str_contains($selectedCategory, '+18') || stripos($selectedCategory, 'sex shop') !== false); ?>
<?php if ($isAdultCategory): ?>
    <div id="ageGateModal" style="display: none; position: fixed; inset: 0; background: rgba(5,7,10,0.92); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #171d22; border: 1px solid rgba(239,68,68,0.5); border-radius: 14px; max-width: 480px; width: 100%; padding: 32px 28px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.8); animation: fadeIn 0.3s ease;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(239,68,68,0.15); border: 2px solid #ef4444; color: #f87171; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; margin: 0 auto 18px auto;">
                🔞
            </div>
            <h2 style="color: #fff; font-size: 22px; font-weight: 800; margin: 0 0 10px 0;">Conteúdo Adulto (+18)</h2>
            <p style="color: #cbd5e1; font-size: 14px; line-height: 1.6; margin: 0 0 24px 0;">
                Esta seção contém produtos íntimos e bem-estar destinados <strong>exclusivamente a maiores de 18 anos</strong>. Ao prosseguir, você declara sob as penas da lei ter atingido a maioridade legal.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="<?= $baseUrl ?>/loja" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="flex: 1; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #cbd5e1; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;">
                    Não, sair
                </a>
                <button type="button" id="btnConfirmAge18" style="flex: 1.4; background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); border: none; color: #fff; padding: 12px 18px; border-radius: 8px; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(239,68,68,0.4);">
                    Sim, tenho 18+ anos
                </button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var modal = document.getElementById('ageGateModal');
        var btnConfirm = document.getElementById('btnConfirmAge18');
        if (modal && !localStorage.getItem('age_verified_18')) {
            modal.style.display = 'flex';
            if (btnConfirm) {
                btnConfirm.addEventListener('click', function() {
                    localStorage.setItem('age_verified_18', 'true');
                    modal.style.display = 'none';
                });
            }
        }
    })();
    </script>
<?php endif; ?>
