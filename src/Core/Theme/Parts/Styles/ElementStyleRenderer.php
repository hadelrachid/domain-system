<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Styles;

use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;

/**
 * Renderizador resiliente e higienizador de estilos CSS para blocos e elementos (SOLID - SRP).
 */
class ElementStyleRenderer implements ElementStyleRendererInterface
{
    public function renderStyleString(array $data): string
    {
        $rules = [];

        // Margem e Padding
        if (!empty($data['margin'])) {
            $rules[] = 'margin: ' . $this->sanitizeCssValue((string)$data['margin']);
        }
        if (!empty($data['padding'])) {
            $rules[] = 'padding: ' . $this->sanitizeCssValue((string)$data['padding']);
        }

        // Flex & Flexbox
        if (!empty($data['display'])) {
            $rules[] = 'display: ' . $this->sanitizeCssValue((string)$data['display']);
        }
        if (!empty($data['flex_direction'])) {
            $rules[] = 'flex-direction: ' . $this->sanitizeCssValue((string)$data['flex_direction']);
        }
        if (!empty($data['justify_content'])) {
            $rules[] = 'justify-content: ' . $this->sanitizeCssValue((string)$data['justify_content']);
        }
        if (!empty($data['align_items'])) {
            $rules[] = 'align-items: ' . $this->sanitizeCssValue((string)$data['align_items']);
        }
        if (!empty($data['flex_wrap'])) {
            $rules[] = 'flex-wrap: ' . $this->sanitizeCssValue((string)$data['flex_wrap']);
        }
        if (!empty($data['gap'])) {
            $rules[] = 'gap: ' . $this->sanitizeCssValue((string)$data['gap']);
        }

        // Dimensões & Visual
        if (!empty($data['width'])) {
            $rules[] = 'width: ' . $this->sanitizeCssValue((string)$data['width']);
        }
        if (!empty($data['max_width'])) {
            $rules[] = 'max-width: ' . $this->sanitizeCssValue((string)$data['max_width']);
        }
        if (!empty($data['height'])) {
            $rules[] = 'height: ' . $this->sanitizeCssValue((string)$data['height']);
        }
        if (!empty($data['min_height'])) {
            $rules[] = 'min-height: ' . $this->sanitizeCssValue((string)$data['min_height']);
        }
        if (!empty($data['background'])) {
            $rules[] = 'background: ' . $this->sanitizeCssValue((string)$data['background']);
        }
        if (!empty($data['color'])) {
            $rules[] = 'color: ' . $this->sanitizeCssValue((string)$data['color']);
        }
        if (!empty($data['border'])) {
            $rules[] = 'border: ' . $this->sanitizeCssValue((string)$data['border']);
        }
        if (!empty($data['border_width'])) {
            $rules[] = 'border-width: ' . $this->sanitizeCssValue((string)$data['border_width']);
        }
        if (!empty($data['border_style'])) {
            $rules[] = 'border-style: ' . $this->sanitizeCssValue((string)$data['border_style']);
        }
        if (!empty($data['border_color'])) {
            $rules[] = 'border-color: ' . $this->sanitizeCssValue((string)$data['border_color']);
        }
        if (!empty($data['border_radius'])) {
            $rules[] = 'border-radius: ' . $this->sanitizeCssValue((string)$data['border_radius']);
        }
        if (!empty($data['box_shadow'])) {
            $rules[] = 'box-shadow: ' . $this->sanitizeCssValue((string)$data['box_shadow']);
        }
        if (!empty($data['opacity']) && $data['opacity'] !== '1' && $data['opacity'] !== 1) {
            $rules[] = 'opacity: ' . $this->sanitizeCssValue((string)$data['opacity']);
        }

        // Tipografia
        if (!empty($data['font_family'])) {
            $rules[] = 'font-family: ' . $this->sanitizeCssValue((string)$data['font_family']);
        }
        if (!empty($data['font_size'])) {
            $rules[] = 'font-size: ' . $this->sanitizeCssValue((string)$data['font_size']);
        }
        if (!empty($data['font_weight'])) {
            $rules[] = 'font-weight: ' . $this->sanitizeCssValue((string)$data['font_weight']);
        }
        if (!empty($data['line_height'])) {
            $rules[] = 'line-height: ' . $this->sanitizeCssValue((string)$data['line_height']);
        }
        if (!empty($data['letter_spacing'])) {
            $rules[] = 'letter-spacing: ' . $this->sanitizeCssValue((string)$data['letter_spacing']);
        }
        if (!empty($data['text_transform'])) {
            $rules[] = 'text-transform: ' . $this->sanitizeCssValue((string)$data['text_transform']);
        }
        if (!empty($data['text_align'])) {
            $rules[] = 'text-align: ' . $this->sanitizeCssValue((string)$data['text_align']);
        }

        return !empty($rules) ? implode('; ', $rules) . ';' : '';
    }

    public function getResponsiveVisibilityClasses(array $data): string
    {
        $classes = [];
        if (!empty($data['hide_desktop']) && $data['hide_desktop'] !== 'false' && $data['hide_desktop'] !== '0') {
            $classes[] = 'ds-hide-desktop';
        }
        $hideLaptop = $data['hide_laptop'] ?? $data['hide_1024'] ?? false;
        if (!empty($hideLaptop) && $hideLaptop !== 'false' && $hideLaptop !== '0') {
            $classes[] = 'ds-hide-laptop';
        }
        $hideTablet = $data['hide_tablet'] ?? $data['hide_768'] ?? false;
        if (!empty($hideTablet) && $hideTablet !== 'false' && $hideTablet !== '0') {
            $classes[] = 'ds-hide-tablet';
        }
        $hideMobile = $data['hide_mobile'] ?? $data['hide_375'] ?? false;
        if (!empty($hideMobile) && $hideMobile !== 'false' && $hideMobile !== '0') {
            $classes[] = 'ds-hide-mobile';
        }

        return implode(' ', $classes);
    }

    public function parse4Sides(string $spacing): array
    {
        $parts = preg_split('/\s+/', trim($spacing));
        if ($parts === false || empty($parts) || $parts[0] === '') {
            return ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'];
        }

        return match (count($parts)) {
            1 => ['top' => $parts[0], 'right' => $parts[0], 'bottom' => $parts[0], 'left' => $parts[0]],
            2 => ['top' => $parts[0], 'right' => $parts[1], 'bottom' => $parts[0], 'left' => $parts[1]],
            3 => ['top' => $parts[0], 'right' => $parts[1], 'bottom' => $parts[2], 'left' => $parts[1]],
            default => ['top' => $parts[0], 'right' => $parts[1], 'bottom' => $parts[2], 'left' => $parts[3]],
        };
    }

    public function sanitizeCssValue(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        // Bloqueia expressões maliciosas de CSS (expression, javascript:, vbscript:)
        $lower = strtolower($trimmed);
        if (
            str_contains($lower, 'expression(') ||
            str_contains($lower, 'javascript:') ||
            str_contains($lower, 'vbscript:') ||
            str_contains($lower, '@import')
        ) {
            return '';
        }

        // Higienização contra injeção de CSS ou quebra de sintaxe
        $sanitized = preg_replace('/[;<>{}\\\\]/', '', $trimmed) ?? '';
        return htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
    }
}
