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
        Exibindo <strong style="color: #5cd67d; font-size: 15px;"><?= empty($products) ? 0 : (($page - 1) * 48 + 1) ?>-<?= min(($page - 1) * 48 + count($products), $totalCount) ?></strong> de <strong style="color: #fff;"><?= $totalCount ?></strong> produtos
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <span style="color: #45f3ff; margin-left: 6px;">(Página <?= $page ?> de <?= $totalPages ?>)</span>
        <?php endif; ?>
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
                        <?php
                        $pStarsCount = (int)max(1, min(5, round((float)$p->rating)));
                        $pStarsEmoji = str_repeat('⭐', $pStarsCount);
                        ?>
                        <span style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.85); border: 1px solid rgba(251,191,36,0.3); color: #fbbf24; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; backdrop-filter: blur(4px);">
                            <span><?= $pStarsEmoji ?></span>
                            <span style="color: #fff; font-family: monospace;"><?= number_format($p->rating, 1) ?></span>
                        </span>
                    <?php endif; ?>

                    <span style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; padding: 3px 7px; border-radius: 4px; font-size: 10px; font-weight: 800; letter-spacing: 0.5px;">
                        PROMO
                    </span>
                </div>

                <!-- Details -->
                <div class="store-card-body" style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <a href="<?= htmlspecialchars($productUrl) ?>" style="color: #fff; text-decoration: none;" title="<?= htmlspecialchars($p->title) ?>">
                                <?= htmlspecialchars($p->title) ?>
                            </a>
                        </h3>

                        <?php
                        $cardRating = (float)($p->rating ?? 4.8);
                        $starCount = (int)max(1, min(5, round($cardRating > 0 ? $cardRating : 4.8)));
                        $starsEmoji = str_repeat('⭐', $starCount);
                        $ordersCount = (int)($p->ordersCount ?? 0);
                        $salesInformed = (string)($p->attributes['Vendas_Informadas'] ?? '');
                        $reviewsCount = (int)($p->attributes['reviews_count'] ?? 0);
                        ?>
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; font-size: 11.5px;">
                            <span style="letter-spacing: 1px; font-size: 12px;"><?= $starsEmoji ?></span>
                            <strong style="color: #fbbf24; font-family: monospace; font-size: 12px;"><?= number_format($cardRating > 0 ? $cardRating : 4.8, 1) ?></strong>
                            <?php if ($ordersCount > 0 && $salesInformed !== 'nao'): ?>
                                <span style="color: #94a3b8; font-size: 11px;">• <strong style="color: #e2e8f0;"><?= number_format($ordersCount, 0, ',', '.') ?></strong> vendidos</span>
                            <?php elseif ($reviewsCount > 0): ?>
                                <span style="color: #94a3b8; font-size: 11px;">• <strong style="color: #e2e8f0;"><?= $reviewsCount ?></strong> avaliações</span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 11px;">• <strong style="color: #e2e8f0;">Destaque</strong> em vendas</span>
                            <?php endif; ?>
                        </div>

                        <?php $isNational = (!empty($p->attributes['origem_brasil']) || (isset($p->attributes['ship_from']) && $p->attributes['ship_from'] === 'BR')); ?>
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; color: #94a3b8; text-decoration: line-through;">
                                R$ <?= number_format($refPrice, 2, ',', '.') ?>
                            </div>
                            <div class="store-card-price" style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                            </div>
                            <div style="font-size: 10px; color: <?= $isNational ? '#4ade80' : '#94a3b8' ?>; margin-top: 2px; font-weight: <?= $isNational ? '600' : 'normal' ?>; display: flex; align-items: center; flex-wrap: wrap;">
                                <?= $isNational ? (\DomainSystem\Plugins\domain_drop\Helpers\FlagHelper::br(15, 10) . ' Estoque no Brasil • Envio 3 a 7 dias') : 'em até 12x ou Pix com desconto' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons: Ver Detalhes e Comprar Explícitos -->
                    <?php
                    $jsProdId = htmlspecialchars((string)json_encode((string)($p->aliexpressId ?: (string)$p->id)), ENT_QUOTES, 'UTF-8');
                    $jsProdTitle = htmlspecialchars((string)json_encode((string)$p->title), ENT_QUOTES, 'UTF-8');
                    $jsProdCat = htmlspecialchars((string)json_encode((string)$p->category), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="store-card-actions" style="display: flex; gap: 6px;">
                        <a href="<?= htmlspecialchars($productUrl) ?>" onclick="if(window.trackDropAction) window.trackDropAction(<?= $jsProdId ?>, <?= $jsProdTitle ?>, <?= $jsProdCat ?>, 'view_details');" style="flex: 1; background: rgba(255,255,255,0.06); border: 1.5px solid rgba(212,175,55,0.4); color: #F0D060; text-decoration: none; padding: 0 6px; height: 36px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 4px; transition: all 0.2s ease; box-sizing: border-box; white-space: nowrap; line-height: 1.2;" onmouseover="this.style.background='rgba(212,175,55,0.18)'; this.style.borderColor='#F0D060';" onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='rgba(212,175,55,0.4)';">
                            <span>🔍</span>
                            <span>Ver Detalhes</span>
                        </a>
                        <a href="<?= htmlspecialchars($checkoutUrl) ?>" onclick="if(window.trackDropAction) window.trackDropAction(<?= $jsProdId ?>, <?= $jsProdTitle ?>, <?= $jsProdCat ?>, 'buy_click');" style="flex: 1; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; text-decoration: none; padding: 0 6px; height: 36px; border-radius: 6px; font-size: 11.5px; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: 0.4px; box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35); transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; white-space: nowrap; line-height: 1.2;">
                            Comprar ➔
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginação SEO da Vitrine Pública (48 itens por página) -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <?php
        $frontQuery = $_GET;
        unset($frontQuery['page'], $frontQuery['ajax']);
        $stageRoute = isset($pageBaseRoute) && $pageBaseRoute !== '' ? (string)$pageBaseRoute : '/loja';
        $buildFrontPageUrl = function(int $p) use ($baseUrl, $frontQuery, $stageRoute): string {
            $params = array_merge($frontQuery, ['page' => $p]);
            return $baseUrl . $stageRoute . '?' . http_build_query($params);
        };
        ?>
        <nav aria-label="Navegação da Loja" style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px 20px; margin-top: 32px; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 13px; color: #94a3b8;">
                Página <strong style="color: #fff;"><?= $page ?></strong> de <strong style="color: #fff;"><?= $totalPages ?></strong> &bull; Total: <strong style="color: #F0D060;"><?= $totalCount ?></strong> produtos
            </div>
            
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars($buildFrontPageUrl(1)) ?>" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;" title="Primeira Página">
                        &laquo; Primeira
                    </a>
                    <a href="<?= htmlspecialchars($buildFrontPageUrl($page - 1)) ?>" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;">
                        &lsaquo; Anterior
                    </a>
                <?php else: ?>
                    <span style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); color: #555; padding: 7px 12px; border-radius: 6px; font-size: 12px; cursor: not-allowed;">
                        &lsaquo; Anterior
                    </span>
                <?php endif; ?>

                <?php
                $frontStartP = max(1, $page - 3);
                $frontEndP = min($totalPages, $page + 3);
                if ($frontStartP > 1) {
                    echo '<span style="color: #666; padding: 0 4px;">...</span>';
                }
                for ($p = $frontStartP; $p <= $frontEndP; $p++):
                ?>
                    <?php if ($p === $page): ?>
                        <span style="background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 800; box-shadow: 0 2px 10px rgba(212,175,55,0.35);">
                            <?= $p ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($buildFrontPageUrl($p)) ?>" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;">
                            <?= $p ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($frontEndP < $totalPages): ?>
                    <span style="color: #666; padding: 0 4px;">...</span>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars($buildFrontPageUrl($page + 1)) ?>" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;">
                        Próxima &rsaquo;
                    </a>
                    <a href="<?= htmlspecialchars($buildFrontPageUrl($totalPages)) ?>" onclick="event.preventDefault(); filterCatalogDynamically(this.href);" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;" title="Última Página">
                        Última &raquo;
                    </a>
                <?php else: ?>
                    <span style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); color: #555; padding: 7px 12px; border-radius: 6px; font-size: 12px; cursor: not-allowed;">
                        Próxima &rsaquo;
                    </span>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- Age Verification Modal for Adult (+18) / Sex Shop Categories -->
<?php
$isAdultCategory = ($dedicatedSection ?? '') === 'sexshop'
    || (!empty($selectedCategory) && (
        str_contains($selectedCategory, '+18')
        || stripos($selectedCategory, 'sex shop') !== false
        || stripos($selectedCategory, 'sexshop') !== false
        || stripos($selectedCategory, 'uso especial') !== false
        || stripos($selectedCategory, 'novidades') !== false
    ));
?>
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
