<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

/**
 * Renderer central: transforma um DTO de widget em HTML.
 * Único ponto no BuilderFlex responsável por serializar DTOs em HTML.
 */
class DtoRenderer
{
    public function render(array $dto): string
    {
        if (empty($dto)) {
            return '';
        }

        $tag = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($dto['tag'] ?? 'div')) ?: 'div';
        $attrs = $this->renderAttrs($dto['attrs'] ?? []);
        $styles = $this->renderStyles($dto['styles'] ?? []);
        $content = (string)($dto['content'] ?? '');

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
            if ($safeKey === '') {
                continue;
            }
            $out .= ' ' . $safeKey . '="' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '"';
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
            $parts[] = "{$prop}: {$val}";
        }
        return implode('; ', $parts);
    }

    public function renderTree(array $tree): string
    {
        return implode('', array_map(fn($node) => is_array($node) ? $this->render($node) : '', $tree));
    }
}
