<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Vídeo Responsivo (YouTube, Vimeo, MP4 com modo cinema).
 */
class VideoBlock implements BlockInterface
{
    private ElementStyleRendererInterface $styleRenderer;
    private HtmlSanitizerInterface $htmlSanitizer;

    public function __construct(
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null
    ) {
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
    }

    public function getAllowedPartTypes(): array
    {
        return [
            ThemePartInterface::TYPE_SECTION,
        ];
    }

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
        $rawUrl = trim((string)($data['url'] ?? $data['url_tablet'] ?? $data['url_mobile'] ?? ''));
        if ($rawUrl === '') {
            return '';
        }

        // Se o usuário colou um iframe completo <iframe ... src="..." ...>, extrai o atributo src
        if (preg_match('#<iframe[^>]+src=["\']([^"\']+)["\']#i', $rawUrl, $matches)) {
            $rawUrl = trim($matches[1]);
        }

        $url = $this->htmlSanitizer->sanitizeUrl($rawUrl, '');
        if ($url === '') {
            return '';
        }

        $aspectRatio = $this->styleRenderer->sanitizeCssValue((string)($data['aspect_ratio'] ?? '16/9'));
        $borderRadius = $this->styleRenderer->sanitizeCssValue((string)($data['border_radius'] ?? '10px'));

        $embedUrl = $this->resolveEmbedUrl($url);

        if ($embedUrl !== null) {
            $safeEmbed = htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8');
            return <<<HTML
<div class="ds-block-video" style="width: 100%; max-width: 100%; margin: 0 auto; border-radius: {$borderRadius}; overflow: hidden; aspect-ratio: {$aspectRatio}; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <iframe src="{$safeEmbed}" style="width: 100%; height: 100%; border: none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
</div>
HTML;
        }

        // Caso seja MP4, WebM ou arquivo de vídeo direto
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<div class="ds-block-video" style="width: 100%; max-width: 100%; margin: 0 auto; border-radius: {$borderRadius}; overflow: hidden; aspect-ratio: {$aspectRatio}; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <video src="{$safeUrl}" controls preload="metadata" style="width: 100%; height: 100%; object-fit: cover;"></video>
</div>
HTML;
    }

    private function resolveEmbedUrl(string $url): ?string
    {
        // 1. YouTube Shorts, Live, Embed ou v/
        if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:embed/|v/|shorts/|live/)|youtu\.be/)([a-zA-Z0-9_-]{6,15})#i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // 2. YouTube watch?v=... (com query parameters arbitrários, ex: watch?feature=share&v=...)
        if (preg_match('#youtube(?:-nocookie)?\.com/watch#i', $url)) {
            $queryString = parse_url($url, PHP_URL_QUERY);
            if ($queryString) {
                parse_str($queryString, $queryParams);
                if (!empty($queryParams['v']) && is_string($queryParams['v'])) {
                    $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '', $queryParams['v']);
                    if ($cleanId !== '') {
                        return 'https://www.youtube.com/embed/' . $cleanId;
                    }
                }
            }
        }

        // 3. Vimeo: vimeo.com/123456789, player.vimeo.com/video/123456789, canais, grupos
        if (preg_match('#vimeo(?:\.com/video/|\.com/channels/(?:\w+/)?|\.com/groups/[^/]+/videos/|\.com/|player\.vimeo\.com/video/)([0-9]+)#i', $url, $m)) {
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
