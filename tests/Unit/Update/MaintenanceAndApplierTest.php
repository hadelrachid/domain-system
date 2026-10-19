<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Http\MaintenancePolicy;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\ApplyException;
use DomainSystem\Core\Update\Infrastructure\CoreProtectedPaths;
use DomainSystem\Core\Update\Infrastructure\FileMaintenanceMode;
use DomainSystem\Core\Update\Infrastructure\Sha256PackageVerifier;
use DomainSystem\Core\Update\Infrastructure\StagedUpdateApplier;
use DomainSystem\Core\Update\Infrastructure\ZipDirectoryBackup;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Tests\Support\PackageBuilder;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MaintenanceAndApplierTest extends TestCase
{
    private string $tempDir;
    private string $rootDir;
    private string $quarantineDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-apply-test-' . bin2hex(random_bytes(6));
        $this->rootDir = $this->tempDir . '/root';
        $this->quarantineDir = $this->tempDir . '/quarantine';

        mkdir($this->rootDir . '/src/Core', 0755, true);
        mkdir($this->rootDir . '/src/Plugins/flextheme/themes/rachidd', 0755, true);
        mkdir($this->rootDir . '/public/uploads', 0755, true);
        mkdir($this->rootDir . '/config', 0755, true);
        mkdir($this->rootDir . '/vendor', 0755, true);
        mkdir($this->quarantineDir, 0755, true);
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    public function testFileMaintenanceModeAndMaintenancePolicyExemptions(): void
    {
        $maint = new FileMaintenanceMode($this->rootDir);
        $this->assertFalse($maint->isEnabled());
        $this->assertFalse($maint->wasAlreadyEnabled());

        $maint->enable('Atualizando núcleo...');
        $this->assertTrue($maint->isEnabled());
        $this->assertFalse($maint->wasAlreadyEnabled());
        $this->assertFileExists($this->rootDir . '/.maintenance');

        // Se já estava ligado e enable() for chamado novamente, registra wasAlreadyEnabled = true
        $maint->enable('Segunda chamada');
        $this->assertTrue($maint->wasAlreadyEnabled());

        // Rotas do atualizador são isentas e visitante em '/' é bloqueado (503)
        $this->assertTrue(MaintenancePolicy::isExemptPath('/admin/settings/updates/core/advance'));
        $this->assertTrue(MaintenancePolicy::isExemptPath('/admin/settings/updates/core/health'));
        $this->assertFalse(MaintenancePolicy::shouldBlock('/admin/settings/updates/core/advance', true, null, 'admin'));
        $this->assertFalse(MaintenancePolicy::shouldBlock('/admin/settings/updates/core/health', true, null, 'admin'));
        $this->assertTrue(MaintenancePolicy::shouldBlock('/', true, null, 'guest'));

        $maint->disable();
        $this->assertFalse($maint->isEnabled());
    }

    public function testStagedUpdateApplierRespectsProtectedPathsDefaultIfMissingSkipsIdenticalAndOrdersStages(): void
    {
        file_put_contents($this->rootDir . '/.env', 'APP_ENV=prod');
        file_put_contents($this->rootDir . '/config/plugins.json', '{"custom":true}');
        file_put_contents($this->rootDir . '/src/Plugins/flextheme/themes/rachidd/style.css', '/* private */');
        file_put_contents($this->rootDir . '/src/Core/Unchanged.php', "<?php // unchanged\n");
        file_put_contents($this->rootDir . '/src/Core/Obsolete.php', "<?php // obsolete\n");
        file_put_contents($this->rootDir . '/bootstrap.php', "<?php define('DS_VERSION', '2.3.0');\n");
        file_put_contents($this->rootDir . '/public/index.php', "<?php // old index\n");

        $protectedPaths = new CoreProtectedPaths();
        $backupService = new ZipDirectoryBackup($this->rootDir);
        $backupRecord = $backupService->create(new BackupScope(UpdateKind::Core, 'core'), 'before-2.4.0');

        $package = PackageBuilder::create()
            ->withVersion('2.4.0')
            ->withFile('bootstrap.php', "<?php define('DS_VERSION', '2.4.0');\n")
            ->withFile('public/index.php', "<?php // new index\n")
            ->withFile('vendor/autoload.php', "<?php // autoload\n")
            ->withFile('src/Core/Unchanged.php', "<?php // unchanged\n")
            ->withFile('src/Core/NewFeature.php', "<?php // new feature\n")
            ->withFile('src/Plugins/flextheme/themes/rachidd/style.css', '/* overwrite attempt */')
            ->withFile('config/plugins.json', '{"custom":false}', 'default_if_missing')
            ->withFile('config/update.json', '{"channel":"stable"}', 'default_if_missing')
            ->withRemoved(['src/Core/Obsolete.php'])
            ->build($this->tempDir);

        $verifier = new Sha256PackageVerifier(
            new JsonUpdateManifestParser(),
            $protectedPaths,
            new ZipPackageExtractor(),
            $this->quarantineDir,
        );
        $verified = $verifier->verify($package);

        $applier = new StagedUpdateApplier($this->rootDir, $protectedPaths, $backupService);
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.3.0');

        $result = $applier->apply($verified, $target, $backupRecord);

        // 1. Ordem: vendor/ primeiro, src/ depois, public/index.php e bootstrap.php por último
        $this->assertSame(
            ['vendor/autoload.php', 'src/Core/NewFeature.php', 'config/update.json', 'public/index.php', 'bootstrap.php'],
            $result->written
        );

        // 2. Arquivo idêntico (src/Core/Unchanged.php) foi pulado (não está em written)
        $this->assertNotContains('src/Core/Unchanged.php', $result->written);

        // 3. Protegidos (tema privado rachidd e config/plugins.json existente) foram pulados e intocados
        $this->assertContains('src/Plugins/flextheme/themes/rachidd/style.css', $result->skippedProtected);
        $this->assertContains('config/plugins.json', $result->skippedProtected);
        $this->assertSame('/* private */', file_get_contents($this->rootDir . '/src/Plugins/flextheme/themes/rachidd/style.css'));
        $this->assertSame('{"custom":true}', file_get_contents($this->rootDir . '/config/plugins.json'));

        // 4. default_if_missing que não existia (config/update.json) foi criado
        $this->assertFileExists($this->rootDir . '/config/update.json');

        // 5. Obsolete foi removido
        $this->assertSame(['src/Core/Obsolete.php'], $result->removed);
        $this->assertFileDoesNotExist($this->rootDir . '/src/Core/Obsolete.php');
    }

    public function testFailureMidApplyThrowsApplyExceptionAndRevertLeavesTreeIdentical(): void
    {
        file_put_contents($this->rootDir . '/bootstrap.php', "<?php define('DS_VERSION', '2.3.0');\n");
        file_put_contents($this->rootDir . '/src/Core/Kernel.php', "<?php // kernel v2.3.0\n");
        // Cria um arquivo onde o pacote tentará criar um subdiretório (src/Core/Blocker/Sub.php)
        file_put_contents($this->rootDir . '/src/Core/Blocker', 'i-am-a-file-not-a-dir');

        $protectedPaths = new CoreProtectedPaths();
        $backupService = new ZipDirectoryBackup($this->rootDir, $this->tempDir . '/backups');
        $backupRecord = $backupService->create(new BackupScope(UpdateKind::Core, 'core'), 'before-fail');

        $hashesBefore = $this->snapshotTreeHashes($this->rootDir);

        $package = PackageBuilder::create()
            ->withVersion('2.4.0')
            ->withFile('vendor/new_lib.php', "<?php // written before failure\n")
            ->withFile('src/Core/Blocker/Sub.php', "<?php // will fail because Blocker is a file\n")
            ->withFile('bootstrap.php', "<?php define('DS_VERSION', '2.4.0');\n")
            ->build($this->tempDir);

        $verifier = new Sha256PackageVerifier(
            new JsonUpdateManifestParser(),
            $protectedPaths,
            new ZipPackageExtractor(),
            $this->quarantineDir,
        );
        $verified = $verifier->verify($package);

        $applier = new StagedUpdateApplier($this->rootDir, $protectedPaths, $backupService);
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.3.0');

        try {
            $applier->apply($verified, $target, $backupRecord);
            $this->fail('Deveria ter lançado ApplyException quando o pai do destino é um arquivo.');
        } catch (ApplyException) {
            // Reverte usando o backupRecord (incluindo o arquivo vendor/new_lib.php que chegou a ser escrito)
            $partialResult = new \DomainSystem\Core\Update\Value\ApplyResult(
                backup: $backupRecord,
                written: ['vendor/new_lib.php'],
                removed: [],
                skippedProtected: [],
            );
            $applier->revert($partialResult);
        }

        $hashesAfter = $this->snapshotTreeHashes($this->rootDir);
        $this->assertSame($hashesBefore, $hashesAfter, 'A árvore deve ficar idêntica após revert.');
    }

    /**
     * @return array<string, string>
     */
    private function snapshotTreeHashes(string $dir): array
    {
        $map = [];
        $baseLen = strlen(rtrim(str_replace('\\', '/', $dir), '/')) + 1;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $rel = str_replace('\\', '/', substr($item->getPathname(), $baseLen));
                $map[$rel] = (string)hash_file('sha256', $item->getPathname());
            }
        }

        ksort($map);
        return $map;
    }
}
