<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Icons\SvgIconRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Icons\DefaultSvgIconsFactory;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;

/**
 * Bloco de Redes Sociais com Vetores SVG Nativos Puros (SOLID - SRP / OCP).
 * 
 * Suporta formatos vetoriais puros (sem dependência de fontes externas como FontAwesome),
 * cores oficiais das marcas ou cores conectadas aos Design Tokens, 4 formatos de silhueta
 * (circle, rounded, square, minimal) e micro-interações de hover.
 */
class SocialIconsBlock implements BlockInterface, StylableElementInterface
{
    use StylableElementTrait;

    private SvgIconRegistryInterface $iconRegistry;

    public function __construct(?SvgIconRegistryInterface $iconRegistry = null)
    {
        $this->iconRegistry = $iconRegistry ?? DefaultSvgIconsFactory::create();
    }

    public function getType(): string
    {
        return 'social_icons';
    }

    public function getName(): string
    {
        return 'Redes Sociais (SVG Vetorial)';
    }

    public function getIcon(): string
    {
        return '🌐';
    }

    public function getCategory(): string
    {
        return 'contact';
    }

    public function render(array $data = [], array $context = []): string
    {
        $this->styleData = $data;

        $networksRaw = $data['networks'] ?? [
            ['network' => 'instagram', 'url' => 'https://instagram.com'],
            ['network' => 'whatsapp',  'url' => 'https://wa.me/5511999999999'],
            ['network' => 'youtube',   'url' => 'https://youtube.com'],
        ];

        // Normalização defensiva: aceita array associativo clássico ['github' => 'url'] ou lista de objetos
        $itemsList = [];
        if (is_array($networksRaw)) {
            foreach ($networksRaw as $key => $val) {
                if (is_array($val)) {
                    $net = (string)($val['network'] ?? '');
                    $url = (string)($val['url'] ?? '');
                } else {
                    $net = (string)$key;
                    $url = (string)$val;
                }
                $net = strtolower(trim($net));
                $url = trim($url);
                if ($net !== '' && $url !== '') {
                    $itemsList[] = ['network' => $net, 'url' => $url];
                }
            }
        }

        if (empty($itemsList)) {
            return '';
        }

        $shape = (string)($data['shape'] ?? 'circle');
        $colorMode = (string)($data['color_mode'] ?? 'official'); // 'official' | 'custom'
        $hoverAnim = (string)($data['hover_animation'] ?? 'lift');
        $align = (string)($data['align'] ?? 'center');
        $gap = (string)($data['gap'] ?? '12px');
        $iconSize = (int)preg_replace('/[^0-9]/', '', (string)($data['icon_size'] ?? '20')) ?: 20;
        $buttonSize = (int)preg_replace('/[^0-9]/', '', (string)($data['button_size'] ?? '40')) ?: 40;

        // Resolução de cores personalizadas via Design Tokens
        $customIconColor = ColorValue::fromString((string)($data['icon_color'] ?? '#ffffff'))->toCss();
        $customBgColor = ColorValue::fromString((string)($data['bg_color'] ?? 'var(--color-surface, #1f2833)'))->toCss();

        $borderRadius = match ($shape) {
            'circle'  => '50%',
            'rounded' => '10px',
            'square'  => '0px',
            default   => '0px',
        };

        $animClass = match ($hoverAnim) {
            'grow'   => 'ds-btn-grow',
            'lift'   => 'ds-btn-lift',
            'glow'   => 'ds-btn-glow',
            'bounce' => 'ds-btn-bounce',
            default  => '',
        };

        $justify = match ($align) {
            'left'   => 'flex-start',
            'right'  => 'flex-end',
            default  => 'center',
        };

        $renderedIcons = [];
        foreach ($itemsList as $item) {
            $net = $item['network'];
            $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
            $label = $this->iconRegistry->getLabel($net) ?? ucfirst($net);
            $officialBrandColor = $this->iconRegistry->getBrandColor($net) ?? '#38bdf8';

            if ($colorMode === 'official') {
                if ($shape === 'minimal') {
                    $itemFill = $officialBrandColor;
                    $itemBg = 'transparent';
                } else {
                    $itemFill = '#ffffff';
                    $itemBg = $officialBrandColor;
                }
            } else {
                $itemFill = $customIconColor;
                $itemBg = ($shape === 'minimal') ? 'transparent' : $customBgColor;
            }

            $svgHtml = $this->iconRegistry->getSvg($net, [
                'width'  => (string)$iconSize,
                'height' => (string)$iconSize,
                'fill'   => $itemFill,
            ]);

            if ($svgHtml === null) {
                // Fallback seguro caso seja uma rede desconhecida
                $svgHtml = $this->iconRegistry->getSvg('website', [
                    'width'  => (string)$iconSize,
                    'height' => (string)$iconSize,
                    'fill'   => $itemFill,
                ]);
            }

            $btnStyle = "display: inline-flex; align-items: center; justify-content: center; width: {$buttonSize}px; height: {$buttonSize}px; border-radius: {$borderRadius}; background: {$itemBg}; text-decoration: none; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);";

            $renderedIcons[] = "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"{$label}\" class=\"ds-social-btn {$animClass}\" style=\"{$btnStyle}\">{$svgHtml}</a>";
        }

        $iconsOutput = implode("\n", $renderedIcons);

        return <<<HTML
<div class="ds-block-social-wrapper" style="display: flex; gap: {$gap}; justify-content: {$justify}; align-items: center; flex-wrap: wrap; width: 100%;">
    {$iconsOutput}
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'social_icons',
            'fields' => [
                'networks' => [
                    'type'    => 'repeater',
                    'label'   => 'Redes Sociais',
                    'default' => [
                        ['network' => 'instagram', 'url' => 'https://instagram.com'],
                        ['network' => 'whatsapp',  'url' => 'https://wa.me/5511999999999'],
                        ['network' => 'youtube',   'url' => 'https://youtube.com'],
                    ],
                ],
                'shape' => [
                    'type'    => 'select',
                    'label'   => 'Formato da Silhueta',
                    'options' => [
                        'circle'  => 'Círculo (50%)',
                        'rounded' => 'Cantos Arredondados (Rounded)',
                        'square'  => 'Quadrado (Square)',
                        'minimal' => 'Minimalista (Sem Fundo)',
                    ],
                    'default' => 'circle',
                ],
                'color_mode' => [
                    'type'    => 'select',
                    'label'   => 'Esquema de Cores',
                    'options' => [
                        'official' => 'Cores Oficiais das Marcas',
                        'custom'   => 'Cores Personalizadas (Design Tokens)',
                    ],
                    'default' => 'official',
                ],
                'icon_color' => [
                    'type'    => 'color',
                    'label'   => 'Cor do Ícone (Modo Custom)',
                    'default' => '#ffffff',
                ],
                'bg_color' => [
                    'type'    => 'color',
                    'label'   => 'Cor de Fundo (Modo Custom)',
                    'default' => 'var(--color-surface)',
                ],
                'hover_animation' => [
                    'type'    => 'select',
                    'label'   => 'Animação no Hover',
                    'options' => [
                        'lift'   => 'Elevar com Sombra (Lift)',
                        'grow'   => 'Aumentar Escala (Grow)',
                        'glow'   => 'Brilho Fluorescente (Glow)',
                        'bounce' => 'Salto Suave (Bounce)',
                        'none'   => 'Nenhuma Animação',
                    ],
                    'default' => 'lift',
                ],
                'icon_size' => [
                    'type'    => 'dimension',
                    'label'   => 'Tamanho do Ícone',
                    'default' => '20px',
                ],
                'button_size' => [
                    'type'    => 'dimension',
                    'label'   => 'Tamanho do Botão',
                    'default' => '40px',
                ],
                'gap' => [
                    'type'    => 'dimension',
                    'label'   => 'Espaçamento Entre Ícones',
                    'default' => '12px',
                ],
                'align' => [
                    'type'    => 'select',
                    'label'   => 'Alinhamento',
                    'options' => ['left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita'],
                    'default' => 'center',
                ],
            ],
        ];
    }
}
