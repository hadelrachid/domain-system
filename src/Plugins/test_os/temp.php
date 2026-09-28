<?php

namespace DomainSystem\Plugins\test_os;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // O AbstractPlugin exige um register() sem parâmetros, 
    // mas a gente pode sobrescrever magicamente ou usar o PHP override?
    // Wait, PHP interface collision: AbstractPlugin demands `register(): void`.
    // OsExtensionInterface demands `register(OsConnector $os): void`.
    // PHP doesn't allow changing signatures.
    // I need to use a different class or rename the method in OsExtensionInterface.
    
}
