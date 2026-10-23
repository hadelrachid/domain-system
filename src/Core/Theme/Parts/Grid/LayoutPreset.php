<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetInterface;

/**
 * Value Object Imutável para Definição de Preset de Layout (SOLID - SRP).
 */
class LayoutPreset implements LayoutPresetInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly int $columns,
        private readonly string $desktopTemplate,
        private readonly string $tabletTemplate,
        private readonly string $mobileTemplate,
        private readonly string $category = 'grid',
        private readonly string $icon = '📐'
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function getDesktopTemplate(): string
    {
        return $this->desktopTemplate;
    }

    public function getTabletTemplate(): string
    {
        return $this->tabletTemplate;
    }

    public function getMobileTemplate(): string
    {
        return $this->mobileTemplate;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'columns'  => $this->columns,
            'template' => $this->desktopTemplate,
            'tablet'   => $this->tabletTemplate,
            'mobile'   => $this->mobileTemplate,
            'category' => $this->category,
            'icon'     => $this->icon,
        ];
    }
}
