<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Icons;

use DomainSystem\Core\Theme\Parts\Contracts\Icons\SvgIconRegistryInterface;

/**
 * Registro e Compilador de Ícones Vetoriais SVG Nativos (SOLID - SRP / OCP).
 */
class SvgIconRegistry implements SvgIconRegistryInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $icons = [];

    public function register(
        string $name,
        string $viewBox,
        string $pathData,
        ?string $brandColor = null,
        ?string $label = null
    ): void {
        $cleanName = strtolower(trim($name));
        $this->icons[$cleanName] = [
            'name'       => $cleanName,
            'viewBox'    => trim($viewBox),
            'path'       => trim($pathData),
            'brandColor' => $brandColor !== null ? trim($brandColor) : null,
            'label'      => $label !== null ? trim($label) : ucfirst($cleanName),
        ];
    }

    public function getSvg(string $name, array $attributes = []): ?string
    {
        $cleanName = strtolower(trim($name));
        $def = $this->icons[$cleanName] ?? null;
        if ($def === null) {
            return null;
        }

        $viewBox = htmlspecialchars($def['viewBox'], ENT_QUOTES, 'UTF-8');
        $width = $attributes['width'] ?? '24';
        $height = $attributes['height'] ?? '24';
        $fill = $attributes['fill'] ?? 'currentColor';

        $attrs = [
            'xmlns'       => 'http://www.w3.org/2000/svg',
            'viewBox'     => $viewBox,
            'width'       => $width,
            'height'      => $height,
            'fill'        => $fill,
            'aria-hidden' => 'true',
            'focusable'   => 'false',
        ];

        // Mescla atributos adicionais
        foreach ($attributes as $k => $v) {
            if (!in_array($k, ['width', 'height', 'fill', 'xmlns', 'viewBox'], true)) {
                $attrs[$k] = $v;
            }
        }

        $compiledAttrs = [];
        foreach ($attrs as $attrKey => $attrVal) {
            $safeKey = preg_replace('/[^a-z0-9_-]/i', '', $attrKey) ?? '';
            $safeVal = htmlspecialchars((string)$attrVal, ENT_QUOTES, 'UTF-8');
            $compiledAttrs[] = "{$safeKey}=\"{$safeVal}\"";
        }
        $attrsStr = implode(' ', $compiledAttrs);

        return "<svg {$attrsStr}>{$def['path']}</svg>";
    }

    public function getBrandColor(string $name): ?string
    {
        $cleanName = strtolower(trim($name));
        return $this->icons[$cleanName]['brandColor'] ?? null;
    }

    public function getLabel(string $name): ?string
    {
        $cleanName = strtolower(trim($name));
        return $this->icons[$cleanName]['label'] ?? null;
    }

    public function has(string $name): bool
    {
        $cleanName = strtolower(trim($name));
        return isset($this->icons[$cleanName]);
    }

    public function all(): array
    {
        return $this->icons;
    }

    public function getAvailableIcons(): array
    {
        $list = [];
        foreach ($this->icons as $k => $v) {
            $list[$k] = $v['label'];
        }
        return $list;
    }
}
