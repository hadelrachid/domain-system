<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\LessonBlockRendererInterface;

/**
 * Renderizador de Conteúdo em Blocos para Aulas do Academy (SOLID - SRP).
 *
 * Converte estruturas de blocos JSON (estilo Gutenberg / Notion / Editor.js)
 * em HTML semântico, responsivo, seguro (anti-XSS) e perfeitamente integrado
 * ao design Dark/Cyberpunk do sistema.
 */
class LessonBlockRenderer implements LessonBlockRendererInterface
{
    private const ALLOWED_INLINE_TAGS = '<strong><b><em><i><code><a><mark><kbd><br><small><u><del>';

    /**
     * Renderiza o conteúdo da aula.
     * Se for um JSON de blocos, renderiza cada bloco em HTML semântico.
     * Se for uma string comum/legada, retorna diretamente.
     */
    public function render(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $trimmed = trim($content);

        // Detecta se é JSON de blocos
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $blocks = $decoded['blocks'] ?? (isset($decoded[0]['type']) ? $decoded : null);
                if (is_array($blocks)) {
                    return $this->renderBlocks($blocks);
                }
            }
        }

        // Conteúdo legado / HTML cru
        return $content;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function renderBlocks(array $blocks): string
    {
        $html = [];
        $html[] = '<div class="ds-lesson-blocks" style="display:flex; flex-direction:column; gap:20px;">';

        foreach ($blocks as $block) {
            $type = (string)($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            switch ($type) {
                case 'header':
                case 'heading':
                    $html[] = $this->renderHeader($data);
                    break;

                case 'paragraph':
                case 'text':
                    $html[] = $this->renderParagraph($data);
                    break;

                case 'video':
                case 'embed':
                    $html[] = $this->renderVideo($data);
                    break;

                case 'code':
                    $html[] = $this->renderCode($data);
                    break;

                case 'callout':
                case 'alert':
                    $html[] = $this->renderCallout($data);
                    break;

                case 'list':
                    $html[] = $this->renderList($data);
                    break;

                case 'image':
                    $html[] = $this->renderImage($data);
                    break;

                case 'delimiter':
                case 'divider':
                    $html[] = $this->renderDelimiter($data);
                    break;

                default:
                    // Bloco desconhecido: se houver texto, renderiza parágrafo seguro
                    if (!empty($data['text'])) {
                        $html[] = $this->renderParagraph($data);
                    }
                    break;
            }
        }

        $html[] = '</div>';
        return implode("\n", $html);
    }

    public function renderHeader(array $data): string
    {
        $rawLevel = (int)($data['level'] ?? 2);
        $level = in_array($rawLevel, [1, 2, 3, 4], true) ? $rawLevel : 2;
        $text = htmlspecialchars((string)($data['text'] ?? ''), ENT_QUOTES, 'UTF-8');

        $fontSizes = [
            1 => '2rem',
            2 => '1.6rem',
            3 => '1.3rem',
            4 => '1.1rem',
        ];
        $fontSize = $fontSizes[$level];

        return "<h{$level} class=\"ds-block-heading ds-heading-h{$level}\" style=\"margin: 25px 0 10px 0; color: #fff; font-weight: 700; font-size: {$fontSize}; letter-spacing: -0.5px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;\">{$text}</h{$level}>";
    }

    public function renderParagraph(array $data): string
    {
        $raw = (string)($data['text'] ?? '');
        $safeHtml = $this->sanitizeInlineHtml($raw);

        return "<p class=\"ds-block-paragraph\" style=\"margin: 0; line-height: 1.8; color: var(--text-main, #c5c6c7); font-size: 1.05rem;\">{$safeHtml}</p>";
    }

    public function renderVideo(array $data): string
    {
        $url = trim((string)($data['url'] ?? $data['source'] ?? ''));
        if ($url === '') {
            return '';
        }

        $caption = htmlspecialchars((string)($data['caption'] ?? ''), ENT_QUOTES, 'UTF-8');
        $captionHtml = $caption !== '' 
            ? "<figcaption style=\"margin-top: 8px; font-size: 12px; color: var(--text-muted, #8c8f94); text-align: center;\">{$caption}</figcaption>" 
            : '';

        $embedUrl = $this->convertVideoUrlToEmbed($url);

        if ($embedUrl !== null) {
            return "
            <figure class=\"ds-block-video\" style=\"margin: 24px 0;\">
                <div style=\"position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background: #000; border: 1px solid rgba(69, 243, 255, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4);\">
                    <iframe src=\"{$embedUrl}\" style=\"position: absolute; top:0; left:0; width:100%; height:100%; border:0;\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen loading=\"lazy\"></iframe>
                </div>
                {$captionHtml}
            </figure>";
        }

        // Vídeo direto MP4/WEBM
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return "
        <figure class=\"ds-block-video\" style=\"margin: 24px 0;\">
            <div style=\"border-radius: 8px; overflow: hidden; background: #000; border: 1px solid rgba(69, 243, 255, 0.2);\">
                <video controls style=\"width: 100%; display: block; max-height: 550px;\">
                    <source src=\"{$safeUrl}\">
                    Seu navegador não suporta a tag de vídeo.
                </video>
            </div>
            {$captionHtml}
        </figure>";
    }

    public function renderCode(array $data): string
    {
        $code = (string)($data['code'] ?? '');
        $lang = htmlspecialchars(trim((string)($data['language'] ?? 'php')), ENT_QUOTES, 'UTF-8');
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $displayLang = strtoupper($lang ?: 'CODE');

        return "
        <div class=\"ds-block-code\" style=\"margin: 24px 0; border-radius: 8px; overflow: hidden; background: #0b0c10; border: 1px solid rgba(255,255,255,0.1);\">
            <div style=\"display: flex; justify-content: space-between; align-items: center; background: #14191c; padding: 8px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); font-family: monospace; font-size: 12px;\">
                <span style=\"color: var(--accent-blue, #45f3ff); font-weight: bold;\">{$displayLang}</span>
                <button type=\"button\" onclick=\"navigator.clipboard.writeText(this.closest('.ds-block-code').querySelector('code').innerText); var b=this; b.innerText='Copiado!'; setTimeout(function(){b.innerText='Copiar';}, 2000);\" style=\"background: transparent; border: 1px solid rgba(255,255,255,0.15); color: #8c8f94; border-radius: 4px; padding: 4px 10px; font-size: 11px; cursor: pointer; transition: all 0.2s;\">
                    Copiar
                </button>
            </div>
            <pre style=\"margin: 0; padding: 18px; overflow-x: auto; font-family: 'Fira Code', Consolas, Monaco, monospace; font-size: 13.5px; line-height: 1.6; color: #e6edf3;\"><code class=\"language-{$lang}\">{$escapedCode}</code></pre>
        </div>";
    }

    public function renderCallout(array $data): string
    {
        $type = (string)($data['type'] ?? 'info');
        $raw = (string)($data['text'] ?? '');
        $safeHtml = $this->sanitizeInlineHtml($raw);

        $styles = [
            'info' => [
                'border' => 'var(--accent-blue, #45f3ff)',
                'bg'     => 'rgba(69, 243, 255, 0.08)',
                'icon'   => '💡',
                'title'  => 'Nota Didática',
            ],
            'success' => [
                'border' => 'var(--accent-green, #00d284)',
                'bg'     => 'rgba(0, 210, 132, 0.08)',
                'icon'   => '✅',
                'title'  => 'Boas Práticas & SOLID',
            ],
            'warning' => [
                'border' => 'var(--accent-orange, #f56e28)',
                'bg'     => 'rgba(245, 110, 40, 0.08)',
                'icon'   => '⚠️',
                'title'  => 'Atenção',
            ],
            'danger' => [
                'border' => '#ff4d4f',
                'bg'     => 'rgba(255, 77, 79, 0.08)',
                'icon'   => '🛑',
                'title'  => 'Cuidado / Erro Comum',
            ],
        ];

        $current = $styles[$type] ?? $styles['info'];

        return "
        <div class=\"ds-block-callout ds-callout-{$type}\" style=\"margin: 20px 0; padding: 16px 20px; border-radius: 6px; border-left: 4px solid {$current['border']}; background: {$current['bg']}; display: flex; gap: 14px; align-items: flex-start;\">
            <div style=\"font-size: 1.4rem; line-height: 1;\">{$current['icon']}</div>
            <div style=\"flex: 1; font-size: 0.98rem; line-height: 1.7; color: var(--text-main, #fff);\">
                {$safeHtml}
            </div>
        </div>";
    }

    public function renderList(array $data): string
    {
        $style = (string)($data['style'] ?? 'unordered');
        $rawItems = is_array($data['items'] ?? null) ? $data['items'] : [];

        if (empty($rawItems)) {
            return '';
        }

        $tag = ($style === 'ordered') ? 'ol' : 'ul';
        $itemsHtml = [];

        foreach ($rawItems as $item) {
            $itemText = is_string($item) ? $item : (string)($item['content'] ?? '');
            $safe = $this->sanitizeInlineHtml($itemText);
            $itemsHtml[] = "<li style=\"margin-bottom: 8px;\">{$safe}</li>";
        }

        $inner = implode("\n", $itemsHtml);
        return "<{$tag} class=\"ds-block-list\" style=\"margin: 16px 0 16px 24px; padding: 0; line-height: 1.8; color: var(--text-main, #c5c6c7); font-size: 1.05rem;\">\n{$inner}\n</{$tag}>";
    }

    public function renderImage(array $data): string
    {
        $url = trim((string)($data['url'] ?? $data['file']['url'] ?? ''));
        if ($url === '') {
            return '';
        }

        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $caption = htmlspecialchars((string)($data['caption'] ?? ''), ENT_QUOTES, 'UTF-8');
        $captionHtml = $caption !== '' 
            ? "<figcaption style=\"margin-top: 8px; font-size: 12px; color: var(--text-muted, #8c8f94); text-align: center;\">{$caption}</figcaption>" 
            : '';

        return "
        <figure class=\"ds-block-figure\" style=\"margin: 28px 0; text-align: center;\">
            <img src=\"{$safeUrl}\" alt=\"{$caption}\" style=\"max-width: 100%; height: auto; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); box-shadow: 0 4px 18px rgba(0,0,0,0.3);\" loading=\"lazy\">
            {$captionHtml}
        </figure>";
    }

    public function renderDelimiter(array $data): string
    {
        return '<hr class="ds-block-delimiter" style="margin: 35px 0; border: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(69, 243, 255, 0.4), transparent);">';
    }

    public function convertVideoUrlToEmbed(string $url): ?string
    {
        // YouTube: youtube.com/watch?v=ID ou youtu.be/ID ou youtube.com/embed/ID
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)/i', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return null;
    }

    /**
     * Extrai texto puro de um conteúdo (seja JSON de blocos ou HTML legado) para uso em SEO / resumos.
     */
    public function extractPlainText(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $trimmed = trim($content);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $blocks = $decoded['blocks'] ?? (isset($decoded[0]['type']) ? $decoded : null);
                if (is_array($blocks)) {
                    $parts = [];
                    foreach ($blocks as $block) {
                        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
                        if (!empty($data['text'])) {
                            $parts[] = strip_tags((string)$data['text']);
                        } elseif (!empty($data['caption'])) {
                            $parts[] = strip_tags((string)$data['caption']);
                        }
                    }
                    return trim(implode(' ', $parts));
                }
            }
        }

        return trim(strip_tags($content));
    }

    private function sanitizeInlineHtml(string $html): string
    {
        // Remove scripts e tags perigosas preservando tags de formatação segura
        $stripped = strip_tags($html, self::ALLOWED_INLINE_TAGS);
        // Remove manipuladores de eventos maliciosos tipo onclick, onerror
        $cleaned = (string)preg_replace('/\s*on\w+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $stripped);
        // Bloqueia protocolos perigosos (javascript:, data:, vbscript:) em atributos href
        return (string)preg_replace('/\s*href\s*=\s*(["\']?)\s*(?:javascript|vbscript|data):[^"\'>\s]*\1/i', ' href="#"', $cleaned);
    }
}
