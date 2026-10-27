<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Subcontrato para partes ou seções que possuem grade de colunas e configurações de container (SOLID - ISP).
 */
interface LayoutContainerInterface
{
    /** Preset de grade de colunas (ex: '3_cols_header', '4_cols_footer', '1_col') */
    public function getLayoutPreset(): string;

    /** Configurações de container (boxed/full, gap, padding, margin, etc.) */
    public function getContainerSettings(): array;

    /**
     * Lista de blocos organizados por coluna (retrocompatibilidade com seção única).
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getColumnsData(): array;
}
