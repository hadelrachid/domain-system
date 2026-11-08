<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\pages\Services;

use DomainSystem\Core\Utils\SlugHelper;

class PageSlugHelper
{
    public static function slugify(string $text): string
    {
        return SlugHelper::slugify($text, 'pagina');
    }
}
