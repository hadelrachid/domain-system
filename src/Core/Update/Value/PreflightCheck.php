<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\CheckLevel;
use InvalidArgumentException;

final readonly class PreflightCheck
{
    public function __construct(
        public string $id,
        public CheckLevel $level,
        public string $message,
    ) {
        if (trim($this->id) === '') {
            throw new InvalidArgumentException('O id do PreflightCheck não pode ser vazio.');
        }
        if (trim($this->message) === '') {
            throw new InvalidArgumentException('A mensagem do PreflightCheck não pode ser vazia.');
        }
    }

    /**
     * @return array{id: string, level: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'id'      => $this->id,
            'level'   => $this->level->value,
            'message' => $this->message,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string)($data['id'] ?? ''),
            level: CheckLevel::from((string)($data['level'] ?? 'block')),
            message: (string)($data['message'] ?? ''),
        );
    }
}
