<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Service\SemverComparator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SemverComparatorTest extends TestCase
{
    #[DataProvider('validVersionsProvider')]
    public function testIsValidAcceptsStrictSemver(string $version): void
    {
        $comparator = new SemverComparator();
        $this->assertTrue($comparator->isValid($version));
    }

    /**
     * @return list<array{0: string}>
     */
    public static function validVersionsProvider(): array
    {
        return [
            ['0.0.1'],
            ['2.3.0'],
            ['2.10.15'],
            ['2.4.0-alpha'],
            ['2.4.0-beta.1'],
            ['2.4.0-rc.1.2'],
        ];
    }

    #[DataProvider('invalidVersionsProvider')]
    public function testIsValidRejectsInvalidSemver(string $version): void
    {
        $comparator = new SemverComparator();
        $this->assertFalse($comparator->isValid($version));
    }

    /**
     * @return list<array{0: string}>
     */
    public static function invalidVersionsProvider(): array
    {
        return [
            [''],
            ['2.3'],
            ['abc'],
            ['2.3.0.1'],
            ['v2.3.0'], // Prefixo 'v' só é aceito em normalize()
            ['02.3.0'],
            ['2.03.0'],
            ['2.3.0-'],
        ];
    }

    public function testNormalizeStripsLeadingVAndValidates(): void
    {
        $comparator = new SemverComparator();

        $this->assertSame('2.3.0', $comparator->normalize('v2.3.0'));
        $this->assertSame('2.4.0-beta.1', $comparator->normalize('V2.4.0-beta.1'));
        $this->assertSame('2.3.0', $comparator->normalize('2.3.0'));

        $this->expectException(InvalidArgumentException::class);
        $comparator->normalize('v2.3');
    }

    #[DataProvider('comparisonsProvider')]
    public function testCompareAndIsNewer(string $a, string $b, int $expected): void
    {
        $comparator = new SemverComparator();

        $this->assertSame($expected, $comparator->compare($a, $b));
        $this->assertSame($expected === 1, $comparator->isNewer($a, $b));
    }

    /**
     * @return list<array{0: string, 1: string, 2: int}>
     */
    public static function comparisonsProvider(): array
    {
        return [
            ['2.3.0', '2.3.0', 0],
            ['2.4.0', '2.3.0', 1],
            ['2.3.0', '2.4.0', -1],
            ['2.10.0', '2.9.9', 1],
            ['2.4.0-beta.1', '2.4.0', -1],
            ['2.4.0', '2.4.0-beta.1', 1],
            ['2.4.0-beta.2', '2.4.0-beta.1', 1],
            ['2.4.0-beta.10', '2.4.0-beta.2', 1],
            ['2.4.0-alpha', '2.4.0-beta', -1],
            ['3.0.0-alpha.1', '2.9.9', 1],
        ];
    }
}
