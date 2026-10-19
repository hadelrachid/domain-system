<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface CourseCatalogRendererInterface
{
    /**
     * Renderiza a grade de cursos publicados/em breve para o catálogo ou shortcode [hub_de_cursos].
     *
     * @param array<string, mixed> $attrs
     */
    public function render(array $attrs = []): string;
}
