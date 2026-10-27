<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Blocks\PostCardsGridBlock;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Providers\MockPostProvider;
use DomainSystem\Core\Theme\Parts\Providers\PagesPostProvider;
use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use PHPUnit\Framework\TestCase;

class PostCardsGridBlockTest extends TestCase
{
    public function testBlockMetadataAndContracts(): void
    {
        $block = new PostCardsGridBlock();

        $this->assertInstanceOf(BlockInterface::class, $block);
        $this->assertInstanceOf(StylableElementInterface::class, $block);
        $this->assertSame('post_cards_grid', $block->getType());
        $this->assertSame('Grid de Artigos (Blog Cards)', $block->getName());
        $this->assertSame('📰', $block->getIcon());
        $this->assertSame('content', $block->getCategory());

        $schema = $block->getSchema();
        $this->assertArrayHasKey('columns', $schema);
        $this->assertArrayHasKey('limit', $schema);
        $this->assertArrayHasKey('card_style', $schema);
        $this->assertArrayHasKey('hover_effect', $schema);
        $this->assertArrayHasKey('gap', $schema);
        $this->assertArrayHasKey('show_image', $schema);
        $this->assertArrayHasKey('show_date', $schema);
        $this->assertArrayHasKey('show_author', $schema);
        $this->assertArrayHasKey('show_excerpt', $schema);
        $this->assertArrayHasKey('show_read_more', $schema);
    }

    public function testRenderWithDefaultMockProvider(): void
    {
        $block = new PostCardsGridBlock();

        $html = $block->render([
            'columns' => 3,
            'limit' => 3,
            'card_style' => 'cyber_glass',
            'hover_effect' => 'lift',
            'gap' => '20px',
        ]);

        $this->assertStringContainsString('class="ds-post-cards-grid ds-cols-3"', $html);
        $this->assertStringContainsString('--ds-grid-cols: 3', $html);
        $this->assertStringContainsString('--ds-grid-gap: 20px', $html);

        // Verificação de SEO Schema.org semântico
        $this->assertStringContainsString('itemscope itemtype="https://schema.org/BlogPosting"', $html);
        $this->assertStringContainsString('itemprop="headline"', $html);
        $this->assertStringContainsString('itemprop="datePublished"', $html);
        $this->assertStringContainsString('itemprop="description"', $html);
        $this->assertStringContainsString('itemprop="image"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);

        // Verificação de classes de estilo e hover
        $this->assertStringContainsString('ds-style-cyber_glass', $html);
        $this->assertStringContainsString('ds-hover-lift', $html);

        // Artigo mock padrão
        $this->assertStringContainsString('Arquitetura SOLID no PHP Moderno', $html);
    }

    public function testRenderWithCustomProviderAndLimit(): void
    {
        $customProvider = new class implements PostProviderInterface {
            public function getPosts(array $options = []): array
            {
                $limit = $options['limit'] ?? 6;
                $all = [
                    [
                        'id' => 101,
                        'title' => 'Microsserviços em PHP 8.2',
                        'slug' => 'microsservicos-php',
                        'excerpt' => 'Construindo microsserviços rápidos e desacoplados.',
                        'published_at' => '26 Out 2026',
                        'author' => 'Arquiteto',
                        'category' => 'Cloud',
                        'url' => '/blog/microsservicos-php',
                    ],
                    [
                        'id' => 102,
                        'title' => 'Event Sourcing Avançado',
                        'slug' => 'event-sourcing',
                        'excerpt' => 'Auditabilidade e rastreabilidade total.',
                        'published_at' => '25 Out 2026',
                        'author' => 'Arquiteto',
                        'category' => 'Arquitetura',
                        'url' => '/blog/event-sourcing',
                    ],
                ];
                return array_slice($all, 0, $limit);
            }
        };

        $block = new PostCardsGridBlock($customProvider);
        $html = $block->render([
            'columns' => 2,
            'limit' => 2,
            'card_style' => 'minimal',
            'hover_effect' => 'glow',
        ]);

        $this->assertStringContainsString('ds-cols-2', $html);
        $this->assertStringContainsString('Microsserviços em PHP 8.2', $html);
        $this->assertStringContainsString('Event Sourcing Avançado', $html);
        $this->assertStringContainsString('ds-style-minimal', $html);
        $this->assertStringContainsString('ds-hover-glow', $html);
    }

    public function testRenderWithManualPosts(): void
    {
        $block = new PostCardsGridBlock();

        $manualPosts = [
            [
                'id' => 'm1',
                'title' => 'Post Manual Curado',
                'slug' => 'post-manual',
                'excerpt' => 'Conteúdo específico curado no editor.',
                'author' => 'Curador',
                'published_at' => 'Hoje',
                'url' => '/artigo-curado',
            ],
        ];

        $html = $block->render([
            'manual_posts' => $manualPosts,
            'show_read_more' => true,
            'read_more_text' => 'Acessar Artigo Especial →',
        ]);

        $this->assertStringContainsString('Post Manual Curado', $html);
        $this->assertStringContainsString('Acessar Artigo Especial →', $html);
        $this->assertStringContainsString('Conteúdo específico curado no editor.', $html);
    }

    public function testRenderEmptyState(): void
    {
        $emptyProvider = new class implements PostProviderInterface {
            public function getPosts(array $options = []): array
            {
                return [];
            }
        };

        $block = new PostCardsGridBlock($emptyProvider);
        $html = $block->render();

        $this->assertStringContainsString('ds-post-cards-empty', $html);
        $this->assertStringContainsString('Nenhum artigo encontrado', $html);
    }

    public function testCardStylesAndHoverVariants(): void
    {
        $block = new PostCardsGridBlock();

        $htmlBordered = $block->render(['card_style' => 'bordered', 'hover_effect' => 'border_accent']);
        $this->assertStringContainsString('ds-style-bordered', $htmlBordered);
        $this->assertStringContainsString('ds-hover-border_accent', $htmlBordered);

        $htmlFlat = $block->render(['card_style' => 'flat', 'hover_effect' => 'none']);
        $this->assertStringContainsString('ds-style-flat', $htmlFlat);
        $this->assertStringContainsString('ds-hover-none', $htmlFlat);
    }

    public function testPagesPostProviderAdapter(): void
    {
        $mockRepo = $this->createMock(PageRepositoryInterface::class);
        $mockRepo->method('getAll')->willReturn([
            [
                'id' => 1,
                'title' => 'Artigo vindo do Banco de Dados',
                'slug' => 'artigo-do-bd',
                'content' => '<p>Este é o conteúdo com <strong>HTML</strong> que deve ser limpo e virar excerto.</p>',
                'created_at' => '2026-10-26 12:00:00',
            ],
        ]);

        $adapter = new PagesPostProvider($mockRepo);
        $posts = $adapter->getPosts(['limit' => 1]);

        $this->assertCount(1, $posts);
        $this->assertSame('Artigo vindo do Banco de Dados', $posts[0]['title']);
        $this->assertSame('artigo-do-bd', $posts[0]['slug']);
        $this->assertStringNotContainsString('<p>', $posts[0]['excerpt']);
        $this->assertStringNotContainsString('<strong>', $posts[0]['excerpt']);
        $this->assertSame('26/10/2026', $posts[0]['published_at']);
    }

    public function testDefaultBlockRegistryFactoryIncludesPostCardsGrid(): void
    {
        $registry = DefaultBlockRegistryFactory::create();
        $block = $registry->get('post_cards_grid');

        $this->assertNotNull($block);
        $this->assertInstanceOf(PostCardsGridBlock::class, $block);
    }
}
