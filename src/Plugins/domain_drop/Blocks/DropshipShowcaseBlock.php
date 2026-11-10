<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;

/**
 * Bloco Semântico de Vitrine de Dropshipping para o Flex Builder / Storefront.
 *
 * Renderiza cards ultra responsivos em CSS Grid conectados ao catálogo de produtos
 * minerados do AliExpress, com checkout direto integrado ao DomainCommerce.
 */
class DropshipShowcaseBlock implements BlockInterface, StylableElementInterface
{
    use StylableElementTrait;

    private DropshipCatalogRepositoryInterface $catalogRepo;
    private ElementStyleRendererInterface $styleRenderer;
    private HtmlSanitizerInterface $htmlSanitizer;
    private ?PublicUrlResolverInterface $urlResolver;

    public function __construct(
        DropshipCatalogRepositoryInterface $catalogRepo,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null
    ) {
        $this->catalogRepo = $catalogRepo;
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->urlResolver = $urlResolver;
    }

    public function getType(): string
    {
        return 'dropship_showcase';
    }

    public function getName(): string
    {
        return 'Vitrine Dropshipping (AliExpress)';
    }

    public function getIcon(): string
    {
        return '🛍️';
    }

    public function getCategory(): string
    {
        return 'ecommerce';
    }

    public function getAllowedPartTypes(): array
    {
        return [ThemePartInterface::TYPE_SECTION, ThemePartInterface::TYPE_PAGE, ThemePartInterface::TYPE_FOOTER];
    }

    public function getSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Título da Vitrine',
                'default' => 'Tendências em Alta & Mais Vendidos',
            ],
            'subtitle' => [
                'type' => 'text',
                'label' => 'Subtítulo',
                'default' => 'Produtos virais selecionados com envio direto e garantia de satisfação.',
            ],
            'columns' => [
                'type' => 'select',
                'label' => 'Colunas no Desktop',
                'options' => [
                    ['value' => '1', 'label' => '1 Coluna'],
                    ['value' => '2', 'label' => '2 Colunas'],
                    ['value' => '3', 'label' => '3 Colunas'],
                    ['value' => '4', 'label' => '4 Colunas (Padrão E-commerce)'],
                ],
                'default' => '4',
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'Limite de Produtos',
                'default' => 8,
                'min' => 1,
                'max' => 24,
            ],
            'category' => [
                'type' => 'text',
                'label' => 'Filtrar por Categoria (opcional)',
                'default' => '',
            ],
            'button_text' => [
                'type' => 'text',
                'label' => 'Texto do Botão de Compra',
                'default' => 'Comprar Agora',
            ],
            'show_rating' => [
                'type' => 'boolean',
                'label' => 'Exibir Avaliação por Estrelas',
                'default' => true,
            ],
            'show_badge' => [
                'type' => 'boolean',
                'label' => 'Exibir Selo de Categoria',
                'default' => true,
            ],
        ];
    }

    public function render(array $data = [], array $context = []): string
    {
        $attributes = $data;
        $title = (string)($attributes['title'] ?? 'Tendências em Alta & Mais Vendidos');
        $subtitle = (string)($attributes['subtitle'] ?? 'Produtos virais selecionados com envio direto.');
        $columns = max(1, min(4, (int)($attributes['columns'] ?? 4)));
        $limit = max(1, min(24, (int)($attributes['limit'] ?? 8)));
        $category = trim((string)($attributes['category'] ?? ''));
        $btnText = (string)($attributes['button_text'] ?? 'Comprar Agora');
        $showRating = (bool)($attributes['show_rating'] ?? true);
        $showBadge = (bool)($attributes['show_badge'] ?? true);

        $criteria = ['status' => 'published'];
        if ($category !== '') {
            $criteria['category'] = $category;
        }

        $products = $this->catalogRepo->list($criteria, $limit, 0);

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if ($this->urlResolver !== null) {
            $resolvedBase = $this->urlResolver->resolve('/');
            if (!empty($resolvedBase)) {
                $baseUrl = rtrim($resolvedBase, '/');
            }
        }

        $cssClasses = 'domain-drop-showcase ds-block-dropship-showcase ' . $this->styleRenderer->getResponsiveVisibilityClasses($data);
        $customStyles = $this->styleRenderer->renderStyleString($data);
        $uniqueId = 'drop_showcase_' . uniqid();

        ob_start();
        ?>
        <section class="<?= htmlspecialchars($cssClasses) ?>" id="<?= $uniqueId ?>" style="padding: 40px 15px; width: 100%; box-sizing: border-box; <?= htmlspecialchars($customStyles) ?>">
            <?php if (!empty($title) || !empty($subtitle)): ?>
                <div style="text-align: center; max-width: 800px; margin: 0 auto 36px auto;">
                    <?php if (!empty($title)): ?>
                        <h2 style="font-size: clamp(22px, 3vw, 32px); font-weight: 700; color: #fff; margin: 0 0 10px 0; letter-spacing: -0.5px;">
                            <?= htmlspecialchars($title) ?>
                        </h2>
                    <?php endif; ?>
                    <?php if (!empty($subtitle)): ?>
                        <p style="font-size: 15px; color: var(--text-muted, #94a3b8); margin: 0; line-height: 1.5;">
                            <?= htmlspecialchars($subtitle) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12); border-radius: 12px; padding: 48px 20px; text-align: center; max-width: 600px; margin: 0 auto;">
                    <div style="font-size: 38px; margin-bottom: 12px;">🛍️</div>
                    <h3 style="color: #fff; margin: 0 0 6px 0; font-size: 18px;">Vitrine em Atualização</h3>
                    <p style="color: var(--text-muted, #94a3b8); font-size: 14px; margin: 0;">
                        Novos produtos virais estão sendo minerados pelo DomainDrop. Volte em instantes!
                    </p>
                </div>
            <?php else: ?>
                <style>
                    #<?= $uniqueId ?> .drop-grid {
                        display: grid;
                        grid-template-columns: repeat(<?= $columns ?>, 1fr);
                        gap: 22px;
                        max-width: 1300px;
                        margin: 0 auto;
                    }
                    @media (max-width: 1024px) {
                        #<?= $uniqueId ?> .drop-grid {
                            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
                        }
                    }
                    @media (max-width: 640px) {
                        #<?= $uniqueId ?> .drop-grid {
                            grid-template-columns: 1fr !important;
                        }
                    }
                    #<?= $uniqueId ?> .drop-card {
                        background: var(--bg-panel, #1d2327);
                        border: 1px solid var(--border, rgba(255,255,255,0.1));
                        border-radius: 12px;
                        overflow: hidden;
                        display: flex;
                        flex-direction: column;
                        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
                    }
                    #<?= $uniqueId ?> .drop-card:hover {
                        transform: translateY(-4px);
                        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.4);
                        border-color: rgba(255, 71, 71, 0.4);
                    }
                    #<?= $uniqueId ?> .drop-card img {
                        transition: transform 0.35s ease;
                    }
                    #<?= $uniqueId ?> .drop-card:hover img {
                        transform: scale(1.05);
                    }
                </style>

                <div class="drop-grid">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$p->id;
                        $costRef = $p->salePriceBrl * 1.35; // Preço original tachado simulado
                        ?>
                        <article class="drop-card">
                            <!-- Image Frame -->
                            <div style="position: relative; width: 100%; height: 230px; background: #0c0f12; overflow: hidden;">
                                <?php if (!empty($p->mainImageUrl)): ?>
                                    <img src="<?= htmlspecialchars($p->mainImageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted, #94a3b8); font-size: 32px;">
                                        📦
                                    </div>
                                <?php endif; ?>

                                <?php if ($showBadge && !empty($p->category)): ?>
                                    <span style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?= htmlspecialchars($p->category) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($showRating && $p->rating > 0): ?>
                                    <span style="position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); color: #fbbf24; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        ★ <?= number_format($p->rating, 1) ?>
                                    </span>
                                <?php endif; ?>

                                <span style="position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                                    PROMO
                                </span>
                            </div>

                            <!-- Content -->
                            <div style="padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #fff; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= htmlspecialchars($p->title) ?>">
                                        <?= htmlspecialchars($p->title) ?>
                                    </h3>

                                    <!-- Pricing Box -->
                                    <div style="margin-bottom: 16px;">
                                        <div style="font-size: 12px; color: var(--text-muted, #94a3b8); text-decoration: line-through; margin-bottom: 2px;">
                                            R$ <?= number_format($costRef, 2, ',', '.') ?>
                                        </div>
                                        <div style="font-size: 20px; font-weight: 700; color: #5cd67d; font-family: monospace;">
                                            R$ <?= number_format($p->salePriceBrl, 2, ',', '.') ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted, #94a3b8); margin-top: 2px;">
                                            em até 12x no cartão ou Pix
                                        </div>
                                    </div>
                                </div>

                                <!-- CTA Buy Button -->
                                <a href="<?= htmlspecialchars($checkoutUrl) ?>" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); color: #fff; text-decoration: none; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(255, 71, 71, 0.3); transition: filter 0.2s ease;">
                                    <span><?= htmlspecialchars($btnText) ?></span>
                                    <span>➔</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean() ?: '';
    }
}
