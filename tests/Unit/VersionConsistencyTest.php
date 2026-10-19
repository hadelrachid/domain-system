<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class VersionConsistencyTest extends TestCase
{
    public function testDsVersionInBootstrapMatchesComposerJsonVersion(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrapPath = $root . '/bootstrap.php';
        $composerPath = $root . '/composer.json';

        $this->assertFileExists($bootstrapPath);
        $this->assertFileExists($composerPath);

        $bootstrapSource = (string)file_get_contents($bootstrapPath);
        $matched = preg_match(
            "/define\\(\\s*['\"]DS_VERSION['\"]\\s*,\\s*['\"]([^'\"]+)['\"]\\s*\\)\\s*;/",
            $bootstrapSource,
            $matches
        );

        $this->assertSame(1, $matched, 'Constante DS_VERSION deve estar definida literalmente em bootstrap.php');
        $dsVersion = $matches[1];

        $composerData = json_decode((string)file_get_contents($composerPath), true);
        $this->assertIsArray($composerData, 'composer.json deve conter JSON válido');
        $this->assertArrayHasKey('version', $composerData, 'composer.json deve declarar o campo version');

        $this->assertSame(
            (string)$composerData['version'],
            $dsVersion,
            'DS_VERSION em bootstrap.php deve ser idêntica ao campo version em composer.json'
        );
    }
}
