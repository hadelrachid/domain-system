<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de compilação unificada de uma ThemePart (compartilhado entre Preview e Publicação).
 */
interface ThemePartCompilerInterface
{
    /**
     * Compila uma instância de ThemePart aplicando todas as regras de compatibilidade de blocos,
     * estilos de elemento, visibilidade responsiva e reajuste automático de CSS Grid.
     *
     * @param array<string, mixed> $context
     */
    public function compilePart(
        ThemePartInterface $part,
        array $context = [],
        bool $injectDesignTokens = true,
        bool $wrapWithSemanticTag = true
    ): string;
}
