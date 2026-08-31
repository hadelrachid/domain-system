<?php

namespace DomainSystem\Plugins\auth\Contracts;

interface TwoFactorCodeStoreInterface
{
    /**
     * Armazena o código de 2FA gerado e o tempo de expiração para o usuário.
     */
    public function storeCode(int $userId, string $code, string $expiry): void;
}
