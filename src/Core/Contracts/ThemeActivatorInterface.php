<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato para ativação e alternância dinâmica de temas no sistema (estilo WordPress).
 */
interface ThemeActivatorInterface
{
    /**
     * Ativa um tema específico no sistema.
     *
     * @param string $themeFolder Nome da pasta do tema a ser ativado
     * @return array{success: bool, message: string, theme?: string}
     */
    public function activate(string $themeFolder): array;

    /**
     * Retorna o identificador da pasta do tema atualmente ativo.
     */
    public function getActiveTheme(): string;
}
