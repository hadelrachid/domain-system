<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\ButtonBlock;
use DomainSystem\Core\Theme\Parts\Blocks\CtaButtonBlock;
use PHPUnit\Framework\TestCase;

class ButtonHoverColorTest extends TestCase
{
    public function testButtonRendersHoverColorsVariables(): void
    {
        $button = new ButtonBlock();
        $html = $button->render([
            'label'            => 'Botão Teste',
            'bg_color'         => '#10b981',
            'text_color'       => '#ffffff',
            'hover_bg_color'   => '#059669',
            'hover_text_color' => '#f9fafb',
            'hover_animation'  => 'lift',
        ]);

        $this->assertStringContainsString('--btn-hover-bg: #059669;', $html);
        $this->assertStringContainsString('--btn-hover-color: #f9fafb;', $html);
        $this->assertStringContainsString('ds-btn-lift', $html);
    }

    public function testCtaButtonRendersHoverColorsVariables(): void
    {
        $cta = new CtaButtonBlock();
        $html = $cta->render([
            'label'            => 'CTA Teste',
            'bg_color'         => '#38bdf8',
            'text_color'       => '#0b0c10',
            'hover_bg_color'   => '#0284c7',
            'hover_text_color' => '#ffffff',
            'hover_animation'  => 'grow',
        ]);

        $this->assertStringContainsString('--btn-hover-bg: #0284c7;', $html);
        $this->assertStringContainsString('--btn-hover-color: #ffffff;', $html);
        $this->assertStringContainsString('ds-btn-grow', $html);
    }
}
