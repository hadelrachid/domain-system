<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Tree;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayerTreeInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;

/**
 * Construtor da Árvore de Camadas e Hierarquias (Layers / Navigator).
 * 
 * Implementa o contrato LayerTreeInterface para fornecer a estrutura em árvore completa
 * de seções, colunas e blocos, no padrão Navigator do Elementor.
 */
class LayerTreeBuilder implements LayerTreeInterface
{
    private ?LayoutGridInterface $gridRenderer;
    private ?BlockRegistryInterface $blockRegistry;

    public function __construct(
        ?LayoutGridInterface $gridRenderer = null,
        ?BlockRegistryInterface $blockRegistry = null
    ) {
        $this->gridRenderer = $gridRenderer;
        $this->blockRegistry = $blockRegistry;
    }

    public function buildTree(ThemePartInterface $part): array
    {
        $sections = $part->getSections();
        $sectionNodes = [];
        $totalColumns = 0;
        $totalBlocks = 0;

        foreach ($sections as $sIdx => $sec) {
            $presetKey = (string)($sec['layout_preset'] ?? '1_col');
            $secTitle = (string)($sec['title'] ?? ('Seção ' . ($sIdx + 1)));
            $secId = (string)($sec['id'] ?? ('sec_' . $sIdx));
            $containerSettings = (array)($sec['container_settings'] ?? []);
            $columnsData = (array)($sec['columns_data'] ?? []);

            $numCols = 1;
            if ($this->gridRenderer !== null) {
                $presets = $this->gridRenderer->getPresets();
                if (isset($presets[$presetKey]['columns'])) {
                    $numCols = (int)$presets[$presetKey]['columns'];
                }
            } elseif (preg_match('/^(\d+)_col/', $presetKey, $m)) {
                $numCols = (int)$m[1];
            }

            $columnNodes = [];
            for ($cIdx = 0; $cIdx < $numCols; $cIdx++) {
                $totalColumns++;
                $blocksInCol = (array)($columnsData[$cIdx] ?? []);
                $blockNodes = [];

                foreach ($blocksInCol as $bIdx => $bData) {
                    $totalBlocks++;
                    $bType = (string)($bData['type'] ?? 'unknown');
                    $bMeta = $this->resolveBlockMeta($bType);

                    $blockNodes[] = [
                        'id'         => "{$secId}_col_{$cIdx}_block_{$bIdx}",
                        'type'       => 'block',
                        'block_type' => $bType,
                        'name'       => $bMeta['name'],
                        'icon'       => $bMeta['icon'],
                        'sec_index'  => $sIdx,
                        'col_index'  => $cIdx,
                        'block_index'=> $bIdx,
                        'data'       => (array)($bData['data'] ?? []),
                    ];
                }

                $columnNodes[] = [
                    'id'          => "{$secId}_col_{$cIdx}",
                    'type'        => 'column',
                    'index'       => $cIdx,
                    'name'        => 'Célula ' . ($cIdx + 1),
                    'total_blocks'=> count($blockNodes),
                    'children'    => $blockNodes,
                ];
            }

            $sectionNodes[] = [
                'id'                 => $secId,
                'type'               => 'section',
                'index'              => $sIdx,
                'name'               => $secTitle,
                'preset'             => $presetKey,
                'container_settings' => $containerSettings,
                'total_columns'      => count($columnNodes),
                'children'           => $columnNodes,
            ];
        }

        return [
            'id'       => 'part_' . $part->getId(),
            'type'     => 'root',
            'name'     => $part->getTitle(),
            'category' => $part->getType(),
            'counts'   => [
                'sections' => count($sectionNodes),
                'columns'  => $totalColumns,
                'blocks'   => $totalBlocks,
            ],
            'children' => $sectionNodes,
        ];
    }

    public function getCounts(ThemePartInterface $part): array
    {
        $tree = $this->buildTree($part);
        return $tree['counts'];
    }

    public function validateDepth(string $partType, int $sectionCount): bool
    {
        $max = match ($partType) {
            ThemePartInterface::TYPE_HEADER => self::MAX_SECTIONS_HEADER,
            ThemePartInterface::TYPE_FOOTER => self::MAX_SECTIONS_FOOTER,
            default                         => self::MAX_SECTIONS_DEFAULT,
        };

        return $sectionCount <= $max;
    }

    /**
     * @return array{name: string, icon: string}
     */
    private function resolveBlockMeta(string $type): array
    {
        if ($this->blockRegistry !== null) {
            $block = $this->blockRegistry->get($type);
            if ($block !== null) {
                return [
                    'name' => $block->getName(),
                    'icon' => $block->getIcon(),
                ];
            }
        }

        $fallbacks = [
            'heading'     => ['name' => 'Título', 'icon' => '🔤'],
            'paragraph'   => ['name' => 'Texto', 'icon' => '📄'],
            'image'       => ['name' => 'Imagem', 'icon' => '🖼️'],
            'cta_button'  => ['name' => 'Botão CTA', 'icon' => '🚀'],
            'nav_menu'    => ['name' => 'Menu de Navegação', 'icon' => '🧭'],
            'logo'        => ['name' => 'Logotipo', 'icon' => '🏷️'],
            'divider'     => ['name' => 'Divisor', 'icon' => '➖'],
            'video'       => ['name' => 'Vídeo', 'icon' => '🎥'],
            'shortcode'   => ['name' => 'Shortcode', 'icon' => '⚡'],
            'copyright'   => ['name' => 'Copyright', 'icon' => '©️'],
            'custom_code' => ['name' => 'HTML / Código', 'icon' => '💻'],
        ];

        return $fallbacks[$type] ?? ['name' => ucfirst($type), 'icon' => '📦'];
    }
}
