<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Theme\Parts\Grid\DefaultLayoutPresetsFactory;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Grid\LayoutPreset;
use DomainSystem\Core\Theme\Parts\Grid\LayoutPresetRegistry;
use PHPUnit\Framework\TestCase;

class LayoutPresetsAndOcpTest extends TestCase
{
    public function testLayoutPresetValueObject(): void
    {
        $preset = new LayoutPreset(
            'custom_magazine_3',
            'Grid Revista Pro',
            3,
            '2fr 1fr 1fr',
            '1fr 1fr',
            '1fr',
            'magazine',
            '📰'
        );

        $this->assertSame('custom_magazine_3', $preset->getId());
        $this->assertSame('Grid Revista Pro', $preset->getName());
        $this->assertSame(3, $preset->getColumns());
        $this->assertSame('2fr 1fr 1fr', $preset->getDesktopTemplate());
        $this->assertSame('1fr 1fr', $preset->getTabletTemplate());
        $this->assertSame('1fr', $preset->getMobileTemplate());
        $this->assertSame('magazine', $preset->getCategory());
        $this->assertSame('📰', $preset->getIcon());

        $arr = $preset->toArray();
        $this->assertArrayHasKey('template', $arr);
        $this->assertSame('2fr 1fr 1fr', $arr['template']);
    }

    public function testDefaultPresetsFactoryContainsCoreAndNewCardLayouts(): void
    {
        $registry = DefaultLayoutPresetsFactory::create();

        // 6 Clássicos
        $this->assertTrue($registry->has('1_col'));
        $this->assertTrue($registry->has('2_cols_equal'));
        $this->assertTrue($registry->has('2_cols_split'));
        $this->assertTrue($registry->has('3_cols_header'));
        $this->assertTrue($registry->has('3_cols_equal'));
        $this->assertTrue($registry->has('4_cols_footer'));

        // 4 Novos Presets de Conteúdo / Cards / Hero
        $this->assertTrue($registry->has('cards_grid_4'));
        $this->assertTrue($registry->has('cards_grid_3'));
        $this->assertTrue($registry->has('content_with_sidebar'));
        $this->assertTrue($registry->has('hero_split'));

        $this->assertCount(10, $registry->all());

        // Verifica agrupamento por categoria
        $byCat = $registry->allByCategory();
        $this->assertArrayHasKey('grid', $byCat);
        $this->assertArrayHasKey('structural', $byCat);
        $this->assertArrayHasKey('header', $byCat);
        $this->assertArrayHasKey('footer', $byCat);
    }

    public function testLayoutGridRendererAdheresToOpenClosedPrinciple(): void
    {
        // Demonstração do OCP: Estendemos o motor com um novo layout sem modificar uma linha do renderer
        $customRegistry = new LayoutPresetRegistry();
        $customRegistry->register(new LayoutPreset(
            'agency_portfolio_5',
            'Portfólio de Agência (5 Colunas)',
            5,
            'repeat(5, 1fr)',
            'repeat(2, 1fr)',
            '1fr',
            'portfolio',
            '🎨'
        ));

        $renderer = new LayoutGridRenderer($customRegistry);

        $presets = $renderer->getPresets();
        $this->assertArrayHasKey('agency_portfolio_5', $presets);
        $this->assertSame('repeat(5, 1fr)', $presets['agency_portfolio_5']['template']);

        $colsHtml = [
            0 => '<p>Item 1</p>',
            1 => '<p>Item 2</p>',
            2 => '<p>Item 3</p>',
            3 => '<p>Item 4</p>',
            4 => '<p>Item 5</p>',
        ];

        $html = $renderer->renderSection('agency_portfolio_5', $colsHtml);

        $this->assertStringContainsString('grid-template-columns: repeat(5, 1fr)', $html);
        $this->assertStringContainsString('grid-template-columns: repeat(2, 1fr) !important', $html);
        $this->assertStringContainsString('grid-template-columns: 1fr !important', $html);
        $this->assertStringContainsString('ds-grid-col-4', $html);
    }

    public function testRenderCardsGrid4ResponsiveTemplates(): void
    {
        $renderer = new LayoutGridRenderer();
        $colsHtml = [
            0 => 'Card Post 1',
            1 => 'Card Post 2',
            2 => 'Card Post 3',
            3 => 'Card Post 4',
        ];

        $html = $renderer->renderSection('cards_grid_4', $colsHtml);

        $this->assertStringContainsString('grid-template-columns: repeat(4, 1fr)', $html);
        $this->assertStringContainsString('grid-template-columns: repeat(2, 1fr) !important', $html);
        $this->assertStringContainsString('grid-template-columns: 1fr !important', $html);
        $this->assertStringContainsString('Card Post 4', $html);
    }

    public function testRenderSectionWithUnifiedVisualStyles(): void
    {
        $renderer = new LayoutGridRenderer();
        $settings = [
            'background'    => 'var(--color-surface)',
            'border'        => '1px solid var(--color-border)',
            'border_radius' => '16px',
            'box_shadow'    => '0 10px 25px rgba(0,0,0,0.4)',
            'opacity'       => '0.9',
            'margin'        => '0px 0px 24px 0px',
        ];

        $html = $renderer->renderSection('1_col', [0 => 'Conteúdo'], $settings);

        $this->assertStringContainsString('background: var(--color-surface)', $html);
        $this->assertStringContainsString('border: 1px solid var(--color-border)', $html);
        $this->assertStringContainsString('border-radius: 16px', $html);
        $this->assertStringContainsString('box-shadow: 0 10px 25px rgba(0,0,0,0.4)', $html);
        $this->assertStringContainsString('opacity: 0.9', $html);
        $this->assertStringContainsString('margin: 0px 0px 24px 0px', $html);
    }
}
