<?php
namespace DomainSystem\Plugins\bomb_plugin;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Assim que o Kernel chamar o register() deste plugin,
        // iniciaremos um vazamento catastrófico de memória (Memory Leak).
        // Isso vai forçar o servidor a atingir o limite e morrer fulminantemente.
        
        $bomb = [];
        while (true) {
            $bomb[] = str_repeat("DRENANDO MEMÓRIA...", 10000);
        }
    }
}
