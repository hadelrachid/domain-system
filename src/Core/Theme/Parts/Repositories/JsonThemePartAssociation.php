<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Repositories;

use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;

/**
 * Persistência do Mapa de Associação: Tema <-> Header / Footer Ativo (config/theme_parts_associations.json).
 */
class JsonThemePartAssociation implements ThemePartAssociationInterface
{
    private string $filePath;
    private array $data;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT . '/config/theme_parts_associations.json' : __DIR__ . '/../../../../../config/theme_parts_associations.json');
        $this->load();
    }

    private function load(): void
    {
        $this->data = [
            'themes'  => [],
            'globals' => [],
        ];

        if (file_exists($this->filePath)) {
            $raw = @file_get_contents($this->filePath);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $this->data = [
                        'themes'  => is_array($decoded['themes'] ?? null) ? $decoded['themes'] : [],
                        'globals' => is_array($decoded['globals'] ?? null) ? $decoded['globals'] : [],
                    ];
                }
            }
        }
    }

    private function save(): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return @file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }

    public function getAssignedPartId(string $themeFolder, string $partType): ?string
    {
        // 1. Verifica associação direta do tema
        if (isset($this->data['themes'][$themeFolder][$partType])) {
            $id = (string)$this->data['themes'][$themeFolder][$partType];
            if (trim($id) !== '') {
                return $id;
            }
        }

        // 2. Fallback para padrão global (se configurado)
        if (isset($this->data['globals'][$partType])) {
            $globalId = (string)$this->data['globals'][$partType];
            if (trim($globalId) !== '') {
                return $globalId;
            }
        }

        return null;
    }

    public function assignPart(string $themeFolder, string $partType, ?string $partId): bool
    {
        if (!isset($this->data['themes'][$themeFolder])) {
            $this->data['themes'][$themeFolder] = [];
        }

        if ($partId === null || trim($partId) === '') {
            unset($this->data['themes'][$themeFolder][$partType]);
            if (empty($this->data['themes'][$themeFolder])) {
                unset($this->data['themes'][$themeFolder]);
            }
        } else {
            $this->data['themes'][$themeFolder][$partType] = trim($partId);
        }

        return $this->save();
    }

    public function setGlobalDefault(string $partType, ?string $partId): bool
    {
        if ($partId === null || trim($partId) === '') {
            unset($this->data['globals'][$partType]);
        } else {
            $this->data['globals'][$partType] = trim($partId);
        }

        return $this->save();
    }

    public function getGlobalDefault(string $partType): ?string
    {
        return isset($this->data['globals'][$partType]) ? (string)$this->data['globals'][$partType] : null;
    }

    public function getAllAssignments(): array
    {
        return $this->data;
    }
}
