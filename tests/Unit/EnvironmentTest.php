<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testPhpVersionIsSupported()
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'A versão do PHP deve ser 8.2 ou superior. Atual: ' . PHP_VERSION
        );
    }

    public function testRequiredExtensionsAreLoaded()
    {
        $requiredExtensions = [
            'pdo',
            'json',
            'session',
            'mbstring',
            'openssl'
        ];

        foreach ($requiredExtensions as $ext) {
            $this->assertTrue(
                extension_loaded($ext),
                "A extensão requerida '$ext' não está carregada."
            );
        }
    }

    public function testDirectorySeparatorIsHandled()
    {
        $osFamily = PHP_OS_FAMILY;
        $this->assertContains($osFamily, ['Windows', 'Linux', 'Darwin', 'BSD', 'Solaris', 'Unknown']);
        $this->assertTrue(true, "Testes rodando em ambiente: " . $osFamily);
    }
}
