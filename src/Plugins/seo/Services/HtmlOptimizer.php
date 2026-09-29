<?php

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\HtmlOptimizerInterface;

class HtmlOptimizer implements HtmlOptimizerInterface
{
    public function compressHtml(string $html): string
    {
        // 1. Remove HTML comments (except IE conditional comments)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // 2. Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // 3. Minify inline CSS and JS
        // (A more advanced implementation would extract <style> and <script> tags and run them through AssetMinifier)
        
        // 4. Remove multiple spaces
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
}
