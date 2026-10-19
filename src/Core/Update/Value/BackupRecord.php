<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class BackupRecord
{
    public function __construct(
        public string $id,
        public BackupScope $scope,
        public string $label,
        public string $createdAt,
        public string $codePath,
        public ?string $dumpPath = null,
    ) {
        if (trim($this->id) === '') {
            throw new InvalidArgumentException('O id do BackupRecord não pode ser vazio.');
        }
        if (trim($this->label) === '') {
            throw new InvalidArgumentException('O rótulo do BackupRecord não pode ser vazio.');
        }
        if (trim($this->createdAt) === '') {
            throw new InvalidArgumentException('A data de criação do BackupRecord não pode ser vazia.');
        }
        if (trim($this->codePath) === '') {
            throw new InvalidArgumentException('O codePath do BackupRecord não pode ser vazio.');
        }
        if ($this->dumpPath !== null && trim($this->dumpPath) === '') {
            throw new InvalidArgumentException('O dumpPath do BackupRecord não pode ser string vazia.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'scope'     => $this->scope->toArray(),
            'label'     => $this->label,
            'createdAt' => $this->createdAt,
            'codePath'  => $this->codePath,
            'dumpPath'  => $this->dumpPath,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $scopeData = is_array($data['scope'] ?? null) ? $data['scope'] : [];

        return new self(
            id: (string)($data['id'] ?? ''),
            scope: BackupScope::fromArray($scopeData),
            label: (string)($data['label'] ?? ''),
            createdAt: (string)($data['createdAt'] ?? ''),
            codePath: (string)($data['codePath'] ?? ''),
            dumpPath: isset($data['dumpPath']) && $data['dumpPath'] !== '' ? (string)$data['dumpPath'] : null,
        );
    }
}
