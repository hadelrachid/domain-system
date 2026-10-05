<?php

namespace DomainSystem\Tests\Architecture;

use DomainSystem\Tests\Support\UndefinedVariableScanner;
use PHPUnit\Framework\TestCase;

/**
 * Garante que nenhum método/closure do sistema leia variáveis que nunca foram
 * declaradas. Pega os bugs "Undefined variable $runtime" (closure aninhado sem
 * use) e "Undefined variable $session" (usado sem parâmetro no construtor),
 * que antes só apareciam em tempo de execução, no navegador.
 */
class NoUndefinedVariablesTest extends TestCase
{
    // ---------- Provas de que o scanner funciona (bugs reais de hoje) ----------

    public function testScannerDetectsNestedClosureMissingUse(): void
    {
        $code = <<<'PHP'
<?php
class P {
    public function osBoot($runtime): void {
        $runtime->onHook('x', function($sm) use ($runtime) {
            $sm->add('nav', function($attrs) {
                return $runtime->make('Y');
            });
        });
    }
}
PHP;
        $vars = array_column(UndefinedVariableScanner::scan($code), 'var');
        $this->assertSame(['runtime'], $vars);
    }

    public function testScannerDetectsConstructorUsingUndeclaredParameter(): void
    {
        $code = <<<'PHP'
<?php
class C {
    public function __construct($theme) {
        $this->theme = $theme;
        $this->session = $session;
    }
}
PHP;
        $vars = array_column(UndefinedVariableScanner::scan($code), 'var');
        $this->assertSame(['session'], $vars);
    }

    public function testScannerAcceptsValidPatterns(): void
    {
        $code = <<<'PHP'
<?php
class OK {
    public function __construct(private $a, $b = 1) {}
    public function run($request, &$out) {
        $x = 1; $arr = [];
        $arr['k'][] = $x;
        foreach ($arr as $k => $v) { echo $k, $v; }
        try { throw new \Exception(); } catch (\Exception $e) { echo $e; }
        preg_match('/a/', 'a', $m); echo $m[0];
        [$p, $q] = [1, 2]; echo $p, $q;
        $fn = fn($z) => $z + $x;
        $cb = function($n) use ($x) { return $n + $x; };
        if (isset($maybe) && !empty($nothing)) {}
        $y = $unset ?? 5;
        static $cache; echo $cache;
        echo "Valor: $x {$y}";
        $out = $request;
    }
}
PHP;
        $this->assertSame([], UndefinedVariableScanner::scan($code));
    }

    // ---------- Varredura real do código-fonte ----------

    public function testNoUndefinedVariablesInSource(): void
    {
        $root = realpath(__DIR__ . '/../../src');
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/views/')) {
                continue; // templates usam variáveis injetadas via extract()
            }
            foreach (UndefinedVariableScanner::scan(file_get_contents($file->getPathname())) as $f) {
                $violations[] = substr($path, strlen(str_replace('\\', '/', $root)) + 1) . ':' . $f['line'] . '  $' . $f['var'];
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Variáveis indefinidas detectadas:\n" . implode("\n", $violations)
        );
    }
}
