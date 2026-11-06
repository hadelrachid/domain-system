<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface AccountStatusProviderInterface
{
    /**
     * Retorna o status da conta do usuário.
     *
     * @param int $userId
     * @return string 'active' | 'suspended' | 'banned' (desconhecido => 'active')
     */
    public function statusOf(int $userId): string;
}
