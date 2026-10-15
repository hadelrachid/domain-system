<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Plugin\Services\ProcessRegistry;
use PHPUnit\Framework\TestCase;

class ProcessRegistryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'proc_reg_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'temp', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testProcessIdsAreDeterministicAndStable(): void
    {
        $registry1 = new ProcessRegistry();
        $pidAcademy1 = $registry1->startProcess('academy', false);
        $pidDatabase1 = $registry1->startProcess('database', true);

        $registry2 = new ProcessRegistry();
        $pidAcademy2 = $registry2->startProcess('academy', false);
        $pidDatabase2 = $registry2->startProcess('database', true);

        // O PID deve ser 100% estável e idêntico entre inicializações para garantir rastreabilidade
        $this->assertSame($pidAcademy1, $pidAcademy2);
        $this->assertSame($pidDatabase1, $pidDatabase2);

        // Módulos diferentes devem receber PIDs diferentes
        $this->assertNotSame($pidAcademy1, $pidDatabase1);
        $this->assertStringStartsWith('PID-', $pidAcademy1);
    }

    public function testEndProcessUpdatesStatusAndMetrics(): void
    {
        $registry = new ProcessRegistry();
        $pid = $registry->startProcess('academy', false);

        $registry->endProcess($pid, 'Running');

        $proc = $registry->getProcess($pid);
        $this->assertNotNull($proc);
        $this->assertSame('Running', $proc['status']);
        $this->assertSame('UserPlugin', $proc['type']);
        $this->assertGreaterThanOrEqual(0, $proc['memory_used_kb']);
        $this->assertGreaterThanOrEqual(0, $proc['duration_ms']);
    }

    public function testSaveAndLoadSnapshot(): void
    {
        $registry = new ProcessRegistry();
        $pid = $registry->startProcess('database', true);
        $registry->endProcess($pid, 'Running');

        $registry->saveSnapshot($this->tempDir);

        $loaded = $registry->loadSnapshot($this->tempDir);
        $this->assertArrayHasKey($pid, $loaded);
        $this->assertSame('database', $loaded[$pid]['name']);
        $this->assertSame('SystemApp', $loaded[$pid]['type']);
    }
}
