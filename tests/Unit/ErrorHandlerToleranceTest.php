<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Error\ErrorHandler;
use DomainSystem\Core\Error\ErrorLogger;
use DomainSystem\Core\Error\CircuitBreaker;
use DomainSystem\Core\Error\ErrorRenderer;

class ErrorHandlerToleranceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_cb_test_' . uniqid();
        @mkdir($this->tempDir . '/config', 0777, true);
        @mkdir($this->tempDir . '/temp', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $p = $dir . '/' . $file;
            is_dir($p) ? $this->removeDirectory($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    public function testNonFatalWarningDoesNotRenderErrorInProduction(): void
    {
        $logger = $this->createMock(ErrorLogger::class);
        $circuitBreaker = $this->createMock(CircuitBreaker::class);
        $renderer = $this->createMock(ErrorRenderer::class);

        // O renderer NUNCA deve ser acionado para simples avisos/warnings em produção
        $renderer->expects($this->never())->method('render');

        $errorHandler = new ErrorHandler($logger, $circuitBreaker, $renderer);

        // Simula ambiente de produção onde APP_ENV não é 'development'
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $previousReporting = error_reporting(E_ALL);
        try {
            // Dispara um warning (nível E_USER_WARNING ou E_WARNING)
            $handled = $errorHandler->handleError(E_USER_WARNING, 'Teste de aviso não-fatal', __FILE__, __LINE__);
            $this->assertTrue($handled, 'O aviso deve ser tratado silenciosamente retornando true.');
        } finally {
            error_reporting($previousReporting);
        }
    }

    public function testCircuitBreakerDisarmsPluginAndWritesToDisarmedJsonEvenIfNotActive(): void
    {
        $configPath = $this->tempDir . '/config/plugins.json';
        $disarmedPath = $this->tempDir . '/config/disarmed.json';
        file_put_contents($configPath, json_encode(['other_plugin' => true]));
        file_put_contents($disarmedPath, json_encode([]));

        $store = new \DomainSystem\Core\Storage\AtomicJsonStore();
        $cb = new CircuitBreaker($configPath, $disarmedPath, $store);

        $fakeException = new \Exception(
            "Cannot declare class DomainSystem\\Plugins\\commerce\\Plugin, because the name is already in use",
            0
        );
        // Reflect exception file to point to src/Plugins/domaincommerce/Plugin.php
        $prop = new \ReflectionProperty(\Exception::class, 'file');
        $prop->setValue($fakeException, '/var/www/src/Plugins/domaincommerce/Plugin.php');

        $culprit = $cb->analyzeAndDisarm($fakeException);

        $this->assertSame('domaincommerce', $culprit);

        $states = json_decode((string)file_get_contents($configPath), true);
        $this->assertArrayHasKey('domaincommerce', $states);
        $this->assertFalse($states['domaincommerce']);

        $disarmed = json_decode((string)file_get_contents($disarmedPath), true);
        $this->assertArrayHasKey('domaincommerce', $disarmed);
    }
}
