<?php

namespace DomainSystem\Tests\Architecture;

use PHPUnit\Framework\TestCase;

class NoBinariesTrackedTest extends TestCase
{
    public function testNoBinariesAreTrackedInGit()
    {
        exec('git ls-files', $output, $returnVar);
        
        $this->assertEquals(0, $returnVar, "Git command failed");

        $violations = [];
        foreach ($output as $file) {
            if (preg_match('/\.(phar|zip|sqlite|env)$/i', $file)) {
                $violations[] = $file;
            }
        }

        $this->assertEmpty(
            $violations, 
            "The following binaries or sensitive files are tracked in Git, which is a security/hygiene risk:\n" . implode("\n", $violations)
        );
    }
}
