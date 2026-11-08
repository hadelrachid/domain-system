<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Core;

use DomainSystem\Core\Utils\SlugHelper;
use PHPUnit\Framework\TestCase;

class SlugHelperTest extends TestCase
{
    public function testSlugifyConvertsAccentsAndSpecialCharacters(): void
    {
        $input = 'Programação Avançada em PHP & SOLID: Boas Práticas!';
        $expected = 'programacao-avancada-em-php-solid-boas-praticas';

        $this->assertSame($expected, SlugHelper::slugify($input));
    }

    public function testSlugifyReturnsFallbackWhenEmpty(): void
    {
        $this->assertSame('item', SlugHelper::slugify(''));
        $this->assertSame('pagina', SlugHelper::slugify('   ', 'pagina'));
        $this->assertSame('aula-sem-titulo', SlugHelper::slugify('???@@@###', 'aula-sem-titulo'));
    }

    public function testSlugifyTrimsHyphens(): void
    {
        $this->assertSame('modulo-1', SlugHelper::slugify('--modulo--1--'));
    }
}
