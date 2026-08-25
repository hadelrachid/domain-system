<?php

namespace DomainSystem\Plugins\clinic_pack\bundled_plugins\dental_module;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Apenas provando que ele é carregado
        $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class)->addListener('init', function() {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            if (session_status() !== PHP_SESSION_NONE) {
                $_SESSION['dental_module_loaded'] = true;
            }
        });
    }
}
