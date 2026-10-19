<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\UpdateStatus;
use InvalidArgumentException;

final readonly class UpdateCheckResult
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public UpdateStatus $status,
        public UpdateTarget $target,
        public ?ReleaseInfo $release = null,
        public array $reasons = [],
        public string $checkedAt = '',
    ) {
        if (trim($this->checkedAt) === '') {
            throw new InvalidArgumentException('O campo checkedAt de UpdateCheckResult não pode ser vazio.');
        }
        foreach ($this->reasons as $reason) {
            if (!is_string($reason) || trim($reason) === '') {
                throw new InvalidArgumentException('Cada motivo em reasons deve ser uma string não vazia.');
            }
        }
    }
}
