<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use Closure;
use DomainSystem\Core\Contracts\Update\ReleaseSourceInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateTarget;
use Throwable;

final class UpdateChecker implements UpdateCheckerInterface
{
    /**
     * @var Closure(): int
     */
    private Closure $clock;

    /**
     * @param (Closure(): int)|null $clock
     */
    public function __construct(
        private readonly ReleaseSourceInterface $releaseSource,
        private readonly VersionComparatorInterface $versionComparator,
        private readonly UpdateStateStoreInterface $stateStore,
        private readonly int $ttlHours = 12,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn(): int => time();
    }

    public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
    {
        $nowTs = ($this->clock)();
        $nowIso = gmdate('c', $nowTs);

        try {
            $state = $this->stateStore->load($target);

            if (!$force && $this->isCacheFresh($state->lastCheckAt, $nowTs) && $state->release !== null) {
                return $this->evaluateRelease($target, $state->release, $state->lastCheckAt ?? $nowIso);
            }

            $knownEtag = $force ? null : $state->etag;
            $latest = $this->releaseSource->latest($target, $knownEtag);

            if ($latest === null) {
                // 304 Not Modified: usa release em cache se existir, atualizando lastCheckAt
                if ($state->release === null) {
                    return new UpdateCheckResult(
                        status: UpdateStatus::Unknown,
                        target: $target,
                        release: null,
                        reasons: ['Resposta 304 recebida sem release prévia em cache.'],
                        checkedAt: $nowIso,
                    );
                }

                $updatedState = $state->withRelease(
                    release: $state->release,
                    etag: $state->etag,
                    lastCheckAt: $nowIso,
                    updatedAt: $nowIso,
                );
                $this->stateStore->save($updatedState);

                return $this->evaluateRelease($target, $state->release, $nowIso);
            }

            $updatedState = $state->withRelease(
                release: $latest,
                etag: $latest->etag ?? $state->etag,
                lastCheckAt: $nowIso,
                updatedAt: $nowIso,
            );
            $this->stateStore->save($updatedState);

            return $this->evaluateRelease($target, $latest, $nowIso);
        } catch (Throwable $e) {
            return new UpdateCheckResult(
                status: UpdateStatus::Unknown,
                target: $target,
                release: null,
                reasons: [$e->getMessage()],
                checkedAt: $nowIso,
            );
        }
    }

    private function evaluateRelease(UpdateTarget $target, ReleaseInfo $release, string $checkedAt): UpdateCheckResult
    {
        if ($this->versionComparator->isNewer($release->version, $target->installedVersion)) {
            return new UpdateCheckResult(
                status: UpdateStatus::Available,
                target: $target,
                release: $release,
                reasons: ["Nova versão {$release->version} disponível."],
                checkedAt: $checkedAt,
            );
        }

        return new UpdateCheckResult(
            status: UpdateStatus::UpToDate,
            target: $target,
            release: $release,
            reasons: [],
            checkedAt: $checkedAt,
        );
    }

    private function isCacheFresh(?string $lastCheckAt, int $nowTs): bool
    {
        if ($lastCheckAt === null || trim($lastCheckAt) === '') {
            return false;
        }

        $parsed = strtotime($lastCheckAt);
        if ($parsed === false) {
            return false;
        }

        $ttlSeconds = max(1, $this->ttlHours) * 3600;
        return ($nowTs - $parsed) < $ttlSeconds;
    }
}
