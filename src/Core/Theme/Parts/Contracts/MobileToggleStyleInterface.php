<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Estilos Padronizados para Botões Toggle Hamburguer Mobile.
 */
interface MobileToggleStyleInterface
{
    public const STYLE_CLASSIC = 'classic'; // ☰ Tradicional 3 barras
    public const STYLE_PILL    = 'pill';    // Pílula com texto e ícone (ex: 'Menu ☰')
    public const STYLE_MINIMAL = 'minimal'; // 2 barras modernas com animação para '✕'
    public const STYLE_DOTS    = 'dots';    // 3 pontinhos verticais modernos (⋮)

    /**
     * Retorna a lista de estilos de toggle mobile válidos e suportados.
     * @return string[]
     */
    public static function getAvailableStyles(): array;
}
