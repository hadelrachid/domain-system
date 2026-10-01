<?php

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\HtmlOptimizerInterface;

class HtmlOptimizer implements HtmlOptimizerInterface
{
    public function compressHtml(string $html): string
    {
        // Arrays para guardar conteúdos protegidos
        $protected = [];
        $i = 0;

        // Função auxiliar para proteger
        $protect = function($matches) use (&$protected, &$i) {
            $placeholder = "@@PROTECTED_{$i}@@";
            $protected[$placeholder] = $matches[0];
            $i++;
            return $placeholder;
        };

        // 1. Proteger <pre>, <code>, <script>, <style>, <textarea>
        $html = preg_replace_callback('/<(pre|code|script|style|textarea)\b[^>]*>.*?<\/\1>/is', $protect, $html);

        // 2. Remove HTML comments (except IE conditional comments)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // 3. Converte todas as quebras de linha e múltiplos espaços em um único espaço.
        // Isso preserva um espaço "seguro" entre tags inline como <strong> e <span>, 
        // e reduz o tamanho do HTML ao máximo sem quebrar layout.
        $html = preg_replace('/\s+/', ' ', $html);

        // 4. Restaurar conteúdos protegidos
        foreach ($protected as $placeholder => $originalContent) {
            $html = str_replace($placeholder, $originalContent, $html);
        }
        
        return trim($html);
    }
}
