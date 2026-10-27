<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Vídeo Responsivo (YouTube, Vimeo, MP4 com modo cinema).
 */
class VideoBlock implements BlockInterface
{
    use AllowedPartTypesTrait;
    public function getType(): string
    {
        return 'video';
    }

    public function getName(): string
    {
        return 'Vídeo';
    }

    public function getIcon(): string
    {
        return '🎥';
    }

    public function getCategory(): string
    {
        return 'media';
    }

    public function render(array $data = [], array $context = []): string
    {
        $url = trim((string)($data['url'] ?? ''));
        if ($url === '') {
            return '';
        }

        $aspectRatio = (string)($data['aspect_ratio'] ?? '16/9');
        $borderRadius = (string)($data['border_radius'] ?? '10px');

        $embedUrl = $this->resolveEmbedUrl($url);

        if ($embedUrl !== null) {
            return <<<HTML
<div class="ds-block-video" style="width: 100%; max-width: 100%; margin: 0 auto; border-radius: {$borderRadius}; overflow: hidden; aspect-ratio: {$aspectRatio}; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <iframe src="{$embedUrl}" style="width: 100%; height: 100%; border: none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
</div>
HTML;
        }

        // Caso seja MP4 direto
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<div class="ds-block-video" style="width: 100%; max-width: 100%; margin: 0 auto; border-radius: {$borderRadius}; overflow: hidden; aspect-ratio: {$aspectRatio}; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <video src="{$safeUrl}" controls style="width: 100%; height: 100%; object-fit: cover;"></video>
</div>
HTML;
    }

    private function resolveEmbedUrl(string $url): ?string
    {
        // YouTube
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Vimeo
        if (preg_match('#vimeo\.com/(?:video/)?([0-9]+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return null;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'video',
            'fields' => [
                'url'           => ['type' => 'text', 'label' => 'URL do Vídeo (YouTube, Vimeo ou MP4)', 'default' => ''],
                'aspect_ratio'  => ['type' => 'select', 'label' => 'Proporção', 'options' => ['16/9', '4/3', '1/1'], 'default' => '16/9'],
                'border_radius' => ['type' => 'dimension', 'label' => 'Arredondamento', 'units' => ['px', 'rem'], 'default' => '10px'],
            ],
        ];
    }
}
