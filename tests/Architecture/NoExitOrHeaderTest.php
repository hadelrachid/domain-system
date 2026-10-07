<?php
namespace DomainSystem\Tests\Architecture;

use PHPUnit\Framework\TestCase;

class NoExitOrHeaderTest extends TestCase
{
    public function testControllersDoNotUseHeaderOrExit()
    {
        $baseDir = DOMAIN_SYSTEM_ROOT . '/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
        
        $violatingFiles = [];
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'Controllers') !== false) {
                $content = file_get_contents($file->getPathname());
                
                // Matches header("Location: ...") or exit; or die;
                // Note: we ignore whitespace
                if (preg_match('/\bheader\s*\(\s*["\']Location:/i', $content) || preg_match('/\b(?:exit|die)\s*[\(;]/', $content)) {
                    $violatingFiles[] = $file->getPathname();
                }
            }
        }
        
        $this->assertEmpty(
            $violatingFiles, 
            "Os seguintes controllers usam header() ou exit()/die() em vez de retornar Response::redirect():\n" . implode("\n", $violatingFiles)
        );
    }
}
