<?php

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface;

class NativeAssetMinifier implements AssetMinifierInterface
{
    public function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        return $css;
    }

    public function minifyJs(string $js): string
    {
        // Simple JS minification (for demonstration, in real life we might use a better parser)
        // Remove multi-line comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        // Remove single line comments
        // Note: this simple regex can break if there's a // inside a string.
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $js);
        // Remove whitespace
        $js = str_replace(array("\r\n", "\r", "\n", "\t"), '', $js);
        // Remove multiple spaces
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }
}
