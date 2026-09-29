<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\PluginLifecycleInterface;
use DomainSystem\Core\Contracts\PluginMetadataInterface;
use DomainSystem\Core\Contracts\PluginStateInterface;

interface PluginInterface extends PluginLifecycleInterface, PluginMetadataInterface, PluginStateInterface
{
    // Métodos segregados nas interfaces acima (ISP).
    // Mantido aqui para retrocompatibilidade de Type Hinting no sistema legado.
}
