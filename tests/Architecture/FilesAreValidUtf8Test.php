<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Garante que todo arquivo PHP do projeto seja UTF-8 valido.
 * Bytes invalidos (ex.: "Configuraes" no lugar de "Configuracoes") quebram menus e JSON.
 */
final class FilesAreValidUtf8Test extends TestCase
{
    public function test_all_php_sources_are_valid_utf8(): void
    {
        $root = dirname(__DIR__, 2);
        $invalid = [];

        foreach (['src', 'themes'] as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                if (!mb_check_encoding((string) file_get_contents($file->getPathname()), 'UTF-8')) {
                    $invalid[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $invalid, "Arquivos com bytes UTF-8 invalidos:\n" . implode("\n", $invalid));
    }
}
