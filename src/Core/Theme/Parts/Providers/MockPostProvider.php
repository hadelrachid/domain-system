<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Providers;

use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;

/**
 * Provedor Mock de Artigos do Blog (Ideal para preview no Builder e testes).
 */
class MockPostProvider implements PostProviderInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $samplePosts = [
        [
            'id'             => 1,
            'title'          => 'Arquitetura SOLID no PHP Moderno: Princípios para Sistemas Resilientes',
            'slug'           => 'arquitetura-solid-no-php-moderno',
            'excerpt'        => 'Descubra como aplicar contratos e inversão de dependência para desacoplar seu ecossistema e evitar dívidas técnicas.',
            'featured_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '24 Out 2026',
            'author'         => 'Rachid',
            'category'       => 'Arquitetura',
            'url'            => '/blog/arquitetura-solid-no-php-moderno',
            'reading_time'   => '5 min de leitura',
        ],
        [
            'id'             => 2,
            'title'          => 'Design Tokens e SEO Semântico: Como Aumentar o Score no Google PageSpeed',
            'slug'           => 'design-tokens-e-seo-semantico',
            'excerpt'        => 'Aprenda técnicas de isolamento de escopo CSS e marcação Schema.org para alcançar pontuações máximas nos Core Web Vitals.',
            'featured_image' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '22 Out 2026',
            'author'         => 'Rachid',
            'category'       => 'Performance',
            'url'            => '/blog/design-tokens-e-seo-semantico',
            'reading_time'   => '4 min de leitura',
        ],
        [
            'id'             => 3,
            'title'          => 'Construindo Motores Visuais com Contratos Verificáveis sem Div Soup',
            'slug'           => 'construindo-motores-visuais-sem-div-soup',
            'excerpt'        => 'Uma análise profunda sobre como estruturar blocos semânticos e grades CSS sem poluir o DOM do navegador.',
            'featured_image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '20 Out 2026',
            'author'         => 'Equipe Domain',
            'category'       => 'Front-End',
            'url'            => '/blog/construindo-motores-visuais-sem-div-soup',
            'reading_time'   => '6 min de leitura',
        ],
        [
            'id'             => 4,
            'title'          => 'Segurança de Núcleo: Criptografia Autenticada AES-GCM e Disjuntores de Falha',
            'slug'           => 'seguranca-de-nucleo-criptografia-e-disjuntores',
            'excerpt'        => 'Como implementar o padrão Circuit Breaker para salvar o sistema de sobrecargas em plugins e runtime.',
            'featured_image' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '18 Out 2026',
            'author'         => 'Equipe Security',
            'category'       => 'Segurança',
            'url'            => '/blog/seguranca-de-nucleo-criptografia-e-disjuntores',
            'reading_time'   => '7 min de leitura',
        ],
        [
            'id'             => 5,
            'title'          => 'O Guia Definitivo de Micro-Interações e Vetores SVG Puros',
            'slug'           => 'guia-definitivo-micro-interacoes-svg',
            'excerpt'        => 'Substitua bibliotecas pesadas de ícones por caminhos vetoriais inline ultra-leves e animações fluidas a 60fps.',
            'featured_image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '15 Out 2026',
            'author'         => 'Rachid',
            'category'       => 'UI/UX',
            'url'            => '/blog/guia-definitivo-micro-interacoes-svg',
            'reading_time'   => '3 min de leitura',
        ],
        [
            'id'             => 6,
            'title'          => 'Domain-Driven Design (DDD) na Prática com PHP 8.2+',
            'slug'           => 'ddd-na-pratica-com-php',
            'excerpt'        => 'Entidades imutáveis, Value Objects tipados e agregados: como modelar domínios complexos com elegância.',
            'featured_image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&auto=format&fit=crop&q=80',
            'published_at'   => '10 Out 2026',
            'author'         => 'Rachid',
            'category'       => 'Engenharia',
            'url'            => '/blog/ddd-na-pratica-com-php',
            'reading_time'   => '8 min de leitura',
        ],
    ];

    public function __construct(?array $customPosts = null)
    {
        if ($customPosts !== null) {
            $this->samplePosts = $customPosts;
        }
    }

    public function getPosts(array $options = []): array
    {
        $posts = $this->samplePosts;

        $category = $options['category'] ?? null;
        if (!empty($category)) {
            $posts = array_filter($posts, static fn($p) => strcasecmp($p['category'] ?? '', (string)$category) === 0);
        }

        $limit = isset($options['limit']) ? (int)$options['limit'] : 6;
        if ($limit > 0) {
            $posts = array_slice($posts, 0, $limit);
        }

        return array_values($posts);
    }
}
