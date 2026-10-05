<?php

namespace DomainSystem\Tests\Architecture;

use PHPUnit\Framework\TestCase;

class NoSuperglobalsInControllersTest extends TestCase
{
    public function testControllersDoNotUseSessionSuperglobal()
    {
        $violationFound = false;
        $violatingFiles = [];

        $directories = [
            __DIR__ . '/../../src/SystemApps',
            __DIR__ . '/../../src/Plugins'
        ];

        foreach ($directories as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'Controllers') !== false) {
                    $content = file_get_contents($file->getPathname());
                    
                    if (preg_match('/\$_SESSION\b/', $content) || preg_match('/\$_POST\b/', $content) || preg_match('/\$_GET\b/', $content)) {
                        $violationFound = true;
                        $violatingFiles[] = $file->getFilename();
                    }
                }
            }
        }

        $this->assertFalse(
            $violationFound,
            "Architecture violation: Superglobals (\$_SESSION, \$_POST, \$_GET) found in controllers: " . implode(', ', $violatingFiles) . ". Use SessionManagerInterface and Request instead."
        );
    }
}
