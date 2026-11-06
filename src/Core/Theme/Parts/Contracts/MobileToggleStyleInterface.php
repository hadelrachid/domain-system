<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Estilos Padronizados para Botões Toggle Hamburguer Mobile,
 * Janela Lateral (Drawer) e Botões de Navegação Mobile.
 */
interface MobileToggleStyleInterface
{
    // Estilos do Botão Hamburguer (Toggle)
    public const STYLE_CLASSIC      = 'classic';      // ☰ Tradicional 3 barras
    public const STYLE_PILL         = 'pill';         // Pílula com texto e ícone (ex: 'Menu ☰')
    public const STYLE_MINIMAL      = 'minimal';      // ═ 2 barras modernas
    public const STYLE_DOTS         = 'dots';         // ⋮ 3 pontinhos verticais modernos
    public const STYLE_BOXED        = 'boxed';        // ▣ Botão Sólido Preenchido
    public const STYLE_GRID         = 'grid';         // ⠿ Bento Grid (9 pontos)
    public const STYLE_ROUNDED_GLOW = 'rounded_glow'; // ◉ Circular Neon Glow
    public const STYLE_CYBER        = 'cyber';        // [☰] Tech Cyber com colchetes

    public const ALL_TOGGLE_STYLES = [
        self::STYLE_CLASSIC,
        self::STYLE_PILL,
        self::STYLE_MINIMAL,
        self::STYLE_DOTS,
        self::STYLE_BOXED,
        self::STYLE_GRID,
        self::STYLE_ROUNDED_GLOW,
        self::STYLE_CYBER,
    ];

    // Estilos/Formatos dos Botões de Menu dentro da Janela Mobile (Drawer)
    public const ITEM_STYLE_CARD      = 'card';      // Card com fundo suave e borda arredondada
    public const ITEM_STYLE_PILL      = 'pill';      // Botão Pílula (border-radius 999px)
    public const ITEM_STYLE_FILLED    = 'filled';    // Botão Sólido preenchido
    public const ITEM_STYLE_OUTLINED  = 'outlined';  // Botão com Borda Definida
    public const ITEM_STYLE_CYBER_BAR = 'cyber_bar'; // Barra Lateral Esquerda Neon (Accent Bar)
    public const ITEM_STYLE_DIVIDER   = 'divider';   // Lista separada por linhas divisórias
    public const ITEM_STYLE_MINIMAL   = 'minimal';   // Link limpo tradicional

    public const ALL_ITEM_STYLES = [
        self::ITEM_STYLE_CARD,
        self::ITEM_STYLE_PILL,
        self::ITEM_STYLE_FILLED,
        self::ITEM_STYLE_OUTLINED,
        self::ITEM_STYLE_CYBER_BAR,
        self::ITEM_STYLE_DIVIDER,
        self::ITEM_STYLE_MINIMAL,
    ];

    // Modos de Abertura / Posição da Janela Mobile (Drawer)
    public const DRAWER_POS_RIGHT      = 'right';      // Desliza da Direita (Padrão)
    public const DRAWER_POS_LEFT       = 'left';       // Desliza da Esquerda
    public const DRAWER_POS_TOP        = 'top';        // Cortina Superior (Dropdown)
    public const DRAWER_POS_FULLSCREEN = 'fullscreen'; // Modal Tela Cheia Centralizado
}
