<?php

namespace DomainSystem\Plugins\seo\Contracts;

interface AssetMinifierInterface
{
    /**
     * Minifica código CSS bruto.
     */
    public function minifyCss(string $css): string;

    /**
     * Minifica código JavaScript bruto.
     */
    public function minifyJs(string $js): string;
}
