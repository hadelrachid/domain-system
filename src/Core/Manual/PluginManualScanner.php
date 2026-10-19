<?php

declare(strict_types=1);

namespace DomainSystem\Core\Manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Varredor (Scanner) desacoplado que rastreia pastas `manual/` dentro dos
 * Plugins e SystemApps e carrega automaticamente cada módulo que implemente
 * ManualModuleInterface (ou defina um array declarativo em manual/module.php).
 */
class PluginManualScanner
{
    /** @var array<int, string> */
    private array $scanRoots;

    /**
     * @param array<int, string>|string|null $scanRoots Diretórios base de plugins/apps a escanear ou diretório raiz do projeto
     */
    public function __construct(array|string|null $scanRoots = null)
    {
        if (is_string($scanRoots)) {
            $baseRoot = rtrim($scanRoots, '/\\');
            $this->scanRoots = [
                $baseRoot . '/src/SystemApps',
                $baseRoot . '/src/Plugins',
            ];
            return;
        }

        $baseRoot = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        $this->scanRoots = $scanRoots ?? [
            $baseRoot . '/src/SystemApps',
            $baseRoot . '/src/Plugins',
        ];
    }

    /**
     * Descobre todos os manuais de plugins presentes nas pastas `manual/`.
     *
     * @return array<string, ManualModuleInterface> Indexado por getId()
     */
    public function discover(): array
    {
        $modules = [];

        foreach ($this->scanRoots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $pluginDirs = glob(rtrim($root, '/\\') . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($pluginDirs as $pluginDir) {
                $manualDir = $pluginDir . DIRECTORY_SEPARATOR . 'manual';
                if (!is_dir($manualDir)) {
                    continue;
                }

                $moduleFile = $manualDir . DIRECTORY_SEPARATOR . 'module.php';
                if (!is_file($moduleFile)) {
                    continue;
                }

                $loaded = $this->loadModuleFile($moduleFile, $manualDir);
                if ($loaded instanceof ManualModuleInterface) {
                    $modules[$loaded->getId()] = $loaded;
                }
            }
        }

        return $modules;
    }

    private function loadModuleFile(string $moduleFile, string $manualDir): ?ManualModuleInterface
    {
        $realModule = realpath($moduleFile);
        $realManualDir = realpath($manualDir);
        if ($realModule === false || $realManualDir === false || !str_starts_with($realModule, $realManualDir)) {
            return null;
        }

        $result = require $realModule;
        if ($result instanceof ManualModuleInterface) {
            return $result;
        }

        if (is_array($result)) {
            return new ArrayManualModuleAdapter($result, $realManualDir);
        }

        return null;
    }
}
