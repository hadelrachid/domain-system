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

            public function getHeadingColor(): ?ColorValue
            {
                return ColorValue::fromToken('color-primary');
            }

            public function getLinkColor(): ?ColorValue
            {
                return ColorValue::fromToken('color-secondary');
            }

            public function getLinkHoverColor(): ?ColorValue
            {
                return ColorValue::fromToken('color-tertiary');
            }

            public function getBorderColor(): ?ColorValue
            {
                return ColorValue::fromHex('#ffffff');
            }

            public function getFontFamily(): ?string
            {
                return 'var(--font-primary)';
            }

            public function getFontSize(): ?DimensionValue
            {
                return DimensionValue::rem(1.2);
            }

            public function getFontWeight(): ?string
            {
                return '700';
            }

            public function getLineHeight(): ?string
            {
                return '1.5';
            }

            public function getLetterSpacing(): ?DimensionValue
            {
                return DimensionValue::px(0);
            }

            public function getTextTransform(): ?string
            {
                return 'none';
            }

            public function getTextAlign(): ?string
            {
                return 'left';
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
        $this->assertSame('var(--color-primary)', $element->getHeadingColor()?->toCss());
        $this->assertSame('var(--color-secondary)', $element->getLinkColor()?->toCss());
        $this->assertSame('var(--color-tertiary)', $element->getLinkHoverColor()?->toCss());
        $this->assertSame('var(--font-primary)', $element->getFontFamily());
        $this->assertSame('1.2rem', $element->getFontSize()?->toCss());
        $this->assertSame('700', $element->getFontWeight());
    }

    public function testGlobalColorTokensAndContainerTypographyCascading(): void
    {
        $styleRenderer = new \DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer();
        $gridRenderer = new \DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer(null, $styleRenderer);
        $headingBlock = new \DomainSystem\Core\Theme\Parts\Blocks\HeadingBlock($styleRenderer);
        $paragraphBlock = new \DomainSystem\Core\Theme\Parts\Blocks\ParagraphBlock(null, $styleRenderer);
        $logoBlock = new \DomainSystem\Core\Theme\Parts\Blocks\LogoBlock(null, null, $styleRenderer);
        $copyrightBlock = new \DomainSystem\Core\Theme\Parts\Blocks\CopyrightBlock(null, $styleRenderer);
        $postCardsBlock = new \DomainSystem\Core\Theme\Parts\Blocks\PostCardsGridBlock(null, $styleRenderer);

        // 1. Verifica contrato StylableElementInterface em todos os blocos de texto
        $this->assertInstanceOf(StylableElementInterface::class, $headingBlock);
        $this->assertInstanceOf(StylableElementInterface::class, $paragraphBlock);
        $this->assertInstanceOf(StylableElementInterface::class, $logoBlock);
        $this->assertInstanceOf(StylableElementInterface::class, $copyrightBlock);
        $this->assertInstanceOf(StylableElementInterface::class, $postCardsBlock);

        // 2. Verifica seleção de cores Primária, Secundária e Terciária nos blocos
        $hHtml = $headingBlock->render([
            'text'           => 'Título Terciário',
            'level'          => 'h2',
            'text_color'     => 'var(--color-tertiary)',
            'font_family'    => 'tertiary',
            'font_weight'    => '900',
            'letter_spacing' => '2px',
            'text_transform' => 'uppercase',
        ]);
        $this->assertStringContainsString('color: var(--color-tertiary)', $hHtml);
        $this->assertStringContainsString('font-family: var(--font-tertiary, sans-serif)', $hHtml);
        $this->assertStringContainsString('font-weight: 900', $hHtml);
        $this->assertStringContainsString('letter-spacing: 2px', $hHtml);
        $this->assertStringContainsString('text-transform: uppercase', $hHtml);
        $this->assertSame('var(--color-tertiary)', $headingBlock->getTextColor()?->toCss());

        $pHtml = $paragraphBlock->render([
            'text'       => 'Parágrafo com cor secundária',
            'text_color' => 'var(--color-secondary)',
        ]);
        $this->assertStringContainsString('color: var(--color-secondary)', $pHtml);

        $logoHtml = $logoBlock->render([
            'brand_text' => 'MinhaMarca',
            'text_color' => 'var(--color-primary)',
            'font_size'  => '2rem',
        ]);
        $this->assertStringContainsString('color: var(--color-primary)', $logoHtml);
        $this->assertStringContainsString('font-size: 2rem', $logoHtml);

        $copyHtml = $copyrightBlock->render([
            'site_name'  => 'DomainSystem',
            'text_color' => 'var(--color-primary)',
            'link_color' => 'var(--color-tertiary)',
        ]);
        $this->assertStringContainsString('color: var(--color-primary)', $copyHtml);
        $this->assertStringContainsString('color: var(--color-tertiary)', $copyHtml);

        // 3. Verifica ElementStyleRenderer gerando variáveis locais de cascata para qualquer bloco
        $elemStyle = $styleRenderer->renderStyleString([
            'text_color'       => 'var(--color-primary)',
            'heading_color'    => 'var(--color-secondary)',
            'link_color'       => 'var(--color-tertiary)',
            'link_hover_color' => '#ffffff',
        ]);
        $this->assertStringContainsString('color: var(--color-primary)', $elemStyle);
        $this->assertStringContainsString('--ds-local-text-color: var(--color-primary)', $elemStyle);
        $this->assertStringContainsString('--ds-local-heading-color: var(--color-secondary)', $elemStyle);
        $this->assertStringContainsString('--ds-local-link-color: var(--color-tertiary)', $elemStyle);
        $this->assertStringContainsString('--ds-local-link-hover-color: #ffffff', $elemStyle);

        // 4. Verifica cascata de cores tipográficas no Container / Seção (LayoutGridRenderer)
        $sectionHtml = $gridRenderer->renderSection('1_col', [$hHtml . $pHtml], [
            'text_color'       => 'var(--color-text-main)',
            'heading_color'    => 'var(--color-primary)',
            'link_color'       => 'var(--color-tertiary)',
            'link_hover_color' => 'var(--color-secondary)',
            'font_family'      => 'tertiary',
            'text_transform'   => 'capitalize',
        ]);
        $this->assertStringContainsString('--ds-local-text-color: var(--color-text-main);', $sectionHtml);
        $this->assertStringContainsString('--ds-local-heading-color: var(--color-primary);', $sectionHtml);
        $this->assertStringContainsString('--ds-local-link-color: var(--color-tertiary);', $sectionHtml);
        $this->assertStringContainsString('font-family: var(--font-tertiary, sans-serif);', $sectionHtml);
        $this->assertStringContainsString('text-transform: capitalize;', $sectionHtml);
    }
}
