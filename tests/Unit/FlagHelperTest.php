<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Helpers\FlagHelper;
use PHPUnit\Framework\TestCase;

class FlagHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
    }

    public function testFlagBrRendersValidSvg(): void
    {
        $svg = FlagHelper::br(20, 14);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringEndsWith('</svg>', $svg);
        $this->assertStringContainsString('width="20"', $svg);
        $this->assertStringContainsString('height="14"', $svg);
        $this->assertStringContainsString('#009b3a', $svg); // Verde Brasil
        $this->assertStringContainsString('#fedf00', $svg); // Amarelo Brasil
        $this->assertStringContainsString('#002776', $svg); // Azul Brasil
        $this->assertStringContainsString('aria-label="Bandeira do Brasil"', $svg);
    }

    public function testFlagCnRendersValidSvg(): void
    {
        $svg = FlagHelper::cn(18, 12);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringEndsWith('</svg>', $svg);
        $this->assertStringContainsString('width="18"', $svg);
        $this->assertStringContainsString('height="12"', $svg);
        $this->assertStringContainsString('#de2910', $svg); // Vermelho China
        $this->assertStringContainsString('#ffde00', $svg); // Amarelo China
        $this->assertStringContainsString('aria-label="Bandeira da China"', $svg);
    }

    public function testFlagRenderDispatchesCorrectly(): void
    {
        $this->assertStringContainsString('Brasil', FlagHelper::render('br'));
        $this->assertStringContainsString('Brasil', FlagHelper::render('Brasil'));
        $this->assertStringContainsString('China', FlagHelper::render('cn'));
        $this->assertStringContainsString('China', FlagHelper::render('china'));
        $this->assertSame('', FlagHelper::render('invalid_code'));
    }
}
