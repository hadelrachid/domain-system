<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Providers\MockPostProvider;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;

/**
 * Bloco de Grade de Artigos do Blog (PostCardsGridBlock) com Contratos SOLID e SEO Semântico.
 * 
 * Renderiza cards responsivos em CSS Grid (1 a 4 colunas), integração nativa com Design Tokens,
 * marcação Schema.org/BlogPosting para SEO de alta performance e lazy-loading de imagens.
 */
class PostCardsGridBlock implements BlockInterface, StylableElementInterface
{
    use StylableElementTrait;

    private PostProviderInterface $postProvider;

    public function __construct(?PostProviderInterface $postProvider = null)
    {
        $this->postProvider = $postProvider ?? new MockPostProvider();
    }

    public function getType(): string
    {
        return 'post_cards_grid';
    }

    public function getName(): string
    {
        return 'Grid de Artigos (Blog Cards)';
    }

    public function getIcon(): string
    {
        return '📰';
    }

    public function getCategory(): string
    {
        return 'content';
    }

    public function getAllowedPartTypes(): array
    {
        return [ThemePartInterface::TYPE_SECTION];
    }

    public function getSchema(): array
    {
        return [
            'columns' => [
                'type' => 'select',
                'label' => 'Colunas no Desktop',
                'options' => [
                    ['value' => '1', 'label' => '1 Coluna (Lista Cheia)'],
                    ['value' => '2', 'label' => '2 Colunas (50% / 50%)'],
                    ['value' => '3', 'label' => '3 Colunas (Padrão Blog)'],
                    ['value' => '4', 'label' => '4 Colunas (Grid Compacto)'],
                ],
                'default' => '3',
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'Limite de Artigos',
                'default' => 6,
                'min' => 1,
                'max' => 24,
            ],
            'card_style' => [
                'type' => 'select',
                'label' => 'Estilo dos Cards',
                'options' => [
                    ['value' => 'cyber_glass', 'label' => 'Cyber Glass (Glassmorphism & Glow)'],
                    ['value' => 'minimal', 'label' => 'Minimalista (Clean & Tipográfico)'],
                    ['value' => 'bordered', 'label' => 'Bordas Definidas (Contraste Token)'],
                    ['value' => 'flat', 'label' => 'Flat Surface (Preenchimento Sólido)'],
                ],
                'default' => 'cyber_glass',
            ],
            'hover_effect' => [
                'type' => 'select',
                'label' => 'Efeito Hover',
                'options' => [
                    ['value' => 'lift', 'label' => 'Elevação 3D (Lift suave)'],
                    ['value' => 'glow', 'label' => 'Brilho Neon (Glow Primário)'],
                    ['value' => 'border_accent', 'label' => 'Realce de Borda (Color Accent)'],
                    ['value' => 'none', 'label' => 'Sem Animação'],
                ],
                'default' => 'lift',
            ],
            'gap' => [
                'type' => 'text',
                'label' => 'Espaçamento (Gap)',
                'default' => '24px',
            ],
            'show_image' => [
                'type' => 'boolean',
                'label' => 'Exibir Imagem Destacada',
                'default' => true,
            ],
            'show_date' => [
                'type' => 'boolean',
                'label' => 'Exibir Data de Publicação',
                'default' => true,
            ],
            'show_author' => [
                'type' => 'boolean',
                'label' => 'Exibir Autor',
                'default' => true,
            ],
            'show_excerpt' => [
                'type' => 'boolean',
                'label' => 'Exibir Resumo (Excerto)',
                'default' => true,
            ],
            'show_read_more' => [
                'type' => 'boolean',
                'label' => 'Exibir Link "Ler Artigo"',
                'default' => true,
            ],
            'read_more_text' => [
                'type' => 'text',
                'label' => 'Texto do Botão / Link',
                'default' => 'Ler Artigo →',
            ],
        ];
    }

    public function render(array $data = [], array $context = []): string
    {
        $this->styleData = $data;

        $rawCols = (int)($data['columns'] ?? 3);
        $columns = in_array($rawCols, [1, 2, 3, 4], true) ? $rawCols : 3;

        $limit = max(1, (int)($data['limit'] ?? 6));
        $cardStyle = (string)($data['card_style'] ?? 'cyber_glass');
        $hoverEffect = (string)($data['hover_effect'] ?? 'lift');
        $gap = !empty($data['gap']) ? htmlspecialchars((string)$data['gap'], ENT_QUOTES, 'UTF-8') : '24px';

        $showImage = (bool)($data['show_image'] ?? true);
        $showDate = (bool)($data['show_date'] ?? true);
        $showAuthor = (bool)($data['show_author'] ?? true);
        $showExcerpt = (bool)($data['show_excerpt'] ?? true);
        $showReadMore = (bool)($data['show_read_more'] ?? true);
        $readMoreText = htmlspecialchars((string)($data['read_more_text'] ?? 'Ler Artigo →'), ENT_QUOTES, 'UTF-8');

        // Se o usuário passou posts manuais estáticos no schema, usa-os; caso contrário consulta o provedor
        if (isset($data['manual_posts']) && is_array($data['manual_posts']) && !empty($data['manual_posts'])) {
            $posts = array_slice($data['manual_posts'], 0, $limit);
        } else {
            $posts = $this->postProvider->getPosts(['limit' => $limit]);
        }

        if (empty($posts)) {
            return '<div class="ds-post-cards-empty" style="text-align: center; padding: 40px; color: var(--text-muted, #8b8c8d); font-family: var(--font-primary, sans-serif);">'
                 . '<p>Nenhum artigo encontrado para exibição no momento.</p>'
                 . '</div>';
        }

        $cardsHtml = [];
        foreach ($posts as $post) {
            $title = htmlspecialchars((string)($post['title'] ?? 'Sem Título'), ENT_QUOTES, 'UTF-8');
            $excerpt = htmlspecialchars((string)($post['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars((string)($post['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $author = htmlspecialchars((string)($post['author'] ?? 'Redação'), ENT_QUOTES, 'UTF-8');
            $publishedAt = htmlspecialchars((string)($post['published_at'] ?? ''), ENT_QUOTES, 'UTF-8');
            $category = htmlspecialchars((string)($post['category'] ?? 'Artigo'), ENT_QUOTES, 'UTF-8');
            $featuredImage = !empty($post['featured_image']) ? htmlspecialchars((string)$post['featured_image'], ENT_QUOTES, 'UTF-8') : '';
            $readingTime = !empty($post['reading_time']) ? htmlspecialchars((string)$post['reading_time'], ENT_QUOTES, 'UTF-8') : '';

            $imageHtml = '';
            if ($showImage) {
                if ($featuredImage !== '') {
                    $imageHtml = "<div class=\"ds-card-thumb\">\n"
                               . "    <a href=\"{$url}\" tabindex=\"-1\" aria-hidden=\"true\">\n"
                               . "        <img src=\"{$featuredImage}\" alt=\"{$title}\" loading=\"lazy\" itemprop=\"image\" class=\"ds-card-img\">\n"
                               . "    </a>\n"
                               . "    <span class=\"ds-card-badge\">{$category}</span>\n"
                               . "</div>\n";
                } else {
                    $imageHtml = "<div class=\"ds-card-thumb ds-card-thumb-placeholder\">\n"
                               . "    <span class=\"ds-card-badge\">{$category}</span>\n"
                               . "</div>\n";
                }
            }

            $metaParts = [];
            if ($showDate && $publishedAt !== '') {
                $metaParts[] = "<time itemprop=\"datePublished\" datetime=\"{$publishedAt}\">{$publishedAt}</time>";
            }
            if ($showAuthor && $author !== '') {
                $metaParts[] = "<span itemprop=\"author\" itemscope itemtype=\"https://schema.org/Person\"><span itemprop=\"name\">{$author}</span></span>";
            }
            if ($readingTime !== '') {
                $metaParts[] = "<span class=\"ds-card-reading-time\">{$readingTime}</span>";
            }
            $metaHtml = !empty($metaParts) ? "<div class=\"ds-card-meta\">\n    " . implode(" • \n    ", $metaParts) . "\n</div>\n" : '';

            $excerptHtml = ($showExcerpt && $excerpt !== '') ? "<p class=\"ds-card-excerpt\" itemprop=\"description\">{$excerpt}</p>\n" : '';
            $readMoreHtml = $showReadMore ? "<div class=\"ds-card-footer\">\n    <a href=\"{$url}\" class=\"ds-card-readmore\" itemprop=\"url\">{$readMoreText}</a>\n</div>\n" : '';

            $cardsHtml[] = <<<HTML
<article class="ds-post-card ds-style-{$cardStyle} ds-hover-{$hoverEffect}" itemscope itemtype="https://schema.org/BlogPosting">
    {$imageHtml}
    <div class="ds-card-content">
        {$metaHtml}
        <h3 class="ds-card-title" itemprop="headline">
            <a href="{$url}" itemprop="mainEntityOfPage">{$title}</a>
        </h3>
        {$excerptHtml}
        {$readMoreHtml}
    </div>
</article>
HTML;
        }

        $allCards = implode("\n", $cardsHtml);

        return <<<HTML
<div class="ds-post-cards-grid ds-cols-{$columns}" style="--ds-grid-cols: {$columns}; --ds-grid-gap: {$gap};">
{$allCards}
</div>
<style>
.ds-post-cards-grid {
    display: grid;
    grid-template-columns: repeat(var(--ds-grid-cols, 3), minmax(0, 1fr));
    gap: var(--ds-grid-gap, 24px);
    width: 100%;
    box-sizing: border-box;
}
@media (max-width: 992px) {
    .ds-post-cards-grid {
        grid-template-columns: repeat(min(var(--ds-grid-cols, 3), 2), minmax(0, 1fr)) !important;
    }
}
@media (max-width: 640px) {
    .ds-post-cards-grid {
        grid-template-columns: 1fr !important;
    }
}
.ds-post-card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    border-radius: 12px;
    box-sizing: border-box;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    font-family: var(--font-primary, 'Inter', sans-serif);
}
.ds-style-cyber_glass {
    background: var(--surface, rgba(31, 40, 51, 0.7));
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--border, rgba(69, 243, 255, 0.15));
}
.ds-style-minimal {
    background: transparent;
    border: none;
    border-bottom: 2px solid var(--border, rgba(255, 255, 255, 0.08));
    border-radius: 0;
    padding-bottom: 16px;
}
.ds-style-bordered {
    background: transparent;
    border: 1.5px solid var(--border, rgba(255, 255, 255, 0.18));
}
.ds-style-flat {
    background: var(--surface, #1f2833);
    border: none;
}
.ds-hover-lift:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
}
.ds-hover-glow:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 25px var(--color-primary, #38bdf8);
    border-color: var(--color-primary, #38bdf8);
}
.ds-hover-border_accent:hover {
    border-color: var(--color-primary, #38bdf8);
}
.ds-card-thumb {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: #0f172a;
}
.ds-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.ds-post-card:hover .ds-card-img {
    transform: scale(1.05);
}
.ds-card-thumb-placeholder {
    height: 80px;
    background: linear-gradient(135deg, rgba(56, 189, 248, 0.15), rgba(31, 40, 51, 0.8));
}
.ds-card-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--color-primary, #38bdf8);
    color: #0b0c10;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 4px;
    z-index: 2;
}
.ds-card-content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    padding: 20px;
}
.ds-card-meta {
    font-size: 0.8rem;
    color: var(--text-muted, #8b8c8d);
    margin-bottom: 10px;
    font-family: var(--font-secondary, monospace);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ds-card-title {
    font-size: 1.18rem;
    font-weight: 700;
    line-height: 1.4;
    margin: 0 0 10px 0;
    font-family: var(--font-primary, sans-serif);
}
.ds-card-title a {
    color: var(--color-primary, #38bdf8);
    text-decoration: none;
    transition: color 0.2s ease, text-shadow 0.2s ease;
}
.ds-card-title a:hover {
    color: #fff;
    text-shadow: 0 0 8px var(--color-primary, #38bdf8);
}
.ds-card-excerpt {
    font-size: 0.92rem;
    color: var(--text-main, #c5c6c7);
    line-height: 1.6;
    margin: 0 0 16px 0;
    flex-grow: 1;
}
.ds-card-footer {
    margin-top: auto;
    padding-top: 12px;
}
.ds-card-readmore {
    display: inline-flex;
    align-items: center;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--color-primary, #38bdf8);
    text-decoration: none;
    transition: transform 0.2s ease, color 0.2s ease;
}
.ds-card-readmore:hover {
    color: #fff;
    transform: translateX(4px);
}
</style>
HTML;
    }
}
