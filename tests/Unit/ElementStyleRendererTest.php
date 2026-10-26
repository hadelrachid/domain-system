<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use PHPUnit\Framework\TestCase;

class ElementStyleRendererTest extends TestCase
{
    private ElementStyleRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ElementStyleRenderer();
    }

    public function testRenderStyleStringWithFlexboxAndDimensions(): void
    {
        $data = [
            'margin'          => '10px 20px 10px 20px',
            'padding'         => '12px 24px',
            'display'         => 'flex',
            'flex_direction'  => 'column',
            'justify_content' => 'space-between',
            'align_items'     => 'center',
            'flex_wrap'       => 'wrap',
            'gap'             => '14px',
            'width'           => '100%',
            'max_width'       => '600px',
            'background'      => '#0f172a',
            'border'          => '1px solid #38bdf8',
            'border_radius'   => '10px',
            'box_shadow'      => '0 4px 12px rgba(0,0,0,0.5)',
            'opacity'         => '0.9',
        ];

        $css = $this->renderer->renderStyleString($data);

        $this->assertStringContainsString('margin: 10px 20px 10px 20px;', $css);
        $this->assertStringContainsString('padding: 12px 24px;', $css);
        $this->assertStringContainsString('display: flex;', $css);
        $this->assertStringContainsString('flex-direction: column;', $css);
        $this->assertStringContainsString('justify-content: space-between;', $css);
        $this->assertStringContainsString('align-items: center;', $css);
        $this->assertStringContainsString('flex-wrap: wrap;', $css);
        $this->assertStringContainsString('gap: 14px;', $css);
        $this->assertStringContainsString('width: 100%;', $css);
        $this->assertStringContainsString('max-width: 600px;', $css);
        $this->assertStringContainsString('background: #0f172a;', $css);
        $this->assertStringContainsString('border: 1px solid #38bdf8;', $css);
        $this->assertStringContainsString('border-radius: 10px;', $css);
        $this->assertStringContainsString('box_shadow: 0 4px 12px rgba(0,0,0,0.5);' !== $css ? 'box-shadow: 0 4px 12px rgba(0,0,0,0.5);' : '', $css);
        $this->assertStringContainsString('opacity: 0.9;', $css);
    }

    public function testParse4Sides(): void
    {
        // 1 valor (todos os 4 lados iguais)
        $sides1 = $this->renderer->parse4Sides('16px');
        $this->assertSame(['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px'], $sides1);

        // 2 valores (topo/base e direita/esquerda)
        $sides2 = $this->renderer->parse4Sides('10px 20px');
        $this->assertSame(['top' => '10px', 'right' => '20px', 'bottom' => '10px', 'left' => '20px'], $sides2);

        // 4 valores (cima, direita, baixo, esquerda)
        $sides4 = $this->renderer->parse4Sides('5px 10px 15px 20px');
        $this->assertSame(['top' => '5px', 'right' => '10px', 'bottom' => '15px', 'left' => '20px'], $sides4);

        // Vazio ou nulo
        $empty = $this->renderer->parse4Sides('');
        $this->assertSame(['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'], $empty);
    }

    public function testSanitizeCssInjection(): void
    {
        $malicious = [
            'margin'     => '10px; color: red;',
            'background' => '</style><script>alert(1)</script>',
        ];

        $css = $this->renderer->renderStyleString($malicious);

        // O ponto e vírgula interno é higienizado para evitar injeção de propriedades arbitrárias
        $this->assertStringContainsString('margin: 10px color: red;', $css);
        $this->assertStringNotContainsString('<script>', $css);
        $this->assertStringNotContainsString('</style>', $css);
    }

    public function testRenderStyleStringWithDetailedBorderProperties(): void
    {
        $data = [
            'border_width' => '2px 0px 4px 0px',
            'border_style' => 'dashed',
            'border_color' => 'var(--color-primary)',
        ];

        $css = $this->renderer->renderStyleString($data);

        $this->assertStringContainsString('border-width: 2px 0px 4px 0px;', $css);
        $this->assertStringContainsString('border-style: dashed;', $css);
        $this->assertStringContainsString('border-color: var(--color-primary);', $css);
    }
}
