<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Orquestrador de Renderização de Partes com Garantia de Fallback Gracioso.
 */
interface ThemePartRendererInterface
{
    /**
     * Renderiza o Header ativo do tema.
     * Se houver parte dinâmica configurada, compila os blocos com o motor de grade.
     * Caso contrário, faz fallback seguro para o arquivo partials/header.php do tema.
     */
    public function renderHeader(string $themeFolder, array $context = []): string;

    /**
     * Renderiza o Footer ativo do tema com mesma garantia de fallback.
     */
    public function renderFooter(string $themeFolder, array $context = []): string;

    /**
     * Renderiza uma seção ou bloco central específico.
     */
    public function renderSection(string $sectionId, array $context = []): string;
}
