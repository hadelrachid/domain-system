<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\SocialIconsBlock;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Icons\SvgIconRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Icons\DefaultSvgIconsFactory;
use DomainSystem\Core\Theme\Parts\Icons\SvgIconRegistry;
use PHPUnit\Framework\TestCase;

class SvgIconRegistryAndSocialBlockTest extends TestCase
{
    public function testRegistryCanRegisterAndRetrieveSvgIcons(): void
    {
        $registry = new SvgIconRegistry();
        
        $this->assertFalse($registry->has('bluesky'));
        $this->assertNull($registry->getSvg('bluesky'));

        $registry->register(
            'bluesky',
            '0 0 24 24',
            '<path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566 1.01 0 1.886 0 5.485c0 1.205.284 6.84 0.448 7.643.518 2.535 2.87 3.336 4.757 3.037-3.056 1.63-3.67 4.26-1.577 6.137 2.656 2.38 6.757-1.164 8.372-5.402 1.615 4.238 5.716 7.782 8.372 5.402 2.093-1.877 1.479-4.507-1.577-6.137 1.887.299 4.239-.502 4.757-3.037.164-.803.448-6.438.448-7.643 0-3.599-2.566-4.475-5.202-2.68C16.046 4.747 13.087 8.686 12 10.8z"/>',
            '#0085ff',
            'Bluesky'
        );

        $this->assertTrue($registry->has('bluesky'));
        $this->assertSame('Bluesky', $registry->getLabel('bluesky'));
        $this->assertSame('#0085ff', $registry->getBrandColor('bluesky'));

        $svg = $registry->getSvg('bluesky', [
            'width'  => '32',
            'height' => '32',
            'fill'   => '#0085ff',
        ]);

        $this->assertNotNull($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('width="32"', $svg);
        $this->assertStringContainsString('height="32"', $svg);
        $this->assertStringContainsString('fill="#0085ff"', $svg);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $svg);
        $this->assertStringContainsString('aria-hidden="true"', $svg);
    }

    public function testDefaultSvgIconsFactoryRegistersCoreNetworks(): void
    {
        $registry = DefaultSvgIconsFactory::create();

        $expectedNetworks = [
            'whatsapp',
            'instagram',
            'youtube',
            'linkedin',
            'facebook',
            'x',
            'tiktok',
            'github',
            'telegram',
            'email',
            'phone',
            'website',
        ];

        foreach ($expectedNetworks as $net) {
            $this->assertTrue($registry->has($net), "Registry should have network: {$net}");
            $this->assertNotEmpty($registry->getBrandColor($net), "Network {$net} should have a brand color");
            $this->assertNotEmpty($registry->getLabel($net), "Network {$net} should have a label");

            $svg = $registry->getSvg($net);
            $this->assertNotNull($svg, "SVG for {$net} should not be null");
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringContainsString('</svg>', $svg);
        }
    }

    public function testSocialIconsBlockImplementsContracts(): void
    {
        $block = new SocialIconsBlock();

        $this->assertInstanceOf(BlockInterface::class, $block);
        $this->assertInstanceOf(StylableElementInterface::class, $block);
        $this->assertSame('social_icons', $block->getType());
        $this->assertSame('contact', $block->getCategory());
        $this->assertIsArray($block->getSchema());
        $this->assertArrayHasKey('shape', $block->getSchema()['fields']);
        $this->assertArrayHasKey('color_mode', $block->getSchema()['fields']);
    }

    public function testSocialIconsBlockRenderOfficialModeWithCircleShape(): void
    {
        $block = new SocialIconsBlock();

        $html = $block->render([
            'networks' => [
                ['network' => 'whatsapp', 'url' => 'https://wa.me/5511999999999'],
                ['network' => 'instagram', 'url' => 'https://instagram.com/empresa'],
            ],
            'shape'      => 'circle',
            'color_mode' => 'official',
            'gap'        => '16px',
            'align'      => 'center',
        ]);

        $this->assertStringContainsString('border-radius: 50%', $html);
        $this->assertStringContainsString('background: #25D366', $html); // WhatsApp brand color
        $this->assertStringContainsString('background: #E4405F', $html); // Instagram brand color
        $this->assertStringContainsString('fill="#ffffff"', $html);
        $this->assertStringContainsString('gap: 16px', $html);
        $this->assertStringContainsString('justify-content: center', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testSocialIconsBlockRenderCustomModeWithDesignTokens(): void
    {
        $block = new SocialIconsBlock();

        $html = $block->render([
            'networks' => [
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/company/empresa'],
            ],
            'color_mode' => 'custom',
            'icon_color' => 'var(--color-primary, #38bdf8)',
            'bg_color'   => 'var(--color-surface, #1e293b)',
            'shape'      => 'rounded',
        ]);

        $this->assertStringContainsString('border-radius: 10px', $html);
        $this->assertStringContainsString('background: var(--color-surface, #1e293b)', $html);
        $this->assertStringContainsString('fill="var(--color-primary, #38bdf8)"', $html);
    }

    public function testSocialIconsBlockShapesAndHoverMicroInteractions(): void
    {
        $block = new SocialIconsBlock();

        // 1. Square shape + grow animation
        $htmlSquare = $block->render([
            'networks' => [
                ['network' => 'github', 'url' => 'https://github.com/empresa'],
            ],
            'shape'           => 'square',
            'hover_animation' => 'grow',
        ]);
        $this->assertStringContainsString('border-radius: 0px', $htmlSquare);
        $this->assertStringContainsString('ds-btn-grow', $htmlSquare);

        // 2. Minimal shape (no background, icon colored with brand color)
        $htmlMinimal = $block->render([
            'networks' => [
                ['network' => 'youtube', 'url' => 'https://youtube.com'],
            ],
            'shape'           => 'minimal',
            'color_mode'      => 'official',
            'hover_animation' => 'bounce',
        ]);
        $this->assertStringContainsString('background: transparent', $htmlMinimal);
        $this->assertStringContainsString('fill="#FF0000"', $htmlMinimal); // YouTube red
        $this->assertStringContainsString('ds-btn-bounce', $htmlMinimal);

        // 3. Glow animation
        $htmlGlow = $block->render([
            'networks' => [
                ['network' => 'telegram', 'url' => 'https://t.me/empresa'],
            ],
            'hover_animation' => 'glow',
        ]);
        $this->assertStringContainsString('ds-btn-glow', $htmlGlow);
    }

    public function testSocialIconsBlockHandlesLegacyArrayFormatAndEmptySafely(): void
    {
        $block = new SocialIconsBlock();

        // Legacy associative array format
        $htmlLegacy = $block->render([
            'networks' => [
                'github' => 'https://github.com/myuser',
                'x'      => 'https://x.com/myuser',
            ],
        ]);

        $this->assertStringContainsString('https://github.com/myuser', $htmlLegacy);
        $this->assertStringContainsString('https://x.com/myuser', $htmlLegacy);

        // Empty returns empty string
        $htmlEmpty = $block->render([
            'networks' => [],
        ]);
        $this->assertSame('', $htmlEmpty);

        // Unknown network falls back to website icon
        $htmlUnknown = $block->render([
            'networks' => [
                ['network' => 'unknown_social_network', 'url' => 'https://unknown.com'],
            ],
        ]);
        $this->assertStringContainsString('<svg', $htmlUnknown);
        $this->assertStringContainsString('https://unknown.com', $htmlUnknown);
    }
}
