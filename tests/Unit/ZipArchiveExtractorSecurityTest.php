<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Utils\Archive\ZipArchiveExtractor;
use Exception;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ZipArchiveExtractorSecurityTest extends TestCase
{
    private string $tempWorkDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempWorkDir = sys_get_temp_dir() . '/ds_zip_sec_test_' . bin2hex(random_bytes(6));
        @mkdir($this->tempWorkDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempWorkDir);
        parent::tearDown();
    }

    public function testBlocksRelativePathTraversalZipSlip(): void
    {
        $zipPath = $this->tempWorkDir . '/rel_slip.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('evil_plugin/plugin.json', json_encode(['name' => 'evil_plugin']));
        $zip->addFromString('evil_plugin/../../shell.php', '<?php echo "pwned";');
        $zip->close();

        $extractor = new ZipArchiveExtractor();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Zip-Slip');
        $extractor->extract($zipPath, $this->tempWorkDir . '/dest');
    }

    public function testBlocksAbsoluteUnixPathInZip(): void
    {
        $zipPath = $this->tempWorkDir . '/abs_unix.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('evil_plugin/plugin.json', json_encode(['name' => 'evil_plugin']));
        // ZipArchive strips leading slash on addFromString in some PHP builds unless renamed or raw,
        // so we add and renameIndex to force a leading slash '/home/anyuser/.bashrc' inside the central directory.
        $zip->addFromString('placeholder_bashrc', '# malicious bashrc');
        $zip->renameName('placeholder_bashrc', '/home/anyuser/.bashrc');
        $zip->close();

        $extractor = new ZipArchiveExtractor();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Zip-Slip');
        $extractor->extract($zipPath, $this->tempWorkDir . '/dest');
    }

    public function testBlocksWindowsDriveAndUncPathsInZip(): void
    {
        $zipPath = $this->tempWorkDir . '/abs_win.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('evil_plugin/plugin.json', json_encode(['name' => 'evil_plugin']));
        $zip->addFromString('placeholder_win', 'malicious');
        $zip->renameName('placeholder_win', 'C:/Windows/win.ini');
        $zip->close();

        $extractor = new ZipArchiveExtractor();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Zip-Slip');
        $extractor->extract($zipPath, $this->tempWorkDir . '/dest');
    }

    public function testBlocksUnixSymlinkInZip(): void
    {
        $zipPath = $this->tempWorkDir . '/symlink.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('evil_plugin/plugin.json', json_encode(['name' => 'evil_plugin']));
        $zip->addFromString('evil_plugin/link_to_passwd', '/etc/passwd');
        // Set UNIX symlink mode (0120777 << 16)
        $zip->setExternalAttributesName(
            'evil_plugin/link_to_passwd',
            ZipArchive::OPSYS_UNIX,
            (0120777 << 16)
        );
        $zip->close();

        $extractor = new ZipArchiveExtractor();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('symlinks');
        $extractor->extract($zipPath, $this->tempWorkDir . '/dest');
    }

    public function testExtractsValidPluginZipSafelyViaStream(): void
    {
        $zipPath = $this->tempWorkDir . '/valid_plugin.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('hello_world/plugin.json', json_encode([
            'name' => 'hello_world',
            'version' => '2.5.0',
            'os_version' => '2.5'
        ]));
        $zip->addFromString('hello_world/Plugin.php', "<?php\nnamespace DomainSystem\\Plugins\\hello_world;\nclass Plugin {}");
        $zip->close();

        $destDir = $this->tempWorkDir . '/dest';
        $extractor = new ZipArchiveExtractor();
        $folder = $extractor->extract($zipPath, $destDir);

        $this->assertSame('hello_world', $folder);
        $this->assertFileExists($destDir . '/hello_world/plugin.json');
        $this->assertFileExists($destDir . '/hello_world/Plugin.php');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
