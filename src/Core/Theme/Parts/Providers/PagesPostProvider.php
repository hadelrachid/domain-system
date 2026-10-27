<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Providers;

use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use Throwable;

/**
 * Adapter desacoplado (Ring 0 Pure) que conecta qualquer repositório de Páginas/Artigos
 * (que implemente getAll(): array) ao contrato PostProviderInterface sem acoplar a Plugins (SOLID - DIP).
 */
class PagesPostProvider implements PostProviderInterface
{
    private ?object $pageRepo;
    private PostProviderInterface $fallbackProvider;
    private PublicUrlResolverInterface $urlResolver;

    public function __construct(
        ?object $pageRepo = null,
        ?PostProviderInterface $fallbackProvider = null,
        ?PublicUrlResolverInterface $urlResolver = null
    ) {
        $this->pageRepo = $pageRepo;
        $this->fallbackProvider = $fallbackProvider ?? new MockPostProvider();
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
    }

    public function getPosts(array $options = []): array
    {
        if ($this->pageRepo === null || !method_exists($this->pageRepo, 'getAll')) {
            return $this->fallbackProvider->getPosts($options);
        }

        try {
            $pages = $this->pageRepo->getAll();
        } catch (Throwable) {
            return $this->fallbackProvider->getPosts($options);
        }

        if (!is_array($pages) || empty($pages)) {
            return $this->fallbackProvider->getPosts($options);
        }

        $posts = [];
        $limit = isset($options['limit']) ? (int)$options['limit'] : 6;

        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }
            $content = (string)($page['content'] ?? '');
            $cleanExcerpt = mb_substr(trim(strip_tags($content)), 0, 160);
            if (mb_strlen($cleanExcerpt) >= 160) {
                $cleanExcerpt .= '...';
            }

            $createdAt = $page['created_at'] ?? null;
            $dateFormatted = 'Recente';
            if ($createdAt) {
                $time = is_numeric($createdAt) ? (int)$createdAt : strtotime((string)$createdAt);
                if ($time > 0) {
                    $dateFormatted = date('d/m/Y', $time);
                }
            }

            $slug = (string)($page['slug'] ?? 'post-' . ($page['id'] ?? '1'));
            $url = $this->urlResolver->resolve('/' . ltrim($slug, '/'));

            $posts[] = [
                'id'             => $page['id'] ?? $slug,
                'title'          => (string)($page['title'] ?? 'Sem Título'),
                'slug'           => $slug,
                'excerpt'        => $cleanExcerpt !== '' ? $cleanExcerpt : 'Confira os detalhes completos deste artigo.',
                'featured_image' => $page['featured_image'] ?? null,
                'published_at'   => $dateFormatted,
                'author'         => (string)($page['author'] ?? 'Redação'),
                'category'       => (string)($page['category'] ?? 'Artigos'),
                'url'            => $url,
                'reading_time'   => max(1, (int)ceil(str_word_count(strip_tags($content)) / 200)) . ' min de leitura',
            ];

            if ($limit > 0 && count($posts) >= $limit) {
                break;
            }
        }

        return $posts;
    }
}
