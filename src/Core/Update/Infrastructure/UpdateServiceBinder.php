<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\HttpClientInterface;
use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\MaintenanceModeInterface;
use DomainSystem\Core\Contracts\Update\MigrationRegistryInterface;
use DomainSystem\Core\Contracts\Update\PreflightInterface;
use DomainSystem\Core\Contracts\Update\ProtectedPathsInterface;
use DomainSystem\Core\Contracts\Update\ReleaseSourceInterface;
use DomainSystem\Core\Contracts\Update\SchemaMigratorInterface;
use DomainSystem\Core\Contracts\Update\SchemaPlannerInterface;
use DomainSystem\Core\Contracts\Update\SchemaProbeInterface;
use DomainSystem\Core\Contracts\Update\SchemaVerifierInterface;
use DomainSystem\Core\Contracts\Update\UpdateApplierInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateLockInterface;
use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Contracts\Update\UpdatePackageVerifierInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use DomainSystem\Core\Database\Update\PdoMigrationRegistry;
use DomainSystem\Core\Database\Update\PdoSchemaProbe;
use DomainSystem\Core\Database\Update\RunnerSchemaMigrator;
use DomainSystem\Core\Update\Infrastructure\Preflight\DiskSpaceCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\DowngradeCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\ExtensionsCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\PhpVersionCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\PluginCompatibilityCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\ProtocolCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\SchemaPlanCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\WritablePathsCheck;
use DomainSystem\Core\Update\Service\CoreUpdater;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Core\Update\Service\Preflight;
use DomainSystem\Core\Update\Service\SchemaPlanner;
use DomainSystem\Core\Update\Service\SchemaVerifier;
use DomainSystem\Core\Update\Service\SemverComparator;
use DomainSystem\Core\Update\Service\UpdateChecker;
use DomainSystem\Core\Update\Service\UpdateNotifier;
use DomainSystem\Core\Update\Service\UpdateTargetResolver;
use Throwable;

final class UpdateServiceBinder
{
    public static function bind(OsRuntimeInterface|ContainerInterface $runtime, string $rootPath): void
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

        $runtime->singleton(ProtectedPathsInterface::class, static function (): ProtectedPathsInterface {
            return new CoreProtectedPaths();
        });

        $runtime->singleton(UpdateManifestParserInterface::class, static function (): UpdateManifestParserInterface {
            return new JsonUpdateManifestParser();
        });

        $runtime->singleton(UpdatePackageVerifierInterface::class, static function ($c) use ($normalizedRoot): UpdatePackageVerifierInterface {
            return new Sha256PackageVerifier(
                manifestParser: $c->make(UpdateManifestParserInterface::class),
                protectedPaths: $c->make(ProtectedPathsInterface::class),
                extractor: new ZipPackageExtractor(),
                quarantineBaseDir: $normalizedRoot . '/temp/updates',
            );
        });

        $runtime->singleton(MigrationRegistryInterface::class, static function ($c): MigrationRegistryInterface {
            return new PdoMigrationRegistry($c->make(DatabaseConnectionInterface::class));
        });

        $runtime->singleton(SchemaProbeInterface::class, static function ($c): SchemaProbeInterface {
            return new PdoSchemaProbe($c->make(DatabaseConnectionInterface::class));
        });

        $runtime->singleton(SchemaPlannerInterface::class, static function ($c): SchemaPlannerInterface {
            return new SchemaPlanner($c->make(MigrationRegistryInterface::class));
        });

        $runtime->singleton(SchemaMigratorInterface::class, static function ($c) use ($normalizedRoot): SchemaMigratorInterface {
            return new RunnerSchemaMigrator(
                $c->make(DatabaseConnectionInterface::class),
                $normalizedRoot,
            );
        });

        $runtime->singleton(SchemaVerifierInterface::class, static function ($c): SchemaVerifierInterface {
            return new SchemaVerifier(
                $c->make(SchemaProbeInterface::class),
                $c->make(MigrationRegistryInterface::class),
            );
        });

        $runtime->singleton(PreflightInterface::class, static function ($c) use ($normalizedRoot): PreflightInterface {
            return new Preflight([
                new PhpVersionCheck(),
                new ExtensionsCheck(),
                new DiskSpaceCheck($normalizedRoot),
                new WritablePathsCheck($normalizedRoot, $c->make(ProtectedPathsInterface::class)),
                new ProtocolCheck($c->make(UpdateManifestParserInterface::class)),
                new DowngradeCheck($c->make(VersionComparatorInterface::class)),
                new PluginCompatibilityCheck($c->make(InstalledStateProviderInterface::class)),
                new SchemaPlanCheck($c->make(SchemaPlannerInterface::class)),
            ]);
        });

        $runtime->singleton(BackupInterface::class, static function ($c) use ($normalizedRoot): BackupInterface {
            $jsonStore = self::resolveOptional($c, JsonStoreInterface::class);
            return new ZipDirectoryBackup($normalizedRoot, null, $jsonStore);
        });

        $runtime->singleton(MaintenanceModeInterface::class, static function () use ($normalizedRoot): MaintenanceModeInterface {
            return new FileMaintenanceMode($normalizedRoot);
        });

        $runtime->singleton(UpdateApplierInterface::class, static function ($c) use ($normalizedRoot): UpdateApplierInterface {
            return new StagedUpdateApplier(
                $normalizedRoot,
                $c->make(ProtectedPathsInterface::class),
                $c->make(BackupInterface::class),
            );
        });

        $runtime->singleton(HealthCheckInterface::class, static function ($c) use ($normalizedRoot): HealthCheckInterface {
            $jsonStore = self::resolveOptional($c, JsonStoreInterface::class);
            return new CoreHealthCheck(
                rootPath: $normalizedRoot,
                container: $c,
                installedState: $c->make(InstalledStateProviderInterface::class),
                stateStore: $c->make(UpdateStateStoreInterface::class),
                schemaVerifier: $c->make(SchemaVerifierInterface::class),
                manifestParser: $c->make(UpdateManifestParserInterface::class),
                jsonStore: $jsonStore,
            );
        });

        $runtime->singleton(CoreUpdaterInterface::class, static function ($c) use ($normalizedRoot): CoreUpdaterInterface {
            /** @var UpdateConfig $config */
            $config = $c->make(UpdateConfig::class);

            return new CoreUpdater(
                targetResolver: $c->make(UpdateTargetResolverInterface::class),
                checker: $c->make(UpdateCheckerInterface::class),
                releaseSource: $c->make(ReleaseSourceInterface::class),
                manifestParser: $c->make(UpdateManifestParserInterface::class),
                packageVerifier: $c->make(UpdatePackageVerifierInterface::class),
                preflight: $c->make(PreflightInterface::class),
                schemaPlanner: $c->make(SchemaPlannerInterface::class),
                backup: $c->make(BackupInterface::class),
                databaseBackup: $c->make(DatabaseBackupInterface::class),
                maintenanceMode: $c->make(MaintenanceModeInterface::class),
                applier: $c->make(UpdateApplierInterface::class),
                schemaMigrator: $c->make(SchemaMigratorInterface::class),
                schemaVerifier: $c->make(SchemaVerifierInterface::class),
                healthCheck: $c->make(HealthCheckInterface::class),
                stateStore: $c->make(UpdateStateStoreInterface::class),
                lock: $c->make(UpdateLockInterface::class),
                tempUpdatesDir: $normalizedRoot . '/temp/updates',
                autoRollback: $config->autoRollback(),
                events: self::resolveOptional($c, EventDispatcherInterface::class),
                notifications: self::resolveOptional($c, NotificationManagerInterface::class),
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
