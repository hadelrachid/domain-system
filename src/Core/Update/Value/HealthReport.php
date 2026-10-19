<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class HealthReport
{
    /**
     * @param list<PreflightCheck|array{id: string, ok: bool, message: string}> $checks
     */
    public function __construct(
        public bool $ok,
        public array $checks,
    ) {
        foreach ($this->checks as $check) {
            if ($check instanceof PreflightCheck) {
                continue;
            }
            if (
                !is_array($check)
                || !isset($check['id'], $check['ok'], $check['message'])
                || !is_string($check['id'])
                || trim($check['id']) === ''
                || !is_bool($check['ok'])
                || !is_string($check['message'])
                || trim($check['message']) === ''
            ) {
                throw new InvalidArgumentException('Item inválido em HealthReport::checks.');
            }
        }
    }

    /**
     * @return array{ok: bool, checks: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'ok'     => $this->ok,
            'checks' => array_values(array_map(
                static fn(PreflightCheck|array $check): array => $check instanceof PreflightCheck ? $check->toArray() : $check,
                $this->checks,
            )),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawChecks = is_array($data['checks'] ?? null) ? $data['checks'] : [];
        $parsed = [];
        foreach ($rawChecks as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['level'])) {
                $parsed[] = PreflightCheck::fromArray($item);
            } else {
                $parsed[] = [
                    'id'      => (string)($item['id'] ?? ''),
                    'ok'      => (bool)($item['ok'] ?? false),
                    'message' => (string)($item['message'] ?? ''),
                ];
            }
        }

        return new self(
            ok: (bool)($data['ok'] ?? false),
            checks: $parsed,
        );
    }
}
