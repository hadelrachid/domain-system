<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\Update\HttpClientInterface;
use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\ReleaseSourceInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateLockInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use DomainSystem\Core\Update\Service\SemverComparator;
use DomainSystem\Core\Update\Service\UpdateChecker;
use DomainSystem\Core\Update\Service\UpdateNotifier;
use DomainSystem\Core\Update\Service\UpdateTargetResolver;
use Throwable;

final class UpdateServiceBinder
{
    public static function bind(OsRuntimeInterface $runtime, string $rootPath): void
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $rootPath), '/');

        $runtime->singleton(UpdateConfig::class, static function ($c) use ($normalizedRoot): UpdateConfig {
            $jsonStore = self::resolveOptional($c, JsonStoreInterface::class);
            return new UpdateConfig($normalizedRoot, $jsonStore);
        });

        $runtime->singleton(HttpClientInterface::class, static function (): HttpClientInterface {
            return new CurlHttpClient(new HostAllowlist());
        });

        $runtime->singleton(VersionComparatorInterface::class, static function (): VersionComparatorInterface {
            return new SemverComparator();
        });

        $runtime->singleton(UpdateStateStoreInterface::class, static function ($c) use ($normalizedRoot): UpdateStateStoreInterface {
            $jsonStore = self::resolveOptional($c, JsonStoreInterface::class);
            return new JsonUpdateStateStore($normalizedRoot, $jsonStore);
        });

        $runtime->singleton(UpdateLockInterface::class, static function () use ($normalizedRoot): UpdateLockInterface {
            return new FileUpdateLock($normalizedRoot);
        });

        $runtime->singleton(InstalledStateProviderInterface::class, static function ($c) use ($normalizedRoot): InstalledStateProviderInterface {
            $jsonStore = self::resolveOptional($c, JsonStoreInterface::class);
            return new FilesystemInstalledState($normalizedRoot, null, $jsonStore);
        });

        $runtime->singleton(UpdateTargetResolverInterface::class, static function ($c): UpdateTargetResolverInterface {
            /** @var UpdateConfig $config */
            $config = $c->make(UpdateConfig::class);
            /** @var InstalledStateProviderInterface $installed */
            $installed = $c->make(InstalledStateProviderInterface::class);

            return new UpdateTargetResolver(
                installedState: $installed,
                coreRepository: $config->coreRepository(),
                channel: $config->channel(),
            );
        });

        $runtime->singleton(ReleaseSourceInterface::class, static function ($c): ReleaseSourceInterface {
            return new GitHubReleaseSource(
                httpClient: $c->make(HttpClientInterface::class),
                versionComparator: $c->make(VersionComparatorInterface::class),
            );
        });

        $runtime->singleton(UpdateCheckerInterface::class, static function ($c): UpdateCheckerInterface {
            /** @var UpdateConfig $config */
            $config = $c->make(UpdateConfig::class);
            $notifications = self::resolveOptional($c, NotificationManagerInterface::class);

            return new UpdateChecker(
                releaseSource: $c->make(ReleaseSourceInterface::class),
                versionComparator: $c->make(VersionComparatorInterface::class),
                stateStore: $c->make(UpdateStateStoreInterface::class),
                ttlHours: $config->checkTtlHours(),
                clock: null,
                notifications: $notifications,
            );
        });

        $runtime->singleton(UpdateNotifier::class, static function ($c): UpdateNotifier {
            $notifications = self::resolveOptional($c, NotificationManagerInterface::class);
            return new UpdateNotifier(
                stateStore: $c->make(UpdateStateStoreInterface::class),
                notifications: $notifications,
            );
        });
    }

    private static function resolveOptional(mixed $container, string $abstract): mixed
    {
        if (!is_object($container) || !method_exists($container, 'make')) {
            return null;
        }

        try {
            if (method_exists($container, 'has') && !$container->has($abstract)) {
                return null;
            }
            return $container->make($abstract);
        } catch (Throwable) {
            return null;
        }
    }
}
