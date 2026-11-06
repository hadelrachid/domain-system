<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato de resolução segura de diretórios físicos de temas (SOLID - DIP).
 */
interface ThemePathResolverInterface
{
    /**
     * Resolve o caminho absoluto do tema solicitado, com fallback resiliente.
     */
    public function resolveThemePath(string $requestedTheme): string;

    /**
     * Verifica se um tema específico existe fisicamente nos diretórios configurados.
     */
    public function themeExists(string $themeName): bool;
}
