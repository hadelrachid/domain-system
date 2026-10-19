<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\CheckLevel;
use InvalidArgumentException;

final readonly class PreflightReport
{
    /**
     * @param list<PreflightCheck> $checks
     */
    public function __construct(
        public array $checks,
    ) {
        foreach ($this->checks as $check) {
            if (!$check instanceof PreflightCheck) {
                throw new InvalidArgumentException('Todos os itens de PreflightReport::checks devem ser instâncias de PreflightCheck.');
            }
        }
    }

    public function canProceed(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->level === CheckLevel::Block) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<PreflightCheck>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->checks,
            static fn(PreflightCheck $check): bool => $check->level === CheckLevel::Warn,
        ));
    }

    /**
     * @return list<PreflightCheck>
     */
    public function blocks(): array
    {
        return array_values(array_filter(
            $this->checks,
            static fn(PreflightCheck $check): bool => $check->level === CheckLevel::Block,
        ));
    }

    /**
     * @return array{canProceed: bool, checks: list<array{id: string, level: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'canProceed' => $this->canProceed(),
            'checks'     => array_values(array_map(
                static fn(PreflightCheck $check): array => $check->toArray(),
                $this->checks,
            )),
        ];
    }
}
