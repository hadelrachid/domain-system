<?php

namespace DomainSystem\Tests\Architecture;

use DomainSystem\Tests\Support\UnresolvedClassScanner;
use PHPUnit\Framework\TestCase;

/**
 * Garante que todo nome de classe usado no src/ é resolvível (import, mesmo
 * arquivo ou mesmo namespace). Pega erros como
 * `Class "...\Controllers\ViewResponse" not found`, causados por esquecer o `use`.
 */
class NoUnresolvedClassNamesTest extends TestCase
{
    private string $srcRoot;

    protected function setUp(): void
    {
        $this->srcRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../src'));
    }

    public function testScannerDetectsMissingImport(): void
    {
        $code = <<<'PHP'
<?php
namespace DomainSystem\Plugins\settings\Controllers;
class C {
    public function index() {
        return new ViewResponse('x', false);
    }
}
PHP;
        $found = array_column(UnresolvedClassScanner::scan($code, $this->srcRoot), 'name');
        $this->assertSame(['ViewResponse'], $found);
    }

    public function testScannerAcceptsImportedDeclaredAndSameNamespaceClasses(): void
    {
        $code = <<<'PHP'
<?php
namespace DomainSystem\Core\Http;
use DomainSystem\Core\Http\Responses\ViewResponse;
use Exception as Ex;
class Local {}
class C {
    public function a(Request $r, Local $l, int $n, ?Ex $e = null) {
        $x = new ViewResponse('x');
        $y = new Local();
        return self::class . static::class . parent::class;
    }
}
PHP;
        $this->assertSame([], UnresolvedClassScanner::scan($code, $this->srcRoot));
    }

    public function testNoUnresolvedClassNamesInSource(): void
    {
        $violations = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->srcRoot, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/views/')) {
                continue;
            }
            foreach (UnresolvedClassScanner::scan(file_get_contents($file->getPathname()), $this->srcRoot) as $f) {
                $violations[] = substr($path, strlen($this->srcRoot) + 1) . ':' . $f['line'] . '  ' . $f['name'];
            }
        }

        $this->assertSame([], $violations, "Classes usadas sem import:\n" . implode("\n", $violations));
    }
}
