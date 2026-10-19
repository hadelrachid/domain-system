<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\UpdateStep;
use InvalidArgumentException;

final readonly class UpdateState
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $updateId,
        public UpdateTarget $target,
        public UpdateStep $step,
        public ?ReleaseInfo $release = null,
        public ?string $etag = null,
        public ?string $lastCheckAt = null,
        public array $context = [],
        public ?string $error = null,
        public string $updatedAt = '',
    ) {
        if (trim($this->updateId) === '') {
            throw new InvalidArgumentException('O updateId de UpdateState não pode ser vazio.');
        }
        if (trim($this->updatedAt) === '') {
            throw new InvalidArgumentException('O updatedAt de UpdateState não pode ser vazio.');
        }
        if ($this->error !== null && trim($this->error) === '') {
            throw new InvalidArgumentException('O campo error de UpdateState não pode ser string vazia.');
        }
    }

    public static function idle(UpdateTarget $target, ?string $updatedAt = null): self
    {
        return new self(
            updateId: 'idle',
            target: $target,
            step: UpdateStep::Idle,
            updatedAt: $updatedAt ?? gmdate('c'),
        );
    }

    public function withStep(UpdateStep $step, ?string $updatedAt = null): self
    {
        return new self(
            updateId: $this->updateId,
            target: $this->target,
            step: $step,
            release: $this->release,
            etag: $this->etag,
            lastCheckAt: $this->lastCheckAt,
            context: $this->context,
            error: $step === UpdateStep::Failed ? $this->error : null,
            updatedAt: $updatedAt ?? gmdate('c'),
        );
    }

    public function withError(string $error, UpdateStep $step = UpdateStep::Failed, ?string $updatedAt = null): self
    {
        return new self(
            updateId: $this->updateId,
            target: $this->target,
            step: $step,
            release: $this->release,
            etag: $this->etag,
            lastCheckAt: $this->lastCheckAt,
            context: $this->context,
            error: $error,
            updatedAt: $updatedAt ?? gmdate('c'),
        );
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    public function withContext(array $extraContext, ?string $updatedAt = null): self
    {
        return new self(
            updateId: $this->updateId,
            target: $this->target,
            step: $this->step,
            release: $this->release,
            etag: $this->etag,
            lastCheckAt: $this->lastCheckAt,
            context: array_replace($this->context, $extraContext),
            error: $this->error,
            updatedAt: $updatedAt ?? gmdate('c'),
        );
    }

    public function withRelease(
        ?ReleaseInfo $release,
        ?string $etag = null,
        ?string $lastCheckAt = null,
        ?string $updatedAt = null,
    ): self {
        return new self(
            updateId: $this->updateId,
            target: $this->target,
            step: $this->step,
            release: $release,
            etag: $etag ?? $this->etag,
            lastCheckAt: $lastCheckAt ?? $this->lastCheckAt,
            context: $this->context,
            error: $this->error,
            updatedAt: $updatedAt ?? gmdate('c'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'updateId'    => $this->updateId,
            'target'      => $this->target->toArray(),
            'step'        => $this->step->value,
            'release'     => $this->release?->toArray(),
            'etag'        => $this->etag,
            'lastCheckAt' => $this->lastCheckAt,
            'context'     => $this->context,
            'error'       => $this->error,
            'updatedAt'   => $this->updatedAt,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, UpdateTarget $defaultTarget): self
    {
        $target = isset($data['target']) && is_array($data['target'])
            ? UpdateTarget::fromArray($data['target'])
            : $defaultTarget;

        $release = isset($data['release']) && is_array($data['release'])
            ? ReleaseInfo::fromArray($data['release'])
            : null;

        return new self(
            updateId: (string)($data['updateId'] ?? 'idle'),
            target: $target,
            step: UpdateStep::from((string)($data['step'] ?? 'idle')),
            release: $release,
            etag: isset($data['etag']) && $data['etag'] !== '' ? (string)$data['etag'] : null,
            lastCheckAt: isset($data['lastCheckAt']) && $data['lastCheckAt'] !== '' ? (string)$data['lastCheckAt'] : null,
            context: is_array($data['context'] ?? null) ? $data['context'] : [],
            error: isset($data['error']) && $data['error'] !== '' ? (string)$data['error'] : null,
            updatedAt: (string)($data['updatedAt'] ?? gmdate('c')),
        );
    }
}
