<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface LessonBlockRendererInterface
{
    /**
     * Renderiza o conteúdo da aula (JSON de blocos ou HTML legado).
     */
    public function render(?string $content): string;

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function renderBlocks(array $blocks): string;

    /**
     * Extrai texto puro dos blocos para meta-description de SEO.
     */
    public function extractPlainText(?string $content): string;
}
