<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

/**
 * Emite aviso (CheckLevel::Warn) para plugins instalados que não fazem parte do pacote
 * e declaram `os_version` de versão maior diferente da versão alvo do núcleo.
 */
final class PluginCompatibilityCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly InstalledStateProviderInterface $installedState,
    ) {
    }

    public function id(): string
    {
        return 'plugin_compatibility';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $targetMajor = explode('.', $package->manifest->version)[0] ?? '2';

        $bundledPluginSlugs = [];
        foreach ($package->manifest->files as $file) {
            if (preg_match('#^src/Plugins/([^/]+)/#', $file['path'], $m) === 1) {
                $bundledPluginSlugs[$m[1]] = true;
            }
        }

        $warnings = [];
        foreach ($this->installedState->plugins() as $slug => $plugin) {
            if (isset($bundledPluginSlugs[$slug])) {
                continue;
            }

            $pluginMajor = explode('.', $plugin->osVersion)[0] ?? '';
            if ($pluginMajor !== $targetMajor) {
                $warnings[] = new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Warn,
                    message: sprintf(
                        'O plugin "%s" (os_version %s) não faz parte do pacote e pode ser incompatível com o núcleo %s.',
                        $plugin->slug,
                        $plugin->osVersion,
                        $package->manifest->version
                    ),
                );
            }
        }

        if ($warnings !== []) {
            return $warnings;
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Ok,
                message: 'Todos os plugins instalados são compatíveis com a versão alvo do núcleo.',
            ),
        ];
    }
}
