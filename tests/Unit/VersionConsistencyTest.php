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

    public function testAllOfficialPluginManifestsMatchCoreVersion(): void
    {
        $root = dirname(__DIR__, 2);
        $composerData = json_decode((string)file_get_contents($root . '/composer.json'), true);
        $this->assertIsArray($composerData);
        $expectedVersion = (string)$composerData['version'];
        $versionParts = explode('.', $expectedVersion);
        $expectedOsVersion = $versionParts[0] . '.' . ($versionParts[1] ?? '0');

        $manifestFiles = array_merge(
            glob($root . '/src/Plugins/*/plugin.json') ?: [],
            glob($root . '/src/Plugins/*/*/plugin.json') ?: [],
            glob($root . '/src/SystemApps/*/plugin.json') ?: []
        );

        $this->assertNotEmpty($manifestFiles, 'Devem existir manifestos plugin.json em src/Plugins e src/SystemApps');

        foreach ($manifestFiles as $file) {
            $relPath = str_replace('\\', '/', substr($file, strlen($root) + 1));
            $data = json_decode((string)file_get_contents($file), true);
            $this->assertIsArray($data, "JSON inválido em {$relPath}");
            $this->assertSame(
                $expectedVersion,
                $data['version'] ?? null,
                "Campo 'version' em {$relPath} deve estar padronizado em {$expectedVersion}"
            );
            $this->assertSame(
                $expectedOsVersion,
                $data['os_version'] ?? null,
                "Campo 'os_version' em {$relPath} deve estar padronizado em {$expectedOsVersion}"
            );
        }
    }
}

