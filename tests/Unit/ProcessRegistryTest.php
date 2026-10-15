<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Plugin\Services\ProcessRegistry;
use DomainSystem\Core\Application;
use PHPUnit\Framework\TestCase;

class ProcessRegistryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'proc_reg_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'temp', 0755, true);
        ProcessRegistry::killSingleton($this->tempDir);
    }

    protected function tearDown(): void
    {
        ProcessRegistry::killSingleton($this->tempDir);
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

    public function testProcessIdsAreStableDuringSingletonLifetime(): void
    {
        $registry1 = new ProcessRegistry($this->tempDir);
        $pidAcademy1 = $registry1->startProcess('academy', false);
        $pidDatabase1 = $registry1->startProcess('database', true);

        $singletonId1 = $registry1->getSingletonId();

        $registry2 = new ProcessRegistry($this->tempDir);
        $pidAcademy2 = $registry2->startProcess('academy', false);
        $pidDatabase2 = $registry2->startProcess('database', true);

        // Enquanto o Singleton estiver vivo, os PIDs devem ser 100% estáveis e específicos
        $this->assertSame($pidAcademy1, $pidAcademy2);
        $this->assertSame($pidDatabase1, $pidDatabase2);
        $this->assertSame($singletonId1, $registry2->getSingletonId());

        // Módulos diferentes devem receber PIDs diferentes
        $this->assertNotSame($pidAcademy1, $pidDatabase1);
        $this->assertStringStartsWith('PID-', $pidAcademy1);
        $this->assertStringStartsWith('PID-', $pidDatabase1);
    }

    public function testProcessIdsRegenerateRandomlyWhenSingletonDies(): void
    {
        $registry1 = new ProcessRegistry($this->tempDir);
        $pidAcademy1 = $registry1->startProcess('academy', false);
        $singletonId1 = $registry1->getSingletonId();

        // Mata o Singleton
        ProcessRegistry::killSingleton($this->tempDir);

        // Ao nascer um novo Singleton, um novo ciclo de vida é gerado
        $registry2 = new ProcessRegistry($this->tempDir);
        $pidAcademy2 = $registry2->startProcess('academy', false);
        $singletonId2 = $registry2->getSingletonId();

        $this->assertNotSame($singletonId1, $singletonId2);
        $this->assertStringStartsWith('PID-', $pidAcademy2);
        // O novo PID gerado aleatoriamente para o novo Singleton deve ser diferente do anterior
        $this->assertNotSame($pidAcademy1, $pidAcademy2);
    }

    public function testMemoryConsumptionAndLiberationTracking(): void
    {
        $registry = new ProcessRegistry($this->tempDir);
        $pid = $registry->startProcess('academy', false);
        $registry->endProcess($pid, 'Running');

        // Simula consumo de memória (alocação de 500 KB)
        $registry->updateProcessMemory('academy', 500.0);
        $proc = $registry->getProcess($pid);
        $this->assertSame(500.0, $proc['memory_used_kb']);
        $this->assertSame(500.0, $proc['peak_memory_kb']);
        $this->assertGreaterThan(0, $proc['memory_delta_kb']);

        // Simula liberação de memória (garbage collection reduz para 200 KB)
        $registry->updateProcessMemory('academy', 200.0);
        $proc = $registry->getProcess($pid);
        $this->assertSame(200.0, $proc['memory_used_kb']);
        // O pico histórico deve ser mantido em 500 KB
        $this->assertSame(500.0, $proc['peak_memory_kb']);
        // O delta deve ser negativo (-300 KB), indicando liberação
        $this->assertSame(-300.0, $proc['memory_delta_kb']);
    }

    public function testEndProcessUpdatesStatusAndMetrics(): void
    {
        $registry = new ProcessRegistry($this->tempDir);
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
        $registry = new ProcessRegistry($this->tempDir);
        $pid = $registry->startProcess('database', true);
        $registry->endProcess($pid, 'Running');

        $registry->saveSnapshot($this->tempDir);

        $loaded = $registry->loadSnapshot($this->tempDir);
        $this->assertArrayHasKey($pid, $loaded);
        $this->assertSame('database', $loaded[$pid]['name']);
        $this->assertSame('SystemApp', $loaded[$pid]['type']);
    }
}
