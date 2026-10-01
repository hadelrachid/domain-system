<?php

namespace DomainSystem\Plugins\clinic_pack\Services;

class ThemeService
{
    private string $uploadsBase;

    public function __construct()
    {
        $this->uploadsBase = defined("DOMAIN_SYSTEM_ROOT")
            ? DOMAIN_SYSTEM_ROOT . "/public/uploads/profiles"
            : dirname(__DIR__, 4) . "/public/uploads/profiles";
    }

    public function getPresets(): array
    {
        return [
            "default" => [
                "label"  => "Verde Clinica",
                "swatch" => "#10b981",
                "colors" => [
                    "primary"       => "#10b981",
                    "primary_hover" => "#059669",
                    "bg_body"       => "#f0f2f5",
                    "bg_card"       => "#ffffff",
                    "bg_card_done"  => "#f8fafc",
                    "bg_header"     => "#ffffff",
                    "text_main"     => "#1d2327",
                    "text_muted"    => "#64748b",
                    "border"        => "#e2e8f0",
                    "btn_action"    => "#10b981",
                    "btn_cancel"    => "#ef4444",
                    "accent"        => "#f59e0b",
                ],
            ],
            "blue" => [
                "label"  => "Azul Clinica",
                "swatch" => "#2271b1",
                "colors" => [
                    "primary"       => "#2271b1",
                    "primary_hover" => "#135e96",
                    "bg_body"       => "#f0f4f8",
                    "bg_card"       => "#ffffff",
                    "bg_card_done"  => "#f0f4f8",
                    "bg_header"     => "#ffffff",
                    "text_main"     => "#1d2327",
                    "text_muted"    => "#64748b",
                    "border"        => "#dbe4f0",
                    "btn_action"    => "#2271b1",
                    "btn_cancel"    => "#ef4444",
                    "accent"        => "#f59e0b",
                ],
            ],
            "dark" => [
                "label"  => "Dark (VS Code)",
                "swatch" => "#1e1e1e",
                "colors" => [
                    "primary"       => "#3b82f6",
                    "primary_hover" => "#2563eb",
                    "bg_body"       => "#1e1e1e",
                    "bg_card"       => "#252526",
                    "bg_card_done"  => "#2d2d30",
                    "bg_header"     => "#323233",
                    "text_main"     => "#e4e4e7",
                    "text_muted"    => "#a1a1aa",
                    "border"        => "#3f3f46",
                    "btn_action"    => "#16a34a",
                    "btn_cancel"    => "#dc2626",
                    "accent"        => "#d97706",
                ],
            ],
            "pink" => [
                "label"  => "Rosa",
                "swatch" => "#ec4899",
                "colors" => [
                    "primary"       => "#ec4899",
                    "primary_hover" => "#be185d",
                    "bg_body"       => "#fdf2f8",
                    "bg_card"       => "#ffffff",
                    "bg_card_done"  => "#fce7f3",
                    "bg_header"     => "#ffffff",
                    "text_main"     => "#831843",
                    "text_muted"    => "#db2777",
                    "border"        => "#fbcfe8",
                    "btn_action"    => "#ec4899",
                    "btn_cancel"    => "#ef4444",
                    "accent"        => "#f97316",
                ],
            ],
            "purple" => [
                "label"  => "Roxo",
                "swatch" => "#8b5cf6",
                "colors" => [
                    "primary"       => "#8b5cf6",
                    "primary_hover" => "#6d28d9",
                    "bg_body"       => "#f5f3ff",
                    "bg_card"       => "#ffffff",
                    "bg_card_done"  => "#ede9fe",
                    "bg_header"     => "#ffffff",
                    "text_main"     => "#2e1065",
                    "text_muted"    => "#7c3aed",
                    "border"        => "#ddd6fe",
                    "btn_action"    => "#8b5cf6",
                    "btn_cancel"    => "#ef4444",
                    "accent"        => "#f59e0b",
                ],
            ],
            "orange" => [
                "label"  => "Laranja",
                "swatch" => "#f97316",
                "colors" => [
                    "primary"       => "#f97316",
                    "primary_hover" => "#c2410c",
                    "bg_body"       => "#fff7ed",
                    "bg_card"       => "#ffffff",
                    "bg_card_done"  => "#ffedd5",
                    "bg_header"     => "#ffffff",
                    "text_main"     => "#7c2d12",
                    "text_muted"    => "#ea580c",
                    "border"        => "#fed7aa",
                    "btn_action"    => "#f97316",
                    "btn_cancel"    => "#ef4444",
                    "accent"        => "#eab308",
                ],
            ],
        ];
    }

    public function loadTheme(int $userId): array
    {
        $path = $this->uploadsBase . "/" . $userId . "/theme.json";
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data) && !empty($data["colors"])) {
                return $data;
            }
        }
        $presets = $this->getPresets();
        return ["base" => "default", "colors" => $presets["default"]["colors"]];
    }

    public function saveTheme(int $userId, string $base, array $colors): void
    {
        $dir = $this->uploadsBase . "/" . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $data = ["base" => $base, "colors" => $colors];
        file_put_contents($dir . "/theme.json", json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getPresetColors(string $presetName): array
    {
        $presets = $this->getPresets();
        return $presets[$presetName]["colors"] ?? $presets["default"]["colors"];
    }
}
