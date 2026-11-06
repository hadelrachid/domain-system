<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Styles\Traits;

use DomainSystem\Core\Theme\Parts\Contracts\Styles\ShadowContractInterface;
use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;
use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;

/**
 * Trait de implementação padrão e reutilizável para StylableElementInterface (SOLID - DRY / Ring 0).
 */
trait StylableElementTrait
{
    /** @var array<string, mixed> */
    protected array $styleData = [];

    /**
     * Define ou atualiza os dados de estilo do elemento.
     * @param array<string, mixed> $data
     * @return static
     */
    public function setStyleData(array $data): static
    {
        $this->styleData = $data;
        return $this;
    }

    public function getBackgroundColor(): ?ColorValue
    {
        $raw = $this->styleData['background'] ?? ($this->styleData['bg_color'] ?? null);
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getTextColor(): ?ColorValue
    {
        $raw = $this->styleData['text_color'] ?? ($this->styleData['color'] ?? null);
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getHeadingColor(): ?ColorValue
    {
        $raw = $this->styleData['heading_color'] ?? ($this->styleData['title_color'] ?? null);
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getLinkColor(): ?ColorValue
    {
        $raw = $this->styleData['link_color'] ?? null;
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getLinkHoverColor(): ?ColorValue
    {
        $raw = $this->styleData['link_hover_color'] ?? ($this->styleData['active_color'] ?? null);
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getBorderColor(): ?ColorValue
    {
        $raw = $this->styleData['border_color'] ?? null;
        return $raw !== null && (string)$raw !== '' ? ColorValue::fromString((string)$raw) : null;
    }

    public function getFontFamily(): ?string
    {
        if (!isset($this->styleData['font_family']) || (string)$this->styleData['font_family'] === '') {
            return null;
        }
        $raw = (string)$this->styleData['font_family'];
        return match ($raw) {
            'primary'   => 'var(--font-primary, sans-serif)',
            'secondary' => 'var(--font-secondary, monospace)',
            'tertiary'  => 'var(--font-tertiary, sans-serif)',
            default     => $raw,
        };
    }

    public function getFontSize(): ?DimensionValue
    {
        $raw = $this->styleData['font_size'] ?? ($this->styleData['size'] ?? null);
        return $raw !== null && (string)$raw !== '' ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getFontWeight(): ?string
    {
        return isset($this->styleData['font_weight']) && (string)$this->styleData['font_weight'] !== ''
            ? (string)$this->styleData['font_weight']
            : null;
    }

    public function getLineHeight(): ?string
    {
        return isset($this->styleData['line_height']) && (string)$this->styleData['line_height'] !== ''
            ? (string)$this->styleData['line_height']
            : null;
    }

    public function getLetterSpacing(): ?DimensionValue
    {
        $raw = $this->styleData['letter_spacing'] ?? null;
        return $raw !== null && (string)$raw !== '' ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getTextTransform(): ?string
    {
        return isset($this->styleData['text_transform']) && (string)$this->styleData['text_transform'] !== ''
            ? (string)$this->styleData['text_transform']
            : null;
    }

    public function getTextAlign(): ?string
    {
        $raw = $this->styleData['text_align'] ?? ($this->styleData['align'] ?? null);
        return $raw !== null && (string)$raw !== '' ? (string)$raw : null;
    }

    public function getWidth(): ?DimensionValue
    {
        $raw = $this->styleData['width'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getMaxWidth(): ?DimensionValue
    {
        $raw = $this->styleData['max_width'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getHeight(): ?DimensionValue
    {
        $raw = $this->styleData['height'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getMinHeight(): ?DimensionValue
    {
        $raw = $this->styleData['min_height'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getBorderStyle(): ?string
    {
        return isset($this->styleData['border_style']) ? (string)$this->styleData['border_style'] : null;
    }

    public function getBorderWidth(): ?DimensionValue
    {
        $raw = $this->styleData['border_width'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getBorderRadius(): ?DimensionValue
    {
        $raw = $this->styleData['border_radius'] ?? null;
        return $raw !== null ? DimensionValue::fromString((string)$raw) : null;
    }

    public function getShadowPreset(): string
    {
        return (string)($this->styleData['shadow_preset'] ?? ShadowContractInterface::SHADOW_NONE);
    }

    public function getBoxShadowCss(): ?string
    {
        return isset($this->styleData['box_shadow']) ? (string)$this->styleData['box_shadow'] : null;
    }

    public function getHoverEffect(): ?string
    {
        return isset($this->styleData['hover_animation']) 
            ? (string)$this->styleData['hover_animation'] 
            : (isset($this->styleData['hover_effect']) ? (string)$this->styleData['hover_effect'] : null);
    }

    public function getEntranceAnimation(): ?string
    {
        return isset($this->styleData['entrance_animation']) ? (string)$this->styleData['entrance_animation'] : null;
    }

    public function getTransitionDuration(): ?string
    {
        return (string)($this->styleData['transition_duration'] ?? '0.25s');
    }

    public function getTimingFunction(): ?string
    {
        return (string)($this->styleData['timing_function'] ?? 'ease');
    }

    public function getStyleProperties(): array
    {
        return $this->styleData;
    }
}
