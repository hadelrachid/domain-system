<?php
namespace DomainSystem\Plugins\crash_test;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void {}

    public function boot(): void
    {
        // 💣 BOOOM! O plugin dispara um erro agressivo assim que acorda!
        throw new \Exception("Simulação de Ameaça Crítica: Memory Leak Detectado! Abortando processo do plugin.");
    }

    public function activate(): void {}
}
