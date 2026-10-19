<?php

namespace DomainSystem\Core\Http;

/**
 * Alias de retrocompatibilidade para o SessionManager consolidado em DomainSystem\Core\Security\SessionManager.
 * Mantém 100% de compatibilidade com typehints e testes existentes.
 */
class SessionManager extends \DomainSystem\Core\Security\SessionManager
{
}
