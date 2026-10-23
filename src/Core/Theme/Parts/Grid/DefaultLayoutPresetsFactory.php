<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetRegistryInterface;

/**
 * Fábrica de Presets Padrão do Flex Builder (SOLID - SRP / OCP).
 */
class DefaultLayoutPresetsFactory
{
    public static function create(): LayoutPresetRegistryInterface
    {
        $registry = new LayoutPresetRegistry();

        // Presets Estruturais Clássicos
        $registry->register(new LayoutPreset(
            '1_col',
            '1 Coluna (100%)',
            1,
            '1fr',
            '1fr',
            '1fr',
            'structural',
            '⬜'
        ));

        $registry->register(new LayoutPreset(
            '2_cols_equal',
            '2 Colunas Iguais (50% / 50%)',
            2,
            '1fr 1fr',
            '1fr 1fr',
            '1fr',
            'structural',
            '🌗'
        ));

        $registry->register(new LayoutPreset(
            '2_cols_split',
            '2 Colunas Pro (30% / 70%)',
            2,
            'minmax(220px, 1fr) 2.5fr',
            '1fr 1fr',
            '1fr',
            'structural',
            '🌘'
        ));

        $registry->register(new LayoutPreset(
            '3_cols_header',
            '3 Colunas Header Pro (25% / 50% / 25%)',
            3,
            '1fr 2fr 1fr',
            '1fr 1fr',
            '1fr',
            'header',
            '🧭'
        ));

        $registry->register(new LayoutPreset(
            '3_cols_equal',
            '3 Colunas Iguais (33% / 33% / 33%)',
            3,
            '1fr 1fr 1fr',
            '1fr 1fr',
            '1fr',
            'structural',
            '🧱'
        ));

        $registry->register(new LayoutPreset(
            '4_cols_footer',
            '4 Colunas Footer Pro (25% cada)',
            4,
            '1fr 1fr 1fr 1fr',
            '1fr 1fr',
            '1fr',
            'footer',
            '👣'
        ));

        // NOVOS PRESETS RESPONSIVOS DE CONTEÚDO E CARDS
        $registry->register(new LayoutPreset(
            'cards_grid_4',
            'Grid de Cards / Blog (4 Colunas)',
            4,
            'repeat(4, 1fr)',
            'repeat(2, 1fr)',
            '1fr',
            'grid',
            '📰'
        ));

        $registry->register(new LayoutPreset(
            'cards_grid_3',
            'Grid de Destaques / Planos (3 Colunas)',
            3,
            'repeat(3, 1fr)',
            'repeat(2, 1fr)',
            '1fr',
            'grid',
            '🗂️'
        ));

        $registry->register(new LayoutPreset(
            'content_with_sidebar',
            'Conteúdo com Barra Lateral (70% / 30%)',
            2,
            'minmax(0, 1fr) 320px',
            '1fr 1fr',
            '1fr',
            'content',
            '📑'
        ));

        $registry->register(new LayoutPreset(
            'hero_split',
            'Hero Split de Conversão (60% / 40%)',
            2,
            '1.2fr 0.8fr',
            '1fr',
            '1fr',
            'hero',
            '⚡'
        ));

        return $registry;
    }
}
