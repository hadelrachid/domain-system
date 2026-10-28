<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Support;

use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;

/**
 * Sanitizador de HTML inline contra XSS (atributos on*, javascript:, vbscript:, data:).
 */
class SafeHtmlSanitizer implements HtmlSanitizerInterface
{
    private const ALLOWED_TAGS = '<strong><b><em><i><u><a><code><br><span>';

    public function sanitizeInlineHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // 1. Mantém apenas a whitelist de tags inline
        $stripped = strip_tags($html, self::ALLOWED_TAGS);

        // 2. Sanitiza atributos dentro de cada tag sobrevivente
        $sanitized = preg_replace_callback(
            '/<(\/?)([a-zA-Z0-9]+)([^>]*)>/s',
            function (array $matches): string {
                $closing = $matches[1];
                $tag = strtolower($matches[2]);
                $rawAttrs = $matches[3];

                if ($closing === '/') {
                    return "</{$tag}>";
                }

                if ($tag === 'br') {
                    return '<br>';
                }

                $safeAttrs = $this->sanitizeAttributes($tag, $rawAttrs);
                return $safeAttrs !== '' ? "<{$tag} {$safeAttrs}>" : "<{$tag}>";
            },
            $stripped
        );

        return $sanitized ?? '';
    }

    private function sanitizeAttributes(string $tag, string $rawAttrs): string
    {
        if (trim($rawAttrs) === '') {
            return '';
        }

        $cleanPairs = [];
        // Captura pares nome="valor", nome='valor' ou nome=valor
        if (preg_match_all('/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/s', $rawAttrs, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $attr) {
                $name = strtolower($attr[1]);
                $value = $attr[2] !== '' ? $attr[2] : ($attr[3] !== '' ? $attr[3] : ($attr[4] ?? ''));

                // Bloqueia incondicionalmente qualquer evento inline (onclick, onmouseover, onerror, etc.)
                if (str_starts_with($name, 'on')) {
                    continue;
                }

                if ($tag === 'a' && in_array($name, ['href', 'target', 'rel', 'title', 'class'], true)) {
                    if ($name === 'href') {
                        if (!$this->isSafeUrl($value)) {
                            continue;
                        }
                    }
                    $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    $cleanPairs[] = "{$name}=\"{$escaped}\"";
                } elseif (in_array($name, ['class', 'title'], true)) {
                    $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    $cleanPairs[] = "{$name}=\"{$escaped}\"";
                } elseif ($name === 'style') {
                    $safeStyle = $this->sanitizeInlineStyle($value);
                    if ($safeStyle !== '') {
                        $escaped = htmlspecialchars($safeStyle, ENT_QUOTES, 'UTF-8');
                        $cleanPairs[] = "style=\"{$escaped}\"";
                    }
                }
            }
        }

        return implode(' ', $cleanPairs);
    }

    public function sanitizeUrl(string $url, string $fallback = '#'): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return $fallback;
        }

        if (!$this->isSafeUrl($trimmed)) {
            return $fallback;
        }

        return $trimmed;
    }

    private function isSafeUrl(string $url): bool
    {
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove caracteres de controle e espaços invisíveis
        $normalized = trim(preg_replace('/[\x00-\x20]+/', '', $decoded) ?? '');
        if ($normalized === '') {
            return false;
        }

        $lower = strtolower($normalized);

        // Bloqueia URLs protocol-relative (//dominio-externo) ou com barras invertidas de evasão
        if (str_starts_with($lower, '//') || str_contains($lower, '\\')) {
            return false;
        }

        // Se possuir esquema explícito (ex: http:, https:, mailto:, javascript:, data:, file:), exige whitelist positiva
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $lower, $matches)) {
            $scheme = strtolower($matches[1]);
            if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                return false;
            }
            if (in_array($scheme, ['http', 'https'], true) && !preg_match('/^https?:\/\/[a-z0-9]/i', $normalized)) {
                return false;
            }
            return true;
        }

        // Se contiver dois-pontos antes de qualquer '/', '?' ou '#' (tentativa de esquema ofuscado), bloqueia
        $firstColon = strpos($lower, ':');
        if ($firstColon !== false) {
            $firstSlash = strpos($lower, '/');
            $firstQuery = strpos($lower, '?');
            $firstHash  = strpos($lower, '#');
            $firstDelimiter = min(array_filter([$firstSlash, $firstQuery, $firstHash], static fn($v) => $v !== false) ?: [PHP_INT_MAX]);
            if ($firstColon < $firstDelimiter) {
                return false;
            }
        }

        return true;
    }

    private function sanitizeInlineStyle(string $style): string
    {
        $decoded = strtolower(html_entity_decode($style, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (
            str_contains($decoded, 'expression(') ||
            str_contains($decoded, 'javascript:') ||
            str_contains($decoded, 'vbscript:') ||
            str_contains($decoded, 'url(') ||
            str_contains($decoded, '@import')
        ) {
            return '';
        }

        return preg_replace('/[<>\\\\{}]/', '', trim($style)) ?? '';
    }
}
