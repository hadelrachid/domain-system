<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Helpers;

/**
 * Renderizador de bandeiras vetoriais (SVG inline).
 *
 * No sistema operacional Windows (incluindo Windows 10 e 11), a fonte do sistema (Segoe UI Emoji)
 * NÃO renderiza os emojis de bandeiras de países (como 🇧🇷 e 🇨🇳), exibindo no lugar apenas blocos
 * de texto "BR" e "CN".
 *
 * Este Helper gera SVGs vetoriais leves, precisos e independentes de CDN ou fontes externas,
 * garantindo que as bandeiras do Brasil e de outros países apareçam com perfeição em qualquer
 * computador, celular ou navegador.
 */
class FlagHelper
{
    /**
     * Retorna a bandeira oficial do Brasil em vetor SVG nítido.
     */
    public static function br(int $width = 16, int $height = 11, string $extraStyle = ''): string
    {
        $style = 'display: inline-block; vertical-align: -1.5px; border-radius: 2px; box-shadow: 0 0 2px rgba(0,0,0,0.4); margin-right: 4px;' . ($extraStyle !== '' ? ' ' . $extraStyle : '');
        return '<svg viewBox="0 0 72 50" width="' . $width . '" height="' . $height . '" style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '" xmlns="http://www.w3.org/2000/svg" aria-label="Bandeira do Brasil" role="img">'
            . '<rect width="72" height="50" fill="#009b3a"/>'
            . '<polygon points="36,6 66,25 36,44 6,25" fill="#fedf00"/>'
            . '<circle cx="36" cy="25" r="11" fill="#002776"/>'
            . '<path d="M26,27 Q36,20 46,24" stroke="#ffffff" stroke-width="1.8" fill="none"/>'
            . '</svg>';
    }

    /**
     * Retorna a bandeira oficial da China em vetor SVG nítido.
     */
    public static function cn(int $width = 16, int $height = 11, string $extraStyle = ''): string
    {
        $style = 'display: inline-block; vertical-align: -1.5px; border-radius: 2px; box-shadow: 0 0 2px rgba(0,0,0,0.4); margin-right: 4px;' . ($extraStyle !== '' ? ' ' . $extraStyle : '');
        return '<svg viewBox="0 0 72 50" width="' . $width . '" height="' . $height . '" style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '" xmlns="http://www.w3.org/2000/svg" aria-label="Bandeira da China" role="img">'
            . '<rect width="72" height="50" fill="#de2910"/>'
            . '<polygon points="12,6 15,15 7,9 17,9 9,15" fill="#ffde00"/>'
            . '<polygon points="23,5 24,7 22,6 25,6 23,7" fill="#ffde00"/>'
            . '<polygon points="27,10 28,12 26,11 29,11 27,12" fill="#ffde00"/>'
            . '<polygon points="27,16 28,18 26,17 29,17 27,18" fill="#ffde00"/>'
            . '<polygon points="23,21 24,23 22,22 25,22 23,23" fill="#ffde00"/>'
            . '</svg>';
    }

    /**
     * Renderizador genérico por código ISO ou nome.
     */
    public static function render(string $countryCode, int $width = 16, int $height = 11, string $extraStyle = ''): string
    {
        $code = strtolower(trim($countryCode));
        return match ($code) {
            'br', 'brasil', 'brazil' => self::br($width, $height, $extraStyle),
            'cn', 'china' => self::cn($width, $height, $extraStyle),
            default => '',
        };
    }
}
