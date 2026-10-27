<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Renderer central: transforma um DTO de widget em HTML.
 * Único ponto no BuilderFlex responsável por serializar DTOs em HTML com sanitização contra XSS/CSS injection.
 */
class DtoRenderer
{
    private HtmlSanitizerInterface $htmlSanitizer;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

    public function render(array $dto): string
    {
        if (empty($dto)) {
            return '';
        }

        $tag = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($dto['tag'] ?? 'div')) ?: 'div';
        $attrs = $this->renderAttrs($dto['attrs'] ?? []);
        $styles = $this->renderStyles($dto['styles'] ?? []);
        $rawContent = (string)($dto['content'] ?? '');
        $content = $this->htmlSanitizer->sanitizeInlineHtml($rawContent);

        $childrenHtml = '';
        foreach ($dto['children'] ?? [] as $child) {
            if (is_array($child)) {
                $childrenHtml .= $this->render($child);
            }
        }

        $styleAttr = $styles !== '' ? ' style="' . htmlspecialchars($styles, ENT_QUOTES, 'UTF-8') . '"' : '';
        if (in_array(strtolower($tag), ['img', 'hr', 'br', 'input'], true)) {
            return "<{$tag}{$attrs}{$styleAttr} />";
        }

        return "<{$tag}{$attrs}{$styleAttr}>{$content}{$childrenHtml}</{$tag}>";
    }

    private function renderAttrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            if (is_array($val)) {
                $val = implode(' ', array_filter($val, fn($v) => $v !== null && $v !== ''));
            }
            if ($val === '') {
                continue;
            }
            $safeKey = preg_replace('/[^a-zA-Z0-9_:-]/', '', (string)$key);
            if ($safeKey === '' || str_starts_with(strtolower($safeKey), 'on')) {
                continue;
            }
            $strVal = (string)$val;
            if (in_array(strtolower($safeKey), ['href', 'src'], true)) {
                $strVal = $this->htmlSanitizer->sanitizeUrl($strVal, '#');
            }
            $out .= ' ' . $safeKey . '="' . htmlspecialchars($strVal, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $out;
    }

    private function renderStyles(array $styles): string
    {
        $parts = [];
        foreach ($styles as $prop => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            $safeProp = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$prop);
            $safeVal = $this->styleRenderer->sanitizeCssValue((string)$val);
            if ($safeProp === '' || $safeVal === '') {
                continue;
            }
            $parts[] = "{$safeProp}: {$safeVal}";
        }
        return implode('; ', $parts);
    }

    public function renderTree(array $tree): string
    {
        return implode('', array_map(fn($node) => is_array($node) ? $this->render($node) : '', $tree));
    }
}
