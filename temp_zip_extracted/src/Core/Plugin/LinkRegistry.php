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
        if (!isset($this->providedLinks[$linkName])) {
            throw new Exception("Link não encontrado: Nenhum plugin ativo fornece o link '{$linkName}'.");
        }

        $providerClass = $this->providedLinks[$linkName];
        
        // Use the Container to instantiate the provider
        return $this->container->make($providerClass);
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
                if (!isset($this->providedLinks[$linkName])) {
                    $missing[$pluginName][] = $linkName;
                }
            }
        }
        return $missing;
    }
}
