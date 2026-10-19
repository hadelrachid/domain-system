<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\UpdateKind;
use InvalidArgumentException;

final readonly class UpdateTarget
{
    public function __construct(
        public UpdateKind $kind,
        public string $slug,
        public string $repository,
        public string $installedVersion,
        public string $channel = 'stable',
    ) {
        if (trim($this->slug) === '') {
            throw new InvalidArgumentException('O slug do UpdateTarget não pode ser vazio.');
        }
        if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $this->repository) !== 1) {
            throw new InvalidArgumentException('O repositório do UpdateTarget deve estar no formato owner/repo.');
        }
        if (trim($this->installedVersion) === '') {
            throw new InvalidArgumentException('A versão instalada do UpdateTarget não pode ser vazia.');
        }
        if (trim($this->channel) === '') {
            throw new InvalidArgumentException('O canal do UpdateTarget não pode ser vazio.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'kind'             => $this->kind->value,
            'slug'             => $this->slug,
            'repository'       => $this->repository,
            'installedVersion' => $this->installedVersion,
            'channel'          => $this->channel,
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
            repository: (string)($data['repository'] ?? ''),
            installedVersion: (string)($data['installedVersion'] ?? ''),
            channel: (string)($data['channel'] ?? 'stable'),
        );
    }
}
