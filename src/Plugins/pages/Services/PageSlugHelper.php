<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\pages\Services;

class PageSlugHelper
{
    private static array $accentMap = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a',
        'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
        'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
        'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o',
        'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u',
        'Ç'=>'c','Ñ'=>'n'
    ];

    public static function slugify(string $text): string
    {
        $text = strtr($text, self::$accentMap);

        if (function_exists('transliterator_transliterate')) {
            $trans = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if ($trans !== false) {
                $text = $trans;
            }
        }

        $clean = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
        return !empty($clean) ? $clean : 'pagina';
    }
}
