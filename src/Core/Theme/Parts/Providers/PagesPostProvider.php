<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Providers;

use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;

/**
 * Adapter que conecta o repositório de Páginas/Artigos do sistema ao contrato PostProviderInterface.
 */
class PagesPostProvider implements PostProviderInterface
{
    private ?PageRepositoryInterface $pageRepo;
    private PostProviderInterface $fallbackProvider;

    public function __construct(?PageRepositoryInterface $pageRepo = null, ?PostProviderInterface $fallbackProvider = null)
    {
        $this->pageRepo = $pageRepo;
        $this->fallbackProvider = $fallbackProvider ?? new MockPostProvider();
    }

    public function getPosts(array $options = []): array
    {
        if ($this->pageRepo === null) {
            return $this->fallbackProvider->getPosts($options);
        }

        try {
            $pages = $this->pageRepo->getAll();
        } catch (\Throwable $e) {
            return $this->fallbackProvider->getPosts($options);
        }

        if (empty($pages)) {
            return $this->fallbackProvider->getPosts($options);
        }

        $posts = [];
        $limit = isset($options['limit']) ? (int)$options['limit'] : 6;

        foreach ($pages as $page) {
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
            $url = defined('BASE_URL') ? BASE_URL . '/' . ltrim($slug, '/') : '/' . ltrim($slug, '/');

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
