<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Styles\Values;

/**
 * Unidades CSS suportadas pelo construtor visual com tipagem forte (SOLID - Ring 0).
 */
enum CssUnit: string
{
    // Absolutas / Pixels
    case PX = 'px';
    case CM = 'cm';
    case MM = 'mm';
    case PT = 'pt';
    case IN = 'in';

    // Relativas ao Container / Layout
    case PERCENT = '%';
    case AUTO = 'auto';
    case FIT_CONTENT = 'fit-content';
    case MIN_CONTENT = 'min-content';
    case MAX_CONTENT = 'max-content';

    // Relativas à Viewport (Tela)
    case VH = 'vh';
    case VW = 'vw';
    case VMIN = 'vmin';
    case VMAX = 'vmax';

    // Tipográficas / Escalares
    case REM = 'rem';
    case EM = 'em';
    case CH = 'ch';

    /**
     * Tenta identificar a unidade a partir de uma string (ex: "px", "%", "vh").
     */
    public static function tryFromString(string $raw): ?self
    {
        $clean = strtolower(trim($raw));
        return self::tryFrom($clean);
    }

    /**
     * Retorna se a unidade é de valor numérico dimensional.
     */
    public function isNumeric(): bool
    {
        return !in_array($this, [self::AUTO, self::FIT_CONTENT, self::MIN_CONTENT, self::MAX_CONTENT], true);
    }
}
