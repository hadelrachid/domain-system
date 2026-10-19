<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Infrastructure\FileUpdateLock;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use PHPUnit\Framework\TestCase;

final class StateAndLockTest extends TestCase
{
    private string $tempDir;
    private UpdateTarget $coreTarget;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir()) . '/ds_state_lock_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->coreTarget = new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: '2.3.0',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testLoadWithoutFileReturnsIdleState(): void
    {
        $store = new JsonUpdateStateStore($this->tempDir);
        $state = $store->load($this->coreTarget);

        $this->assertSame(UpdateStep::Idle, $state->step);
        $this->assertSame('idle', $state->updateId);
        $this->assertSame('core', $state->target->slug);
    }

    public function testSaveAndReloadPersistsStateAtomically(): void
    {
        $store = new JsonUpdateStateStore($this->tempDir);
        $release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Melhorias de segurança',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 4096,
            sha256: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            etag: 'W/"abc"',
        );

        $state = new UpdateState(
            updateId: 'upd-2026-01',
            target: $this->coreTarget,
            step: UpdateStep::BackedUp,
            release: $release,
            etag: 'W/"abc"',
            lastCheckAt: '2026-10-19T12:00:00Z',
            context: ['backup_id' => 'bkp-123'],
            error: null,
            updatedAt: '2026-10-19T12:05:00Z',
        );

        $store->save($state);
        $this->assertFileExists($this->tempDir . '/storage/state/update-core.json');

        $reloaded = $store->load($this->coreTarget);
        $this->assertSame('upd-2026-01', $reloaded->updateId);
        $this->assertSame(UpdateStep::BackedUp, $reloaded->step);
        $this->assertNotNull($reloaded->release);
        $this->assertSame('2.4.0', $reloaded->release->version);
        $this->assertSame(['backup_id' => 'bkp-123'], $reloaded->context);
    }

    public function testFileUpdateLockPreventsConcurrentAcquireRestoresOnExpiryAndReleases(): void
    {
        $now = 1_700_000_000;
        $lock = new FileUpdateLock($this->tempDir, static function () use (&$now): int {
            return $now;
        });

        // 1º acquire tem sucesso
        $this->assertTrue($lock->acquire($this->coreTarget, 300));
        $this->assertFileExists($this->tempDir . '/storage/state/update-core.lock');

        // 2º acquire seguido falha (não bloqueia)
        $this->assertFalse($lock->acquire($this->coreTarget, 300));

        // Avança o relógio além do TTL (300s) -> lock expirado pode ser reaproveitado
        $now += 301;
        $this->assertTrue($lock->acquire($this->coreTarget, 300));

        // Liberação explícita permite novo acquire imediato
        $lock->release($this->coreTarget);
        $this->assertTrue($lock->acquire($this->coreTarget, 300));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
