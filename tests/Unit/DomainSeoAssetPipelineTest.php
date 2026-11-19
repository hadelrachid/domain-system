<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\seo\Services\AssetPipeline;
use DomainSystem\Plugins\seo\Services\NativeAssetMinifier;
use DomainSystem\Plugins\seo\Services\SeoManager;
use PHPUnit\Framework\TestCase;

class DomainSeoAssetPipelineTest extends TestCase
{
    private string $tempCacheDir;
    private string $tempCssFile;
    private string $tempJsFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempCacheDir = sys_get_temp_dir() . '/seo_cache_test_' . uniqid();
        @mkdir($this->tempCacheDir, 0777, true);

        $this->tempCssFile = $this->tempCacheDir . '/sample.css';
        $this->tempJsFile = $this->tempCacheDir . '/sample.js';

        file_put_contents($this->tempCssFile, "/* Comment */\n.btn {\n    color: #F0D060;\n    margin: 10px 20px;\n}\n");
        file_put_contents($this->tempJsFile, "// Line comment\nfunction testApp() {\n    /* Block comment */\n    var x = 10;\n    console.log(x);\n}\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->tempCssFile);
        @unlink($this->tempJsFile);
        $files = glob($this->tempCacheDir . '/*');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($this->tempCacheDir);
        parent::tearDown();
    }

    public function testNativeAssetMinifierMinifiesCssAndJs(): void
    {
        $minifier = new NativeAssetMinifier();

        $rawCss = "/* Brand Colors */\n.card {\n    background: #080c14;\n    border: 1px solid rgba(212, 175, 55, 0.2);\n}\n";
        $minifiedCss = $minifier->minifyCss($rawCss);

        $this->assertStringNotContainsString('/* Brand Colors */', $minifiedCss);
        $this->assertStringNotContainsString("\n", $minifiedCss);
        $this->assertStringContainsString('.card{background:#080c14', $minifiedCss);

        $rawJs = "// Protection\ndocument.addEventListener('click', function(e) {\n    /* inside */\n    console.log('clicked');\n});\n";
        $minifiedJs = $minifier->minifyJs($rawJs);

        $this->assertStringNotContainsString('// Protection', $minifiedJs);
        $this->assertStringNotContainsString('/* inside */', $minifiedJs);
        $this->assertStringContainsString("document.addEventListener('click', function(e) {", $minifiedJs);
    }

    public function testAssetPipelineCreatesMinifiedHashedBundles(): void
    {
        $minifier = new NativeAssetMinifier();
        $pipeline = new AssetPipeline($minifier, $this->tempCacheDir, '/assets/cache');

        $pipeline->enqueueCss($this->tempCssFile);
        $pipeline->enqueueJs($this->tempJsFile);

        $cssTags = $pipeline->renderCssTags();
        $jsTags = $pipeline->renderJsTags();

        $this->assertMatchesRegularExpression('/<link rel="stylesheet" href="\/assets\/cache\/style_min_[a-f0-9]{10}\.css">/', $cssTags);
        $this->assertMatchesRegularExpression('/<script src="\/assets\/cache\/script_min_[a-f0-9]{10}\.js" defer><\/script>/', $jsTags);

        // Verifica se os arquivos foram realmente gravados em disco
        $cachedFiles = glob($this->tempCacheDir . '/*_min_*.*');
        $this->assertCount(2, $cachedFiles);
    }

    public function testSeoManagerDelegatesToPipelineCorrectly(): void
    {
        $minifier = new NativeAssetMinifier();
        $pipeline = new AssetPipeline($minifier, $this->tempCacheDir, '/assets/cache');
        $seo = new SeoManager($pipeline);

        $seo->enqueueCss($this->tempCssFile)
            ->enqueueJs($this->tempJsFile);

        $cssTags = $seo->renderCssTags();
        $jsTags = $seo->renderJsTags();

        $this->assertStringContainsString('style_min_', $cssTags);
        $this->assertStringContainsString('script_min_', $jsTags);
        $this->assertStringContainsString('defer', $jsTags);
    }
}
