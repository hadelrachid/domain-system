<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use Exception;

class LinkRegistry
{
    /**
     * Map of linkName => className (or Closure)
     */
    private array $providedLinks = [];

    /**
     * Map of pluginName => OsConnector (to keep track of who requires what)
     */
    private array $connectors = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Called by the bootstrapper to register a plugin's manifest
     */
    public function registerConnector(string $pluginName, OsConnector $connector): void
    {
        $this->connectors[$pluginName] = $connector;

        // Register all provided links globally
        foreach ($connector->getProvidedLinks() as $linkName => $providerClass) {
            if (isset($this->providedLinks[$linkName])) {
                throw new Exception("Conflito de Link: O link '{$linkName}' já está sendo fornecido por outro plugin.");
            }
            $this->providedLinks[$linkName] = $providerClass;
        }
    }

    /**
     * Resolves a link if it exists.
     */
    public function resolve(string $linkName)
    {
        if (isset($this->providedLinks[$linkName])) {
            $providerClass = $this->providedLinks[$linkName];
            return $this->container->make($providerClass);
        }

        // --- FALLBACK DO KERNEL (LINKS OFICIAIS DO SO) ---
        if ($linkName === 'core.session') {
            return $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
        }
        if ($linkName === 'core.db') {
            return $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        }
        if ($linkName === 'core.router') {
            return $this->container->make(\DomainSystem\Core\Contracts\RouterInterface::class);
        }
        if ($linkName === 'core.theme') {
            return $this->container->make(\DomainSystem\Core\Contracts\ThemeManagerInterface::class);
        }

        throw new Exception("Link não encontrado: Nenhum plugin ativo fornece o link '{$linkName}'.");
    }

    /**
     * Validate that all required links across all registered connectors are met
     * Can be used for auditing or pre-boot checks.
     */
    public function validateDependencies(): array
    {
        $missing = [];
        foreach ($this->connectors as $pluginName => $connector) {
            foreach ($connector->getRequiredLinks() as $linkName) {
                // If it's not a core link and not provided by a plugin, it's missing
                if (!str_starts_with($linkName, 'core.') && !isset($this->providedLinks[$linkName])) {
                    $missing[$pluginName][] = $linkName;
                }
            }
        }
        return $missing;
    }

    public function getUnmetLinks(string $pluginName): array
    {
        if (!isset($this->connectors[$pluginName])) {
            return [];
        }
        $missing = [];
        foreach ($this->connectors[$pluginName]->getRequiredLinks() as $linkName) {
            if (!str_starts_with($linkName, 'core.') && !isset($this->providedLinks[$linkName])) {
                $missing[] = $linkName;
            }
        }
        return $missing;
    }
}
