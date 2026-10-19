<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class UpdateArchitectureTest extends TestCase
{
    public function testContractsUpdateContainsOnlyInterfaces(): void
    {
        $contractsDir = dirname(__DIR__, 2) . '/src/Core/Contracts/Update';
        $this->assertDirectoryExists($contractsDir);

        $files = glob($contractsDir . '/*.php') ?: [];
        $this->assertNotEmpty($files, 'src/Core/Contracts/Update deve conter os contratos do atualizador.');

        foreach ($files as $file) {
            $className = 'DomainSystem\\Core\\Contracts\\Update\\' . basename($file, '.php');
            $this->assertTrue(
                interface_exists($className),
                "Arquivo {$file} em Contracts/Update deve declarar exclusivamente a interface {$className}"
            );
            $ref = new ReflectionClass($className);
            $this->assertTrue($ref->isInterface(), "{$className} deve ser uma interface");
        }
    }

    public function testUpdateServicesAndSystemUpdaterDoNotContainForbiddenInfrastructureOrSuperglobals(): void
    {
        $root = dirname(__DIR__, 2);
        $directories = [
            $root . '/src/Core/Update/Service',
            $root . '/src/SystemApps/SystemUpdater',
        ];

        $forbiddenTokens = [
            'PDO',
            'sqlite',
            'mysql',
            'curl_',
            'ZipArchive',
            'file_put_contents',
            'unlink(',
            'rename(',
            'exec(',
            '$_SESSION',
            '$_GET',
            '$_POST',
            '$_SERVER',
            'new Curl',
            'new Zip',
            'new Pdo',
        ];

        $violations = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $raw = (string)file_get_contents($file->getPathname());
                $stripped = $this->stripComments($raw);

                foreach ($forbiddenTokens as $token) {
                    if (stripos($stripped, $token) !== false) {
                        $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                        $violations[] = "{$relPath} contém token proibido '{$token}'";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Violações de arquitetura Ring 0 / Contratos no atualizador:\n" . implode("\n", $violations)
        );
    }

    public function testValueObjectsDoNotImportInfrastructureOrDatabase(): void
    {
        $root = dirname(__DIR__, 2);
        $valueDir = $root . '/src/Core/Update/Value';
        $this->assertDirectoryExists($valueDir);

        $files = glob($valueDir . '/*.php') ?: [];
        $this->assertNotEmpty($files);

        $violations = [];
        foreach ($files as $file) {
            $raw = (string)file_get_contents($file);
            $stripped = $this->stripComments($raw);

            if (
                preg_match('/\\\\Infrastructure\\\\/i', $stripped) === 1
                || preg_match('/\\\\Database\\\\/i', $stripped) === 1
            ) {
                $relPath = str_replace('\\', '/', substr($file, strlen($root) + 1));
                $violations[] = "{$relPath} importa camada de Infrastructure ou Database";
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    private function stripComments(string $source): string
    {
        $tokens = token_get_all($source);
        $output = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $output .= $token[1];
            } else {
                $output .= $token;
            }
        }
        return $output;
    }
}
