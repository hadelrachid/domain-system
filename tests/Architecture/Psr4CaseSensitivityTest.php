<?php

namespace DomainSystem\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Compatibilidade Windows -> Linux (Hostinger).
 *
 * O Windows ignora maiúsculas/minúsculas em caminhos; o Linux não. O autoload
 * PSR-4 monta o caminho a partir do namespace, então o caminho REAL do arquivo
 * precisa bater EXATAMENTE (inclusive caixa) com namespace + nome da classe.
 * Este teste compara strings (não depende do sistema de arquivos), por isso
 * detecta o problema mesmo rodando no Windows.
 */
class Psr4CaseSensitivityTest extends TestCase
{
    public function testClassPathsMatchNamespacesExactlyIncludingCase(): void
    {
        $srcRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../src'));
        $mismatches = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if (!preg_match('/^namespace\s+([^;]+);/m', $code, $ns)
                || !preg_match('/^(?:final\s+|abstract\s+)*(?:class|interface|trait|enum)\s+(\w+)/m', $code, $cls)) {
                continue;
            }

            $fqcn = trim($ns[1]) . '\\' . $cls[1];
            if (!str_starts_with($fqcn, 'DomainSystem\\')) {
                continue;
            }

            $expected = str_replace('\\', '/', substr($fqcn, strlen('DomainSystem\\'))) . '.php';
            $actual = substr(str_replace('\\', '/', $file->getPathname()), strlen($srcRoot) + 1);

            // Só é problema quando difere APENAS na caixa das letras.
            if ($expected !== $actual && strcasecmp($expected, $actual) === 0) {
                $mismatches[] = "$actual  (esperado pelo namespace: $expected)";
            }
        }

        $this->assertSame(
            [],
            $mismatches,
            "Arquivos que quebram no Linux por diferença de maiúsculas/minúsculas:\n" . implode("\n", $mismatches)
        );
    }
}
