<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Models;

use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use InvalidArgumentException;

/**
 * Modelo de Dados Imutável de uma Parte Estrutural de Tema.
 */
class ThemePart implements ThemePartInterface
{
    private string $id;
    private string $type;
    private string $title;
    private string $layoutPreset;
    private array $containerSettings;
    private array $columnsData;
    private string $mobileToggleStyle;
    private bool $isDefault;
    private int $createdAt;
    private int $updatedAt;

    public function __construct(
        string $id,
        string $type,
        string $title,
        string $layoutPreset = '1_col',
        array $containerSettings = [],
        array $columnsData = [],
        string $mobileToggleStyle = MobileToggleStyleInterface::STYLE_CLASSIC,
        bool $isDefault = false,
        ?int $createdAt = null,
        ?int $updatedAt = null
    ) {
        $cleanId = trim($id);
        if ($cleanId === '') {
            throw new InvalidArgumentException("O ID da parte de tema não pode ser vazio.");
        }

        $validTypes = [self::TYPE_HEADER, self::TYPE_FOOTER, self::TYPE_SECTION];
        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Tipo de parte de tema inválido: '{$type}'.");
        }

        $this->id = $cleanId;
        $this->type = $type;
        $this->title = trim($title) !== '' ? trim($title) : 'Parte Sem Título';
        $this->layoutPreset = $layoutPreset;
        $this->containerSettings = $containerSettings;
        $this->columnsData = $columnsData;
        $this->mobileToggleStyle = in_array($mobileToggleStyle, [
            MobileToggleStyleInterface::STYLE_CLASSIC,
            MobileToggleStyleInterface::STYLE_PILL,
            MobileToggleStyleInterface::STYLE_MINIMAL,
            MobileToggleStyleInterface::STYLE_DOTS
        ], true) ? $mobileToggleStyle : MobileToggleStyleInterface::STYLE_CLASSIC;
        $this->isDefault = $isDefault;
        $this->createdAt = $createdAt ?? time();
        $this->updatedAt = $updatedAt ?? time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLayoutPreset(): string
    {
        return $this->layoutPreset;
    }

    public function getContainerSettings(): array
    {
        return $this->containerSettings;
    }

    public function getColumnsData(): array
    {
        return $this->columnsData;
    }

    public function getMobileToggleStyle(): string
    {
        return $this->mobileToggleStyle;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'type'                => $this->type,
            'title'               => $this->title,
            'layout_preset'       => $this->layoutPreset,
            'container_settings'  => $this->containerSettings,
            'columns_data'        => $this->columnsData,
            'mobile_toggle_style' => $this->mobileToggleStyle,
            'is_default'          => $this->isDefault,
            'created_at'          => $this->createdAt,
            'updated_at'          => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['id'] ?? uniqid('part_', true)),
            (string)($data['type'] ?? self::TYPE_HEADER),
            (string)($data['title'] ?? 'Sem Título'),
            (string)($data['layout_preset'] ?? '1_col'),
            (array)($data['container_settings'] ?? []),
            (array)($data['columns_data'] ?? []),
            (string)($data['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC),
            (bool)($data['is_default'] ?? false),
            isset($data['created_at']) ? (int)$data['created_at'] : null,
            isset($data['updated_at']) ? (int)$data['updated_at'] : null
        );
    }
}
