<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use DomainSystem\Core\Update\Value\InstalledPlugin;

final class FilesystemInstalledState implements InstalledStateProviderInterface
{
    private string $rootPath;
    private ?string $coreVersionOverride;
    private JsonStoreInterface $jsonStore;

    public function __construct(
        string $rootPath,
        ?string $coreVersionOverride = null,
        ?JsonStoreInterface $jsonStore = null,
    ) {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->coreVersionOverride = $coreVersionOverride;
        $this->jsonStore = $jsonStore ?? new AtomicJsonStore();
    }

    public function coreVersion(): string
    {
        if ($this->coreVersionOverride !== null && trim($this->coreVersionOverride) !== '') {
            return $this->coreVersionOverride;
        }

        $bootstrapFile = $this->rootPath . '/bootstrap.php';
        if (is_file($bootstrapFile)) {
            $source = (string)file_get_contents($bootstrapFile);
            if (preg_match("/define\\(\\s*['\"]DS_VERSION['\"]\\s*,\\s*['\"]([^'\"]+)['\"]\\s*\\)/", $source, $m) === 1) {
                return $m[1];
            }
        }

        if (defined('DS_VERSION') && is_string(DS_VERSION) && DS_VERSION !== '') {
            return DS_VERSION;
        }

        return '2.3.0';
    }

    public function plugins(): array
    {
        $pluginsDir = $this->rootPath . '/src/Plugins';
        if (!is_dir($pluginsDir)) {
            return [];
        }

        $activeMap = $this->jsonStore->read($this->rootPath . '/config/plugins.json', []);
        $entries = scandir($pluginsDir) ?: [];
        sort($entries);

        $installed = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestPath = $pluginsDir . '/' . $entry . '/plugin.json';
            if (!is_file($manifestPath)) {
                continue;
            }

            $data = $this->jsonStore->read($manifestPath, []);
            if ($data === []) {
                continue;
            }

            $slug = trim((string)($data['name'] ?? $entry));
            $version = trim((string)($data['version'] ?? '1.0.0'));
            $osVersion = trim((string)($data['os_version'] ?? '2.3'));
            $isCore = (bool)($data['core'] ?? false);
            $isActive = !empty($activeMap[$entry]) || !empty($activeMap[$slug]);

            if ($slug === '' || $version === '' || $osVersion === '') {
                continue;
            }

            $installed[$entry] = new InstalledPlugin(
                slug: $entry,
                version: $version,
                osVersion: $osVersion,
                isCore: $isCore,
                isActive: $isActive,
            );
        }

        return $installed;
    }
}
