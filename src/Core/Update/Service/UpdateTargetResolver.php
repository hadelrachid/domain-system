<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Value\UpdateTarget;

final class UpdateTargetResolver implements UpdateTargetResolverInterface
{
    public function __construct(
        private readonly InstalledStateProviderInterface $installedState,
        private readonly string $coreRepository = 'hadelrachid/domain-system',
        private readonly string $channel = 'stable',
    ) {
    }

    public function core(): UpdateTarget
    {
        return new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: $this->coreRepository,
            installedVersion: $this->installedState->coreVersion(),
            channel: $this->channel,
        );
    }
}
