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
<div class="domain-drop-store" style="max-width: 1360px; margin: 0 auto; padding: 30px 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Store Hero Banner -->
    <div style="background: linear-gradient(135deg, rgba(212,175,55,0.12) 0%, rgba(20,24,30,0.85) 100%); border: 1px solid rgba(212,175,55,0.3); border-radius: 16px; padding: 45px 30px; margin-bottom: 35px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <!-- Lâmpada de Aladim Dourada Flutuante -->
        <div style="display: inline-block; margin-bottom: 12px; filter: drop-shadow(0 0 20px rgba(212,175,55,0.6));">
            <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="RachidD Lâmpada Mágica" style="height: 64px; width: auto; display: block; margin: 0 auto;">
        </div>
        <br>
        <span style="background: rgba(212,175,55,0.15); color: #F0D060; border: 1px solid rgba(212,175,55,0.3); padding: 5px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; display: inline-block; margin-bottom: 14px;">
            ✨ O Gênio das Ofertas • Seleção Internacional Curada
        </span>
        <h1 style="font-size: clamp(26px, 4vw, 42px); font-weight: 800; color: #fff; margin: 0 0 12px 0; letter-spacing: -0.5px;">
            Loja Oficial <span style="background: linear-gradient(135deg, #FFE082 0%, #D4AF37 50%, #B8962E 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">RachidD.com</span>
        </h1>
        <p style="font-size: 16px; color: #cbd5e1; max-width: 680px; margin: 0 auto 26px auto; line-height: 1.6;">
            Tesouros minerados e tendências mundiais com frete internacional rastreado e pagamento 100% protegido.
        </p>

        <!-- Search Bar -->
        <form action="<?= $baseUrl ?>/loja" method="GET" style="max-width: 580px; margin: 0 auto; display: flex; gap: 8px;">
            <?php if (!empty($selectedCategory)): ?>
                <input type="hidden" name="cat" value="<?= htmlspecialchars($selectedCategory) ?>">
            <?php endif; ?>
            <div style="position: relative; flex: 1;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="O que você deseja encontrar hoje no RachidD.com?..." style="width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(212,175,55,0.35); color: #fff; padding: 13px 18px; border-radius: 8px; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s ease;">
            </div>
            <button type="submit" style="background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); border: none; color: #0b0f17; padding: 0 26px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                Buscar
            </button>
        </form>
    </div>

    <!-- Category Pills Navigation -->
    <?php if (!empty($categories)): ?>
        <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 30px; scrollbar-width: thin;">
            <a href="<?= $baseUrl ?>/loja<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" style="padding: 9px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: all 0.2s ease; <?= empty($selectedCategory) ? 'background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; box-shadow: 0 4px 12px rgba(212,175,55,0.3);' : 'background: rgba(255,255,255,0.06); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1);' ?>">
                Todas as Categorias
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php
                $isActive = ($selectedCategory === $cat);
                $query = ['cat' => $cat];
                if (!empty($search)) $query['q'] = $search;
                $catUrl = $baseUrl . '/loja?' . http_build_query($query);
                ?>
                <a href="<?= htmlspecialchars($catUrl) ?>" style="padding: 9px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: all 0.2s ease; <?= $isActive ? 'background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; box-shadow: 0 4px 12px rgba(212,175,55,0.3);' : 'background: rgba(255,255,255,0.06); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1);' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Catalog Status & Filter Details -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 14px; color: #94a3b8;">
        <div>
            Exibindo <strong style="color: #fff;"><?= count($products) ?></strong> de <?= $totalCount ?> produtos
            <?php if (!empty($selectedCategory)): ?>
                em <span style="color: #ff6b6b; font-weight: 600;">"<?= htmlspecialchars($selectedCategory) ?>"</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($selectedCategory) || !empty($search)): ?>
            <a href="<?= $baseUrl ?>/loja" style="color: #94a3b8; text-decoration: none; font-size: 13px;">✕ Limpar Filtros</a>
        <?php endif; ?>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12); border-radius: 12px; padding: 60px 20px; text-align: center;">
            <div style="font-size: 42px; margin-bottom: 12px;">🛍️</div>
            <h3 style="color: #fff; font-size: 20px; margin: 0 0 8px 0;">Nenhum produto encontrado</h3>
            <p style="color: #94a3b8; font-size: 14px; margin: 0 0 20px 0;">
                Tente selecionar outra categoria ou limpar a pesquisa para ver mais produtos.
            </p>
            <a href="<?= $baseUrl ?>/loja" style="display: inline-block; background: #ff4747; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">
                Ver Todos os Produtos
            </a>
        </div>
    <?php else: ?>
        <style>
            .store-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
                gap: 22px;
            }
            .store-card {
                background: #171d22;
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            }
            .store-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 16px 32px rgba(0,0,0,0.6);
                border-color: rgba(212,175,55,0.45);
            }
            .store-card img {
                transition: transform 0.35s ease;
            }
            .store-card:hover img {
                transform: scale(1.05);
            }
        </style>

        <div class="store-grid">
            <?php foreach ($products as $p): ?>
                <?php
                $productUrl = !empty($p->slug) ? $baseUrl . '/produto/' . urlencode($p->slug) : $baseUrl . '/produto/' . (int)$p->id;
                $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$p->id;
                $refPrice = $p->salePriceBrl * 1.35;
                ?>
                <article class="store-card">
                    <!-- Image Box -->
                    <div style="position: relative; width: 100%; height: 240px; background: #0c0f12; overflow: hidden;">
                        <a href="<?= htmlspecialchars($productUrl) ?>">
                            <?php if (!empty($p->mainImageUrl)): ?>
                                <img src="<?= htmlspecialchars($p->mainImageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80';" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 36px;">📦</div>
                            <?php endif; ?>
                        </a>

                        <?php if (!empty($p->category)): ?>
                            <span style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                <?= htmlspecialchars($p->category) ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($p->rating > 0): ?>
                            <span style="position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.8); color: #fbbf24; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                ★ <?= number_format($p->rating, 1) ?>
                            </span>
                        <?php endif; ?>

                        <span style="position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;">
                            PROMO
                        </span>
                    </div>

                    <!-- Details -->
                    <div style="padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600; line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <a href="<?= htmlspecialchars($productUrl) ?>" style="color: #fff; text-decoration: none;" title="<?= htmlspecialchars($p->title) ?>">
                                    <?= htmlspecialchars($p->title) ?>
                                </a>
                            </h3>

                            <div style="margin-bottom: 14px;">
                                <div style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">
                                    R$ <?= number_format($refPrice, 2, ',', '.') ?>
                                </div>
                                <div style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                    R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                    em até 12x ou Pix com desconto
                                </div>
                            </div>
                        </div>

                        <!-- Buy Action -->
                        <div style="display: flex; gap: 8px;">
                            <a href="<?= htmlspecialchars($checkoutUrl) ?>" style="flex: 1; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; text-decoration: none; padding: 11px; border-radius: 6px; font-size: 13px; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35); transition: all 0.2s ease;">
                                Comprar Agora ➔
                            </a>
                            <a href="<?= htmlspecialchars($productUrl) ?>" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; text-decoration: none; padding: 11px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: center;" title="Ver Detalhes">
                                ➔
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
