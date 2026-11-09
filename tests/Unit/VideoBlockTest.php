<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\VideoBlock;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use PHPUnit\Framework\TestCase;

class VideoBlockTest extends TestCase
{
    private VideoBlock $block;

    protected function setUp(): void
    {
        $this->block = new VideoBlock();
    }

    public function testBlockAttributesAndRestrictions(): void
    {
        $this->assertSame('video', $this->block->getType());
        $this->assertSame('Vídeo', $this->block->getName());
        $this->assertSame('media', $this->block->getCategory());
        $this->assertSame([ThemePartInterface::TYPE_SECTION], $this->block->getAllowedPartTypes());
    }

    public function testRenderEmptyWhenUrlMissing(): void
    {
        $this->assertSame('', $this->block->render([]));
        $this->assertSame('', $this->block->render(['url' => '']));
        $this->assertSame('', $this->block->render(['url' => '   ']));
    }

    public function testRenderYouTubeWatchUrl(): void
    {
        $html = $this->block->render(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderYouTubeWatchWithExtraQueryParams(): void
    {
        $html = $this->block->render(['url' => 'https://www.youtube.com/watch?feature=shared&si=abc123xyz&v=dQw4w9WgXcQ&t=42s']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderYouTubeShorts(): void
    {
        $html = $this->block->render(['url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderYouTubeLive(): void
    {
        $html = $this->block->render(['url' => 'https://www.youtube.com/live/dQw4w9WgXcQ']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderYoutuBeShortUrl(): void
    {
        $html = $this->block->render(['url' => 'https://youtu.be/dQw4w9WgXcQ?t=10']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderPastedIframeSnippet(): void
    {
        $snippet = '<iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video player" frameborder="0" allowfullscreen></iframe>';
        $html = $this->block->render(['url' => $snippet]);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function testRenderVimeoUrl(): void
    {
        $html = $this->block->render(['url' => 'https://vimeo.com/76979871']);
        $this->assertStringContainsString('iframe', $html);
        $this->assertStringContainsString('https://player.vimeo.com/video/76979871', $html);
    }

    public function testRenderFallbackToResponsiveKeys(): void
    {
        $htmlTablet = $this->block->render(['url_tablet' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $htmlTablet);

        $htmlMobile = $this->block->render(['url_mobile' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $htmlMobile);
    }

    public function testRenderDirectMp4Video(): void
    {
        $html = $this->block->render(['url' => 'https://example.com/videos/hero.mp4']);
        $this->assertStringNotContainsString('iframe', $html);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('src="https://example.com/videos/hero.mp4"', $html);
        $this->assertStringContainsString('controls', $html);
    }

    public function testRenderUploadedLocalMp4Video(): void
    {
        $html = $this->block->render(['url' => '/uploads/builder/media_12345_abcde.mp4']);
        $this->assertStringNotContainsString('iframe', $html);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('src="/uploads/builder/media_12345_abcde.mp4"', $html);
    }
}
