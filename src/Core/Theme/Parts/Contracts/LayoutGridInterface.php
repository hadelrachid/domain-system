<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Motor de Grade e Divisórias (Estilo Elementor / CSS Grid / Flexbox).
 */
interface LayoutGridInterface
{
    /**
     * Retorna a lista de presets de colunas/divisórias disponíveis.
     * @return array<string, array<string, mixed>>
     */
    public function getPresets(): array;

    /**
     * Renderiza uma seção com a estrutura de colunas e blocos compilados.
     *
     * @param string $preset Identificador do preset (ex: '1_col', '2_cols_equal', '3_cols_header', '4_cols_footer').
     * @param array<int, string> $columnsHtml HTML compilado de cada coluna.
     * @param array<string, mixed> $settings Configurações do container (boxed/full, gap, align_items, padding).
     */
    public function renderSection(string $preset, array $columnsHtml, array $settings = []): string;
}
