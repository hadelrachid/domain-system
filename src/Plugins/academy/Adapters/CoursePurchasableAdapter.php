<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Adapters;

use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;

/**
 * Adaptador que transforma um Curso do Academy em um Item Vendável do Commerce (Padrão Adapter / DIP).
 */
class CoursePurchasableAdapter implements PurchasableItemInterface
{
    private array $course;
    private ?string $thumbnailUrl;

    public function __construct(array $course, ?string $thumbnailUrl = null)
    {
        $this->course = $course;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function getId(): int
    {
        return (int)($this->course['id'] ?? 0);
    }

    public function getItemType(): string
    {
        return 'course';
    }

    public function getTitle(): string
    {
        return (string)($this->course['title'] ?? 'Curso sem título');
    }

    public function getPrice(): float
    {
        return (float)($this->course['price'] ?? 0.0);
    }

    public function isAvailable(): bool
    {
        return ($this->course['status'] ?? '') === 'published';
    }

    public function getDescription(): string
    {
        return (string)($this->course['description'] ?? '');
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl ?: ($this->course['image'] ?? null);
    }
}
