<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface;

class NativeAssetMinifier implements AssetMinifierInterface
{
    public function minifyCss(string $css): string
    {
        // 1. Remove comentários CSS /* ... */
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // 2. Remove espaços em branco redundantes ao redor de delimitadores e seletores
        $css = preg_replace('/\s*([{}|:;,>+~])\s*/', '$1', $css);
        // 3. Remove ponto e vírgula antes de fechar chaves
        $css = str_replace(';}', '}', $css);
        // 4. Colapsa espaços múltiplos em um único espaço
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }

    public function minifyJs(string $js): string
    {
        // 1. Remove comentários multi-linhas /* ... */
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        // 2. Processa linha a linha de forma segura, removendo indentação e linhas vazias
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $js));
        $cleaned = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && !str_starts_with($trimmed, '//')) {
                $trimmed = preg_replace('/(?<!:|\'|"|\/)\/\/.*$/', '', $trimmed);
                $cleaned[] = trim($trimmed);
            }
        }
        return implode("\n", $cleaned);
    }
}
