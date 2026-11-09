<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;
use DomainSystem\Plugins\academy\Contracts\LessonBlockRendererInterface;
use DomainSystem\Plugins\academy\Contracts\LessonPdfStorageInterface;

/**
 * Renderizador de Conteúdo em Blocos para Aulas do Academy (SOLID - SRP / DIP).
 *
 * Converte estruturas de blocos JSON (estilo Gutenberg / Notion / Editor.js)
 * em HTML semântico, responsivo, seguro (anti-XSS) e perfeitamente integrado
 * ao design Dark/Cyberpunk do sistema.
 */
class LessonBlockRenderer implements LessonBlockRendererInterface
{
    private const ALLOWED_INLINE_TAGS = '<strong><b><em><i><code><a><mark><kbd><br><small><u><del>';

    private ?LessonPdfStorageInterface $pdfStorage;
    private HtmlSanitizerInterface $sanitizer;
    private bool $pdfRuntimeInjected = false;

    public function __construct(
        ?LessonPdfStorageInterface $pdfStorage = null,
        ?HtmlSanitizerInterface $sanitizer = null
    ) {
        $this->pdfStorage = $pdfStorage;
        $this->sanitizer = $sanitizer ?? new SafeHtmlSanitizer();
    }

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

                case 'pdf':
                case 'pdf_viewer':
                    $html[] = $this->renderPdf($data);
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

    /**
     * Renderiza um Leitor de PDF Híbrido (Nuvem HTTP/HTTPS, Google Drive ou Pasta Local na Hostinger).
     * Suporta renderização preguiçosa por página via PDF.js (<canvas>) com recorte de páginas (start_page / end_page),
     * modo iframe nativo e modo cartão compacto expansível.
     *
     * @param array<string, mixed> $data
     */
    public function renderPdf(array $data): string
    {
        $rawUrl = trim((string)($data['url'] ?? $data['source'] ?? $data['file']['url'] ?? ''));
        if ($rawUrl === '') {
            return '';
        }

        // Caso o usuário tenha colado o código HTML <iframe src="..."> do OneDrive/Google Drive, extrai apenas o src
        if (stripos($rawUrl, '<iframe') !== false && preg_match('/\bsrc=["\']([^"\']+)["\']/i', $rawUrl, $iframeSrcMatch)) {
            $rawUrl = html_entity_decode(trim($iframeSrcMatch[1]), ENT_QUOTES, 'UTF-8');
        }

        // Bloqueia esquemas maliciosos
        if (preg_match('/^\s*(?:javascript|data|vbscript|file):/i', $rawUrl)) {
            return '';
        }

        $resolvedUrl = $this->pdfStorage !== null
            ? $this->pdfStorage->resolvePublicUrl($rawUrl)
            : $rawUrl;

        if ($resolvedUrl === '') {
            return '';
        }

        $title = htmlspecialchars(trim((string)($data['title'] ?? 'Documento PDF da Aula')), ENT_QUOTES, 'UTF-8');
        if ($title === '') {
            $title = 'Documento PDF da Aula';
        }
        $caption = htmlspecialchars(trim((string)($data['caption'] ?? '')), ENT_QUOTES, 'UTF-8');
        $mode = (string)($data['mode'] ?? 'pdfjs_canvas');
        if (!in_array($mode, ['pdfjs_canvas', 'native_embed', 'card_modal'], true)) {
            $mode = 'pdfjs_canvas';
        }

        $startPage = max(1, (int)($data['start_page'] ?? 1));
        $endPage = max(0, (int)($data['end_page'] ?? 0));
        if ($endPage > 0 && $endPage < $startPage) {
            $endPage = $startPage;
        }

        $rawHeight = trim((string)($data['height'] ?? '620px'));
        $height = preg_match('/^\d+(?:px|vh|rem)$/', $rawHeight) ? $rawHeight : '620px';
        $allowDownload = !array_key_exists('allow_download', $data) || !empty($data['allow_download']);

        // Detecta links de nuvem (Google Drive, Microsoft OneDrive / SharePoint, Dropbox) e converte para URL de embed
        [$isCloudEmbedOnly, $iframeUrl] = $this->resolveCloudOrDirectIframeUrl($resolvedUrl, $startPage, $allowDownload);

        $safeUrl = htmlspecialchars($resolvedUrl, ENT_QUOTES, 'UTF-8');
        $safeIframeUrl = htmlspecialchars($iframeUrl, ENT_QUOTES, 'UTF-8');
        $uid = 'ds_pdf_' . substr(md5($resolvedUrl . '_' . $startPage . '_' . $endPage . '_' . uniqid('', true)), 0, 10);

        $rangeBadge = $endPage > 0
            ? "<span style=\"background:rgba(69,243,255,0.14); color:#45f3ff; border:1px solid rgba(69,243,255,0.3); padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;\">Págs. {$startPage}&ndash;{$endPage}</span>"
            : ($startPage > 1
                ? "<span style=\"background:rgba(69,243,255,0.14); color:#45f3ff; border:1px solid rgba(69,243,255,0.3); padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;\">Inicia na Pág. {$startPage}</span>"
                : '');

        $downloadActionsHtml = '';
        if ($allowDownload) {
            $downloadActionsHtml = "
                <a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"ds-pdf-btn\" title=\"Abrir PDF em nova guia\" style=\"text-decoration:none;\">↗ Abrir</a>
                <a href=\"{$safeUrl}\" download class=\"ds-pdf-btn ds-pdf-btn-accent\" title=\"Baixar arquivo PDF\" style=\"text-decoration:none;\">⬇ Baixar</a>";
        }

        $captionHtml = $caption !== ''
            ? "<figcaption style=\"margin-top: 8px; font-size: 12px; color: var(--text-muted, #8c8f94); text-align: center;\">{$caption}</figcaption>"
            : '';

        $runtimeScript = $this->getPdfViewerRuntimeOnce();

        // Se for link de nuvem (Google Drive / OneDrive / SharePoint) ou modo native_embed, renderiza o container com Iframe otimizado
        if ($mode === 'native_embed' || $isCloudEmbedOnly) {
            return "{$runtimeScript}
            <figure class=\"ds-block-pdf ds-pdf-mode-native\" id=\"{$uid}\" style=\"margin: 26px 0; border-radius: 10px; overflow: hidden; background: #0b0e14; border: 1px solid rgba(69, 243, 255, 0.25); box-shadow: 0 8px 28px rgba(0,0,0,0.45);\">
                <div class=\"ds-pdf-toolbar\" style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding:10px 16px; background:#141920; border-bottom:1px solid rgba(255,255,255,0.08);\">
                    <div style=\"display:flex; align-items:center; gap:10px; min-width:0;\">
                        <span style=\"font-size:1.15rem;\">📄</span>
                        <strong style=\"color:#fff; font-size:0.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">{$title}</strong>
                        {$rangeBadge}
                    </div>
                    <div style=\"display:flex; align-items:center; gap:6px; flex-wrap:wrap;\">
                        <button type=\"button\" class=\"ds-pdf-btn\" onclick=\"window.dsTogglePdfFullscreen('{$uid}')\" title=\"Tela Cheia\">⛶ Tela Cheia</button>
                        {$downloadActionsHtml}
                    </div>
                </div>
                <div class=\"ds-pdf-viewport\" style=\"position:relative; width:100%; height:{$height}; background:#1a1d24;\">
                    <iframe src=\"{$safeIframeUrl}\" style=\"width:100%; height:100%; border:0; display:block;\" loading=\"lazy\" allowfullscreen></iframe>
                </div>
                {$captionHtml}
            </figure>";
        }

        // Modo Cartão Compacto com Leitor Expansível Sob Demanda (Ideal para não poluir a aula até o clique)
        $isCardModal = ($mode === 'card_modal');
        $bodyDisplay = $isCardModal ? 'none' : 'block';
        $toggleCardBtn = $isCardModal
            ? "<button type=\"button\" class=\"ds-pdf-btn ds-pdf-btn-accent\" onclick=\"window.dsTogglePdfCollapse('{$uid}')\" data-pdf-toggle-btn=\"{$uid}\">📖 Abrir Leitor Online</button>"
            : '';

        $contextMenuGuard = !$allowDownload ? ' oncontextmenu="return false;"' : '';

        return "{$runtimeScript}
        <figure class=\"ds-block-pdf ds-pdf-mode-{$mode}\" id=\"{$uid}\"
            data-pdf-url=\"{$safeUrl}\"
            data-pdf-iframe-url=\"{$safeIframeUrl}\"
            data-start-page=\"{$startPage}\"
            data-end-page=\"{$endPage}\"
            data-allow-download=\"" . ($allowDownload ? '1' : '0') . "\"
            data-lazy-collapsed=\"" . ($isCardModal ? '1' : '0') . "\"
            style=\"margin: 26px 0; border-radius: 10px; overflow: hidden; background: #0b0e14; border: 1px solid rgba(69, 243, 255, 0.25); box-shadow: 0 8px 28px rgba(0,0,0,0.45);\">{$contextMenuGuard}
            <div class=\"ds-pdf-toolbar\" style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding:10px 16px; background:#141920; border-bottom:1px solid rgba(255,255,255,0.08);\">
                <div style=\"display:flex; align-items:center; gap:10px; min-width:0;\">
                    <span style=\"font-size:1.15rem;\">📄</span>
                    <strong style=\"color:#fff; font-size:0.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">{$title}</strong>
                    {$rangeBadge}
                </div>
                <div class=\"ds-pdf-controls\" style=\"display:flex; align-items:center; gap:6px; flex-wrap:wrap;\">
                    {$toggleCardBtn}
                    <div class=\"ds-pdf-nav-group\" style=\"display:" . ($isCardModal ? 'none' : 'inline-flex') . "; align-items:center; gap:5px; background:#0b0e14; padding:3px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);\">
                        <button type=\"button\" class=\"ds-pdf-btn\" data-pdf-prev title=\"Página Anterior\">◀</button>
                        <span style=\"font-size:12px; color:#cbd5e1; font-family:monospace;\">
                            Pág <input type=\"number\" data-pdf-page-input value=\"{$startPage}\" min=\"{$startPage}\" style=\"width:46px; background:#141920; color:#45f3ff; border:1px solid rgba(69,243,255,0.3); border-radius:4px; padding:2px 4px; text-align:center; font-size:12px; font-weight:700;\">
                            de <span data-pdf-page-count>...</span>
                        </span>
                        <button type=\"button\" class=\"ds-pdf-btn\" data-pdf-next title=\"Próxima Página\">▶</button>
                    </div>
                    <div class=\"ds-pdf-zoom-group\" style=\"display:" . ($isCardModal ? 'none' : 'inline-flex') . "; align-items:center; gap:4px; background:#0b0e14; padding:3px 6px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);\">
                        <button type=\"button\" class=\"ds-pdf-btn\" data-pdf-zoom-out title=\"Diminuir Zoom\">&minus;</button>
                        <span data-pdf-zoom-label style=\"font-size:11px; color:#94a3b8; font-family:monospace; min-width:42px; text-align:center;\">100%</span>
                        <button type=\"button\" class=\"ds-pdf-btn\" data-pdf-zoom-in title=\"Aumentar Zoom\">+</button>
                        <button type=\"button\" class=\"ds-pdf-btn\" data-pdf-zoom-fit title=\"Ajustar à Largura\">↔</button>
                    </div>
                    <button type=\"button\" class=\"ds-pdf-btn\" onclick=\"window.dsTogglePdfFullscreen('{$uid}')\" title=\"Tela Cheia\">⛶</button>
                    {$downloadActionsHtml}
                </div>
            </div>
            <div class=\"ds-pdf-body\" style=\"display:{$bodyDisplay}; position:relative; width:100%; height:{$height}; overflow:auto; background:#161922; text-align:center; padding:16px; box-sizing:border-box;\">
                <div data-pdf-status style=\"color:#45f3ff; font-size:13px; padding:24px;\">⏳ Carregando página {$startPage} sob demanda...</div>
                <canvas data-pdf-canvas style=\"max-width:100%; height:auto; border-radius:4px; box-shadow:0 6px 24px rgba(0,0,0,0.6); display:none; margin:0 auto;\"></canvas>
                <iframe data-pdf-fallback-frame style=\"width:100%; height:100%; border:0; display:none; border-radius:4px;\"></iframe>
            </div>
            {$captionHtml}
        </figure>";
    }

    /**
     * Normaliza links de provedores de nuvem (Google Drive, Microsoft OneDrive, SharePoint, Dropbox)
     * ou URLs diretas de PDF para exibição embutida no leitor.
     *
     * @return array{0: bool, 1: string} [isCloudEmbedOnly, iframeUrl]
     */
    private function resolveCloudOrDirectIframeUrl(string $resolvedUrl, int $startPage, bool $allowDownload): array
    {
        // 1. Google Drive: /file/d/{ID}/view ou /open?id={ID} -> /file/d/{ID}/preview
        if (preg_match('~drive\.google\.com/file/d/([a-zA-Z0-9_-]+)~i', $resolvedUrl, $gdMatches)) {
            return [true, 'https://drive.google.com/file/d/' . $gdMatches[1] . '/preview'];
        }
        if (preg_match('~drive\.google\.com/(?:open|uc)\?(?:[^#]*&)?id=([a-zA-Z0-9_-]+)~i', $resolvedUrl, $gdOpenMatches)) {
            return [true, 'https://drive.google.com/file/d/' . $gdOpenMatches[1] . '/preview'];
        }

        // 2. Microsoft OneDrive Pessoal (onedrive.live.com / 1drv.ms)
        if (preg_match('~onedrive\.live\.com/embed~i', $resolvedUrl)) {
            return [true, $resolvedUrl];
        }
        if (preg_match('~onedrive\.live\.com/(?:view\.aspx|redir)\?(.+)$~i', $resolvedUrl, $odMatches)) {
            return [true, 'https://onedrive.live.com/embed?' . $odMatches[1]];
        }
        if (preg_match('~1drv\.ms/~i', $resolvedUrl)) {
            return [true, $resolvedUrl];
        }

        // 3. Microsoft SharePoint / OneDrive for Business (*.sharepoint.com)
        if (preg_match('~\.sharepoint\.com/~i', $resolvedUrl)) {
            $spUrl = $resolvedUrl;
            if (!str_contains(strtolower($spUrl), 'action=embedview') && !str_contains(strtolower($spUrl), '/embed')) {
                $spUrl .= (str_contains($spUrl, '?') ? '&' : '?') . 'action=embedview';
            }
            return [true, $spUrl];
        }

        // 4. Dropbox: converte ?dl=0 para ?raw=1 para streaming direto do PDF
        if (preg_match('~dropbox\.com/~i', $resolvedUrl)) {
            $dbxUrl = preg_replace('/([?&])dl=0\b/i', '$1raw=1', $resolvedUrl) ?? $resolvedUrl;
            if (!str_contains($dbxUrl, 'raw=1')) {
                $dbxUrl .= (str_contains($dbxUrl, '?') ? '&' : '?') . 'raw=1';
            }
            return [true, $dbxUrl];
        }

        // 5. URL direta de arquivo .pdf (Local ou CDN)
        $hashParams = ["page={$startPage}", 'zoom=page-width'];
        if (!$allowDownload) {
            $hashParams[] = 'toolbar=0';
            $hashParams[] = 'navpanes=0';
        }
        $fragment = implode('&', $hashParams);
        $iframeUrl = str_contains($resolvedUrl, '#')
            ? $resolvedUrl . '&' . $fragment
            : $resolvedUrl . '#' . $fragment;

        return [false, $iframeUrl];
    }

    private function getPdfViewerRuntimeOnce(): string
    {
        if ($this->pdfRuntimeInjected) {
            return '';
        }
        $this->pdfRuntimeInjected = true;

        return <<<'HTML'
<style id="ds-academy-pdf-runtime-css">
.ds-block-pdf .ds-pdf-btn {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.14);
    color: #e2e8f0;
    border-radius: 5px;
    padding: 4px 9px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.ds-block-pdf .ds-pdf-btn:hover {
    background: rgba(69,243,255,0.15);
    border-color: rgba(69,243,255,0.45);
    color: #45f3ff;
}
.ds-block-pdf .ds-pdf-btn-accent {
    background: rgba(69,243,255,0.15);
    border-color: rgba(69,243,255,0.4);
    color: #45f3ff;
}
.ds-block-pdf:fullscreen,
.ds-block-pdf.ds-pdf-fullscreen {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    z-index: 99999 !important;
    border-radius: 0 !important;
    display: flex !important;
    flex-direction: column !important;
}
.ds-block-pdf:fullscreen .ds-pdf-body,
.ds-block-pdf:fullscreen .ds-pdf-viewport,
.ds-block-pdf.ds-pdf-fullscreen .ds-pdf-body,
.ds-block-pdf.ds-pdf-fullscreen .ds-pdf-viewport {
    flex: 1 !important;
    height: auto !important;
}
</style>
<script id="ds-academy-pdf-runtime-js">
(function() {
    if (window.__dsAcademyPdfRuntimeLoaded) return;
    window.__dsAcademyPdfRuntimeLoaded = true;

    var PDFJS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
    var PDFJS_WORKER = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    var pdfJsPromise = null;

    function loadPdfJs() {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (pdfJsPromise) return pdfJsPromise;
        pdfJsPromise = new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = PDFJS_CDN;
            s.async = true;
            s.onload = function() {
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
                    resolve(window.pdfjsLib);
                } else {
                    reject(new Error('PDF.js indisponível'));
                }
            };
            s.onerror = function() { reject(new Error('Falha ao carregar CDN PDF.js')); };
            document.head.appendChild(s);
        });
        return pdfJsPromise;
    }

    window.dsTogglePdfFullscreen = function(uid) {
        var el = document.getElementById(uid);
        if (!el) return;
        if (document.fullscreenElement === el) {
            document.exitFullscreen().catch(function(){});
            el.classList.remove('ds-pdf-fullscreen');
        } else if (el.requestFullscreen) {
            el.requestFullscreen().catch(function() {
                el.classList.toggle('ds-pdf-fullscreen');
            });
        } else {
            el.classList.toggle('ds-pdf-fullscreen');
        }
    };

    window.dsTogglePdfCollapse = function(uid) {
        var el = document.getElementById(uid);
        if (!el) return;
        var body = el.querySelector('.ds-pdf-body');
        var navGroup = el.querySelector('.ds-pdf-nav-group');
        var zoomGroup = el.querySelector('.ds-pdf-zoom-group');
        var btn = el.querySelector('[data-pdf-toggle-btn]');
        if (!body) return;
        var isHidden = (body.style.display === 'none');
        body.style.display = isHidden ? 'block' : 'none';
        if (navGroup) navGroup.style.display = isHidden ? 'inline-flex' : 'none';
        if (zoomGroup) zoomGroup.style.display = isHidden ? 'inline-flex' : 'none';
        if (btn) btn.textContent = isHidden ? '🔼 Recolher Leitor' : '📖 Abrir Leitor Online';
        if (isHidden && !el.dataset.pdfInitialized) {
            initSinglePdfViewer(el);
        }
    };

    function activateIframeFallback(container) {
        var statusEl = container.querySelector('[data-pdf-status]');
        var canvas = container.querySelector('[data-pdf-canvas]');
        var frame = container.querySelector('[data-pdf-fallback-frame]');
        var body = container.querySelector('.ds-pdf-body');
        if (statusEl) statusEl.style.display = 'none';
        if (canvas) canvas.style.display = 'none';
        if (body) body.style.padding = '0';
        if (frame) {
            frame.src = container.getAttribute('data-pdf-iframe-url') || container.getAttribute('data-pdf-url') || '';
            frame.style.display = 'block';
        }
    }

    function initSinglePdfViewer(container) {
        if (!container || container.dataset.pdfInitialized === '1') return;
        var url = container.getAttribute('data-pdf-url');
        if (!url) return;
        container.dataset.pdfInitialized = '1';

        var startPage = Math.max(1, parseInt(container.getAttribute('data-start-page') || '1', 10));
        var endPage = Math.max(0, parseInt(container.getAttribute('data-end-page') || '0', 10));
        var currentPage = startPage;
        var scale = 1.25;
        var pdfDoc = null;
        var rendering = false;
        var pendingPage = null;

        var statusEl = container.querySelector('[data-pdf-status]');
        var canvas = container.querySelector('[data-pdf-canvas]');
        var pageInput = container.querySelector('[data-pdf-page-input]');
        var pageCountEl = container.querySelector('[data-pdf-page-count]');
        var zoomLabel = container.querySelector('[data-pdf-zoom-label]');
        var btnPrev = container.querySelector('[data-pdf-prev]');
        var btnNext = container.querySelector('[data-pdf-next]');
        var btnZoomIn = container.querySelector('[data-pdf-zoom-in]');
        var btnZoomOut = container.querySelector('[data-pdf-zoom-out]');
        var btnZoomFit = container.querySelector('[data-pdf-zoom-fit]');

        function getMaxAllowedPage() {
            if (!pdfDoc) return endPage > 0 ? endPage : 1;
            return endPage > 0 ? Math.min(endPage, pdfDoc.numPages) : pdfDoc.numPages;
        }

        function renderPage(num) {
            if (!pdfDoc || !canvas) return;
            rendering = true;
            if (statusEl) {
                statusEl.textContent = '⏳ Renderizando página ' + num + '...';
                statusEl.style.display = 'block';
            }

            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport({ scale: scale });
                var outputScale = window.devicePixelRatio || 1;
                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = Math.floor(viewport.width) + 'px';
                canvas.style.height = Math.floor(viewport.height) + 'px';

                var ctx = canvas.getContext('2d');
                var transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

                return page.render({
                    canvasContext: ctx,
                    transform: transform,
                    viewport: viewport
                }).promise;
            }).then(function() {
                rendering = false;
                if (statusEl) statusEl.style.display = 'none';
                canvas.style.display = 'block';
                if (pageInput) pageInput.value = String(currentPage);
                if (zoomLabel) zoomLabel.textContent = Math.round((scale / 1.25) * 100) + '%';
                if (pendingPage !== null) {
                    var next = pendingPage;
                    pendingPage = null;
                    renderPage(next);
                }
            }).catch(function() {
                rendering = false;
                activateIframeFallback(container);
            });
        }

        function queueRenderPage(num) {
            var maxP = getMaxAllowedPage();
            num = Math.max(startPage, Math.min(maxP, num));
            currentPage = num;
            if (rendering) {
                pendingPage = num;
            } else {
                renderPage(num);
            }
        }

        if (btnPrev) btnPrev.addEventListener('click', function() { if (currentPage > startPage) queueRenderPage(currentPage - 1); });
        if (btnNext) btnNext.addEventListener('click', function() { if (currentPage < getMaxAllowedPage()) queueRenderPage(currentPage + 1); });
        if (pageInput) pageInput.addEventListener('change', function() { queueRenderPage(parseInt(this.value || String(startPage), 10)); });
        if (btnZoomIn) btnZoomIn.addEventListener('click', function() { scale = Math.min(3.0, scale + 0.2); queueRenderPage(currentPage); });
        if (btnZoomOut) btnZoomOut.addEventListener('click', function() { scale = Math.max(0.6, scale - 0.2); queueRenderPage(currentPage); });
        if (btnZoomFit) btnZoomFit.addEventListener('click', function() { scale = 1.25; queueRenderPage(currentPage); });

        loadPdfJs().then(function(pdfjsLib) {
            return pdfjsLib.getDocument({
                url: url,
                disableAutoFetch: true,
                disableStream: false
            }).promise;
        }).then(function(doc) {
            pdfDoc = doc;
            var maxP = getMaxAllowedPage();
            if (startPage > doc.numPages) startPage = 1;
            currentPage = startPage;
            if (pageCountEl) pageCountEl.textContent = String(maxP);
            if (pageInput) {
                pageInput.min = String(startPage);
                pageInput.max = String(maxP);
            }
            renderPage(currentPage);
        }).catch(function() {
            activateIframeFallback(container);
        });
    }

    window.dsInitAcademyPdfViewers = function() {
        var viewers = document.querySelectorAll('.ds-block-pdf[data-pdf-url]');
        viewers.forEach(function(el) {
            if (el.getAttribute('data-lazy-collapsed') === '1') return;
            initSinglePdfViewer(el);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.dsInitAcademyPdfViewers);
    } else {
        window.dsInitAcademyPdfViewers();
    }
})();
</script>
HTML;
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
                        } elseif (!empty($data['title'])) {
                            $parts[] = strip_tags((string)$data['title']);
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
        return $this->sanitizer->sanitizeInlineHtml($html);
    }
}
