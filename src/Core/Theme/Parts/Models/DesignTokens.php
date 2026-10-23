<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Models;

use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;

/**
 * Gestão Centralizada de Design Tokens Globais (Paleta de Cores e Tipografia).
 */
class DesignTokens implements DesignTokensInterface
{
    private string $storagePath;
    private array $palette;
    private array $typography;

    private const DEFAULT_PALETTE = [
        'primary'    => '#45f3ff',
        'secondary'  => '#1f2833',
        'tertiary'   => '#60a5fa',
        'background' => '#0b0c10',
        'surface'    => 'rgba(31, 40, 51, 0.7)',
        'text_main'  => '#c5c6c7',
        'text_muted' => '#8b8c8d',
        'border'     => 'rgba(255, 255, 255, 0.1)',
    ];

    private const DEFAULT_TYPOGRAPHY = [
        'primary_font'   => 'Inter, sans-serif',
        'secondary_font' => 'Fira Code, monospace',
        'tertiary_font'  => 'Montserrat, sans-serif',
        'base_size'      => '16px',
        'heading_weight' => '800',
        'body_weight'    => '400',
    ];

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT . '/config/design_tokens.json' : __DIR__ . '/../../../../../config/design_tokens.json');
        $this->load();
    }

    private function load(): void
    {
        $this->palette = self::DEFAULT_PALETTE;
        $this->typography = self::DEFAULT_TYPOGRAPHY;

        if (file_exists($this->storagePath)) {
            $raw = @file_get_contents($this->storagePath);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    if (isset($decoded['palette']) && is_array($decoded['palette'])) {
                        $this->palette = array_merge(self::DEFAULT_PALETTE, $decoded['palette']);
                    }
                    if (isset($decoded['typography']) && is_array($decoded['typography'])) {
                        $this->typography = array_merge(self::DEFAULT_TYPOGRAPHY, $decoded['typography']);
                    }
                }
            }
        }
    }

    public function getColorPalette(): array
    {
        return $this->palette;
    }

    public function getTypography(): array
    {
        return $this->typography;
    }

    public function generateCssVariables(): string
    {
        $vars = [];
        foreach ($this->palette as $key => $val) {
            $cssKey = str_replace('_', '-', $key);
            $vars[] = "    --color-{$cssKey}: {$val};";
            if ($key === 'primary') {
                $vars[] = "    --primary: {$val};";
            }
            if ($key === 'secondary') {
                $vars[] = "    --secondary: {$val};";
            }
            if ($key === 'background') {
                $vars[] = "    --bg-color: {$val};";
            }
            if ($key === 'surface') {
                $vars[] = "    --surface: {$val};";
            }
            if ($key === 'text_main') {
                $vars[] = "    --text-main: {$val};";
            }
            if ($key === 'text_muted') {
                $vars[] = "    --text-muted: {$val};";
            }
        }

        foreach ($this->typography as $key => $val) {
            $cssKey = str_replace('_', '-', $key);
            $vars[] = "    --font-{$cssKey}: {$val};";
            if ($key === 'primary_font') {
                $vars[] = "    --font-primary: {$val};";
            }
            if ($key === 'secondary_font') {
                $vars[] = "    --font-secondary: {$val};";
            }
            if ($key === 'tertiary_font') {
                $vars[] = "    --font-tertiary: {$val};";
            }
        }

        return ":root {\n" . implode("\n", $vars) . "\n}";
    }

    public function updateTokens(array $tokens): bool
    {
        if (isset($tokens['palette']) && is_array($tokens['palette'])) {
            $this->palette = array_merge($this->palette, $tokens['palette']);
        }
        if (isset($tokens['typography']) && is_array($tokens['typography'])) {
            $this->typography = array_merge($this->typography, $tokens['typography']);
        }

        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'palette'    => $this->palette,
            'typography' => $this->typography,
            'updated_at' => time(),
        ];

        return @file_put_contents(
            $this->storagePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }
}
