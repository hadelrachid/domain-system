<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use Throwable;

final class JsonUpdateStateStore implements UpdateStateStoreInterface
{
    private string $stateDir;
    private JsonStoreInterface $jsonStore;

    public function __construct(string $rootPath, ?JsonStoreInterface $jsonStore = null)
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->stateDir = $normalizedRoot . '/storage/state';
        $this->jsonStore = $jsonStore ?? new AtomicJsonStore();
    }

    public function load(UpdateTarget $target): UpdateState
    {
        $filePath = $this->resolveFilePath($target);
        $data = $this->jsonStore->read($filePath, []);

        if ($data === []) {
            return UpdateState::idle($target);
        }

        try {
            return UpdateState::fromArray($data, $target);
        } catch (Throwable) {
            return UpdateState::idle($target);
        }
    }

    public function save(UpdateState $state): void
    {
        $filePath = $this->resolveFilePath($state->target);
        $this->jsonStore->write($filePath, $state->toArray());
    }

    public function resolveFilePath(UpdateTarget $target): string
    {
        if ($target->kind === UpdateKind::Core) {
            return $this->stateDir . '/update-core.json';
        }

        $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $target->slug) ?: 'plugin';
        return $this->stateDir . '/update-plugin-' . $safeSlug . '.json';
    }
}
