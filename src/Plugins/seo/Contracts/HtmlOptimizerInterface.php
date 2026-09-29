<?php

namespace DomainSystem\Plugins\seo\Contracts;

interface HtmlOptimizerInterface
{
    /**
     * Otimiza/Minifica um buffer HTML removendo espaços brancos desnecessários
     * e comentários, reduzindo o tempo de download da página.
     */
    public function compressHtml(string $html): string;
}
