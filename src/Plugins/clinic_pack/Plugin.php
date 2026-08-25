<?php

namespace DomainSystem\Plugins\clinic_pack;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Hub Plugin não tem rotas próprias, ele só injeta os sub-plugins.
    }

    public function getSubPluginsPath(): ?string
    {
        return __DIR__ . '/bundled_plugins';
    }
}
