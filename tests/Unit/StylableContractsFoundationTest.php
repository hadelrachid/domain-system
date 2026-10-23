<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Theme\Parts\Contracts\Styles\ColorContractInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\DimensionContractInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;
use DomainSystem\Core\Theme\Parts\Styles\Values\CssUnit;
use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StylableContractsFoundationTest extends TestCase
{
    public function testCssUnitEnumSupportsAllModernUnits(): void
    {
        $this->assertSame('px', CssUnit::PX->value);
        $this->assertSame('%', CssUnit::PERCENT->value);
        $this->assertSame('vh', CssUnit::VH->value);
        $this->assertSame('vw', CssUnit::VW->value);
        $this->assertSame('rem', CssUnit::REM->value);
        $this->assertSame('em', CssUnit::EM->value);
        $this->assertSame('cm', CssUnit::CM->value);
        $this->assertSame('mm', CssUnit::MM->value);
        $this->assertSame('pt', CssUnit::PT->value);
        $this->assertSame('auto', CssUnit::AUTO->value);
        $this->assertSame('fit-content', CssUnit::FIT_CONTENT->value);

        $this->assertTrue(CssUnit::PX->isNumeric());
        $this->assertTrue(CssUnit::PERCENT->isNumeric());
        $this->assertTrue(CssUnit::VH->isNumeric());
        $this->assertFalse(CssUnit::AUTO->isNumeric());
        $this->assertFalse(CssUnit::FIT_CONTENT->isNumeric());

        $this->assertSame(CssUnit::VH, CssUnit::tryFromString('vh'));
        $this->assertSame(CssUnit::PERCENT, CssUnit::tryFromString('%'));
        $this->assertSame(CssUnit::CM, CssUnit::tryFromString('CM'));
        $this->assertNull(CssUnit::tryFromString('invalid_unit'));
    }

    public function testDimensionValueCreationAndParsing(): void
    {
        // 1. Factory methods
        $d1 = DimensionValue::px(24);
        $this->assertSame(24.0, $d1->getAmount());
        $this->assertSame(CssUnit::PX, $d1->getUnit());
        $this->assertSame('24px', $d1->toCss());

        $d2 = DimensionValue::percent(50.5);
        $this->assertSame('50.5%', $d2->toCss());

        $d3 = DimensionValue::vh(100);
        $this->assertSame('100vh', $d3->toCss());

        $d4 = DimensionValue::cm(2.5);
        $this->assertSame('2.5cm', $d4->toCss());

        $d5 = DimensionValue::auto();
        $this->assertSame('auto', $d5->toCss());
        $this->assertSame(CssUnit::AUTO, $d5->getUnit());

        // 2. String parsing
        $p1 = DimensionValue::fromString('100vh');
        $this->assertSame(100.0, $p1->getAmount());
        $this->assertSame(CssUnit::VH, $p1->getUnit());
        $this->assertSame('100vh', (string)$p1);

        $p2 = DimensionValue::fromString('75%');
        $this->assertSame(75.0, $p2->getAmount());
        $this->assertSame(CssUnit::PERCENT, $p2->getUnit());

        $p3 = DimensionValue::fromString('3.2rem');
        $this->assertSame(3.2, $p3->getAmount());
        $this->assertSame(CssUnit::REM, $p3->getUnit());

        $p4 = DimensionValue::fromString('15cm');
        $this->assertSame(15.0, $p4->getAmount());
        $this->assertSame(CssUnit::CM, $p4->getUnit());

        $p5 = DimensionValue::fromString('auto');
        $this->assertSame('auto', $p5->toCss());

        $p6 = DimensionValue::fromString('fit-content');
        $this->assertSame('fit-content', $p6->toCss());

        // Sem unidade definida: assume px
        $p7 = DimensionValue::fromString('32');
        $this->assertSame('32px', $p7->toCss());
        $this->assertSame(CssUnit::PX, $p7->getUnit());

        // Vazio ou zero
        $p8 = DimensionValue::fromString('');
        $this->assertSame('0px', $p8->toCss());

        // Serialização JSON
        $json = json_encode($p1);
        $this->assertIsString($json);
        $this->assertStringContainsString('"amount":100', $json);
        $this->assertStringContainsString('"unit":"vh"', $json);
        $this->assertStringContainsString('"css":"100vh"', $json);
    }

    public function testDimensionValueThrowsOnInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DimensionValue::fromString('invalid_dimension_xyz');
    }

    public function testColorValueTokenFirstAndTypes(): void
    {
        // 1. Design Token
        $c1 = ColorValue::fromToken('primary');
        $this->assertTrue($c1->isToken());
        $this->assertSame('primary', $c1->getTokenName());
        $this->assertSame('var(--primary)', $c1->toCss());

        // Limpeza de var(--) se passado bruto
        $c1b = ColorValue::fromToken('var(--secondary)');
        $this->assertSame('secondary', $c1b->getTokenName());
        $this->assertSame('var(--secondary)', $c1b->toCss());

        // 2. Hexadecimal
        $c2 = ColorValue::fromHex('#45f3ff');
        $this->assertSame(ColorValue::TYPE_HEX, $c2->getType());
        $this->assertFalse($c2->isToken());
        $this->assertSame('#45f3ff', $c2->toCss());

        // 3. RGBA
        $c3 = ColorValue::fromRgba(69, 243, 255, 0.5);
        $this->assertSame(ColorValue::TYPE_RGB, $c3->getType());
        $this->assertSame('rgba(69, 243, 255, 0.5)', $c3->toCss());

        // 4. Transparente
        $c4 = ColorValue::transparent();
        $this->assertSame(ColorValue::TYPE_TRANSPARENT, $c4->getType());
        $this->assertSame('transparent', $c4->toCss());

        // 5. Gradiente
        $c5 = ColorValue::fromGradient('linear-gradient(135deg, var(--primary), var(--secondary))');
        $this->assertSame(ColorValue::TYPE_GRADIENT, $c5->getType());
        $this->assertStringStartsWith('linear-gradient(', $c5->toCss());

        // 6. Parse automático via fromString
        $autoToken = ColorValue::fromString('var(--tertiary)');
        $this->assertTrue($autoToken->isToken());
        $this->assertSame('tertiary', $autoToken->getTokenName());

        $autoTokenName = ColorValue::fromString('background');
        $this->assertTrue($autoTokenName->isToken());
        $this->assertSame('var(--background)', $autoTokenName->toCss());

        $autoHex = ColorValue::fromString('#0b0c10');
        $this->assertSame('#0b0c10', $autoHex->toCss());

        $autoTransparent = ColorValue::fromString('transparent');
        $this->assertSame('transparent', $autoTransparent->toCss());

        $autoRgba = ColorValue::fromString('rgba(255, 255, 255, 0.8)');
        $this->assertSame('rgba(255, 255, 255, 0.8)', $autoRgba->toCss());
    }

    public function testColorValueThrowsOnInvalidInputs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ColorValue::fromString('invalid_color_value_alert()');
    }

    public function testStylableElementContractComposition(): void
    {
        // Criação de classe anônima mockando um elemento estilizável que implementa o contrato mestre
        $element = new class implements StylableElementInterface {
            public function getBackgroundColor(): ?ColorValue
            {
                return ColorValue::fromToken('primary');
            }

            public function getTextColor(): ?ColorValue
            {
                return ColorValue::fromToken('text_main');
            }

            public function getBorderColor(): ?ColorValue
            {
                return ColorValue::fromHex('#ffffff');
            }

            public function getWidth(): ?DimensionValue
            {
                return DimensionValue::percent(100);
            }

            public function getMaxWidth(): ?DimensionValue
            {
                return DimensionValue::px(1200);
            }

            public function getHeight(): ?DimensionValue
            {
                return DimensionValue::vh(80);
            }

            public function getMinHeight(): ?DimensionValue
            {
                return null;
            }

            public function getBorderStyle(): ?string
            {
                return 'solid';
            }

            public function getBorderWidth(): ?DimensionValue
            {
                return DimensionValue::px(2);
            }

            public function getBorderRadius(): ?DimensionValue
            {
                return DimensionValue::px(8);
            }

            public function getShadowPreset(): string
            {
                return 'glow_primary';
            }

            public function getBoxShadowCss(): ?string
            {
                return '0 0 15px var(--primary)';
            }

            public function getHoverEffect(): ?string
            {
                return 'lift';
            }

            public function getEntranceAnimation(): ?string
            {
                return 'fadeIn';
            }

            public function getTransitionDuration(): ?string
            {
                return '0.3s';
            }

            public function getTimingFunction(): ?string
            {
                return 'ease';
            }

            public function getStyleProperties(): array
            {
                return [
                    'background' => $this->getBackgroundColor()?->toCss(),
                    'color'      => $this->getTextColor()?->toCss(),
                    'width'      => $this->getWidth()?->toCss(),
                    'max_width'  => $this->getMaxWidth()?->toCss(),
                    'height'     => $this->getHeight()?->toCss(),
                    'border'     => '2px solid #ffffff',
                    'box_shadow' => $this->getBoxShadowCss(),
                    'hover'      => $this->getHoverEffect(),
                ];
            }
        };

        $this->assertInstanceOf(StylableElementInterface::class, $element);
        $this->assertInstanceOf(ColorContractInterface::class, $element);
        $this->assertInstanceOf(DimensionContractInterface::class, $element);

        $styles = $element->getStyleProperties();
        $this->assertSame('var(--primary)', $styles['background']);
        $this->assertSame('var(--text_main)', $styles['color']);
        $this->assertSame('100%', $styles['width']);
        $this->assertSame('1200px', $styles['max_width']);
        $this->assertSame('80vh', $styles['height']);
        $this->assertSame('lift', $styles['hover']);
    }
}
