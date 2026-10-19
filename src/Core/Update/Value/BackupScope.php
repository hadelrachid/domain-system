<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\UpdateKind;
use InvalidArgumentException;

final readonly class BackupScope
{
    public function __construct(
        public UpdateKind $kind,
        public string $slug,
    ) {
        if (trim($this->slug) === '') {
            throw new InvalidArgumentException('O slug do BackupScope não pode ser vazio.');
        }
    }

    /**
     * @return array{kind: string, slug: string}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'slug' => $this->slug,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            kind: UpdateKind::from((string)($data['kind'] ?? 'core')),
            slug: (string)($data['slug'] ?? ''),
        );
    }
}
