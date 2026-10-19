<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use InvalidArgumentException;

final class UpdateConfig
{
    private string $configFile;
    private JsonStoreInterface $jsonStore;

    /**
     * @var array{core_repository: string, channel: string, check_ttl_hours: int, auto_rollback: bool}|null
     */
    private ?array $cached = null;

    public function __construct(string $rootPath, ?JsonStoreInterface $jsonStore = null)
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->configFile = $normalizedRoot . '/config/update.json';
        $this->jsonStore = $jsonStore ?? new AtomicJsonStore();
    }

    public function coreRepository(): string
    {
        return $this->load()['core_repository'];
    }

    public function channel(): string
    {
        return $this->load()['channel'];
    }

    public function checkTtlHours(): int
    {
        return $this->load()['check_ttl_hours'];
    }

    public function autoRollback(): bool
    {
        return $this->load()['auto_rollback'];
    }

    /**
     * @return array{core_repository: string, channel: string, check_ttl_hours: int, auto_rollback: bool}
     */
    public function load(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $defaults = [
            'core_repository' => 'hadelrachid/domain-system',
            'channel'         => 'stable',
            'check_ttl_hours' => 12,
            'auto_rollback'   => true,
        ];

        $raw = $this->jsonStore->read($this->configFile, $defaults);

        $repo = (string)($raw['core_repository'] ?? $defaults['core_repository']);
        if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo) !== 1) {
            throw new InvalidArgumentException("Repositório inválido em config/update.json: {$repo}");
        }

        $channel = trim((string)($raw['channel'] ?? $defaults['channel']));
        if ($channel === '') {
            throw new InvalidArgumentException('O campo channel em config/update.json não pode ser vazio.');
        }

        $ttlHours = (int)($raw['check_ttl_hours'] ?? $defaults['check_ttl_hours']);
        if ($ttlHours < 1) {
            throw new InvalidArgumentException('O campo check_ttl_hours em config/update.json deve ser >= 1.');
        }

        $autoRollback = isset($raw['auto_rollback']) ? (bool)$raw['auto_rollback'] : true;

        $this->cached = [
            'core_repository' => $repo,
            'channel'         => $channel,
            'check_ttl_hours' => $ttlHours,
            'auto_rollback'   => $autoRollback,
        ];

        return $this->cached;
    }
}
