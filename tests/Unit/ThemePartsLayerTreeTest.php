<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\BlockRegistry;
use DomainSystem\Core\Theme\Parts\Blocks\HeadingBlock;
use DomainSystem\Core\Theme\Parts\Blocks\LogoBlock;
use DomainSystem\Core\Theme\Parts\Blocks\NavMenuBlock;
use DomainSystem\Core\Theme\Parts\Contracts\LayerTreeInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Tree\LayerTreeBuilder;
use PHPUnit\Framework\TestCase;

class ThemePartsLayerTreeTest extends TestCase
{
    private LayerTreeBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $registry = new BlockRegistry();
        $registry->register(new LogoBlock());
        $registry->register(new NavMenuBlock());
        $registry->register(new HeadingBlock());

        $grid = new LayoutGridRenderer();
        $this->builder = new LayerTreeBuilder($grid, $registry);
    }

    public function testBuildTreeGeneratesStandardizedHierarchy(): void
    {
        $part = ThemePart::fromArray([
            'id'    => 'header_main',
            'title' => 'Header Principal Pro',
            'type'  => ThemePartInterface::TYPE_HEADER,
            'sections' => [
                [
                    'id'            => 'sec_topbar',
                    'title'         => 'Topbar',
                    'layout_preset' => '2_cols_equal',
                    'columns_data'  => [
                        0 => [['type' => 'heading', 'data' => ['text' => 'Bem-vindo!']]],
                        1 => [['type' => 'cta_button', 'data' => ['label' => 'Login']]],
                    ],
                ],
                [
                    'id'            => 'sec_nav',
                    'title'         => 'Navegação Principal',
                    'layout_preset' => '3_cols_header',
                    'columns_data'  => [
                        0 => [['type' => 'logo', 'data' => ['text' => 'DomainSystem']]],
                        1 => [['type' => 'nav_menu', 'data' => ['menu_location' => 'header']]],
                        2 => [['type' => 'cta_button', 'data' => ['label' => 'Cadastre-se']]],
                    ],
                ],
            ],
        ]);

        $tree = $this->builder->buildTree($part);

        $this->assertSame('part_header_main', $tree['id']);
        $this->assertSame('root', $tree['type']);
        $this->assertSame('Header Principal Pro', $tree['name']);
        $this->assertSame(ThemePartInterface::TYPE_HEADER, $tree['category']);

        $counts = $tree['counts'];
        $this->assertSame(2, $counts['sections']);
        $this->assertSame(5, $counts['columns']); // 2 cols in topbar + 3 cols in main
        $this->assertSame(5, $counts['blocks']);

        $this->assertCount(2, $tree['children']);
        $sec0 = $tree['children'][0];
        $this->assertSame('sec_topbar', $sec0['id']);
        $this->assertSame('Topbar', $sec0['name']);
        $this->assertCount(2, $sec0['children']);

        // Check first column of second section
        $sec1 = $tree['children'][1];
        $this->assertSame('sec_nav', $sec1['id']);
        $this->assertSame('3_cols_header', $sec1['preset']);
        $this->assertCount(3, $sec1['children']);

        $logoCol = $sec1['children'][0];
        $this->assertSame('Célula 1', $logoCol['name']);
        $this->assertCount(1, $logoCol['children']);

        $logoBlock = $logoCol['children'][0];
        $this->assertSame('block', $logoBlock['type']);
        $this->assertSame('logo', $logoBlock['block_type']);
        $this->assertSame('Logotipo / Marca', $logoBlock['name']);
    }

    public function testGetCountsReturnsAccurateSummary(): void
    {
        $part = ThemePart::fromArray([
            'id'    => 'footer_simple',
            'title' => 'Rodapé Simples',
            'type'  => ThemePartInterface::TYPE_FOOTER,
            'sections' => [
                [
                    'layout_preset' => '1_col',
                    'columns_data'  => [
                        0 => [
                            ['type' => 'paragraph', 'data' => ['text' => 'Texto 1']],
                            ['type' => 'copyright', 'data' => ['site_name' => 'DS']],
                        ],
                    ],
                ],
            ],
        ]);

        $counts = $this->builder->getCounts($part);
        $this->assertSame(1, $counts['sections']);
        $this->assertSame(1, $counts['columns']);
        $this->assertSame(2, $counts['blocks']);
    }

    public function testValidateDepthRespectsPartVerticalLimits(): void
    {
        $this->assertTrue($this->builder->validateDepth(ThemePartInterface::TYPE_HEADER, 1));
        $this->assertTrue($this->builder->validateDepth(ThemePartInterface::TYPE_HEADER, 3));
        $this->assertFalse($this->builder->validateDepth(ThemePartInterface::TYPE_HEADER, 4));

        $this->assertTrue($this->builder->validateDepth(ThemePartInterface::TYPE_FOOTER, 3));
        $this->assertFalse($this->builder->validateDepth(ThemePartInterface::TYPE_FOOTER, 4));

        $this->assertTrue($this->builder->validateDepth(ThemePartInterface::TYPE_SECTION, 8));
        $this->assertFalse($this->builder->validateDepth(ThemePartInterface::TYPE_SECTION, 15));
    }
}
