<?php
namespace DomainSystem\Plugins\auth\Services\Providers;

/**
 * A "Tomada" (Fêmea) - Define o contrato padrão que todo plugue de autenticação 
 * em duas etapas precisará respeitar, blindando o sistema central.
 */
interface TwoFactorProviderInterface
{
    /**
     * Dispara o desafio de 2FA (Ex: envia o e-mail ou simula).
     */
    public function challenge(array $user): void;

    /**
     * Valida o código preenchido pelo usuário.
     */
    public function verify(array $user, string $code): bool;
}
