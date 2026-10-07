<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Utils\Archive\ZipArchiveExtractor;
use Exception;
use ZipArchive;

class ZipArchiveExtractorTest extends TestCase
{
    private string $tempDir;
    private string $maliciousZip;
    private string $safeZip;

    protected function setUp(): void
    {
        $this->tempDir = DOMAIN_SYSTEM_ROOT . '/temp/test_zips';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        // Criando ZIP malicioso
        $this->maliciousZip = $this->tempDir . '/malicious.zip';
        $zip = new ZipArchive();
        if ($zip->open($this->maliciousZip, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('plugin.json', json_encode(['name' => 'BadPlugin']));
            $zip->addFromString('../../malicious_shell.php', '<?php echo "Hacked"; ?>');
            $zip->close();
        }

        // Criando ZIP seguro
        $this->safeZip = $this->tempDir . '/safe.zip';
        $zip2 = new ZipArchive();
        if ($zip2->open($this->safeZip, ZipArchive::CREATE) === TRUE) {
            $zip2->addFromString('safe-plugin/plugin.json', json_encode(['name' => 'SafePlugin']));
            $zip2->addFromString('safe-plugin/index.php', '<?php echo "Safe"; ?>');
            $zip2->close();
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->maliciousZip)) unlink($this->maliciousZip);
        if (file_exists($this->safeZip)) unlink($this->safeZip);
        
        $this->deleteDirectory($this->tempDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testMaliciousZipIsBlockedAndThrowsException()
    {
        $extractor = new ZipArchiveExtractor();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Caminhos maliciosos'); // Da defesa 1
        
        $extractor->extract($this->maliciousZip, $this->tempDir . '/plugins');
    }

    public function testSafeZipIsExtractedCorrectly()
    {
        $extractor = new ZipArchiveExtractor();
        $dirName = $extractor->extract($this->safeZip, $this->tempDir . '/plugins');
        
        $this->assertEquals('safe-plugin', $dirName);
        $this->assertFileExists($this->tempDir . '/plugins/safe-plugin/plugin.json');
        
        $this->deleteDirectory($this->tempDir . '/plugins/safe-plugin');
    }
}
