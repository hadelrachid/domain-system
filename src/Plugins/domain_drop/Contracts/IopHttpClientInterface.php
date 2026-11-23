<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato para clientes HTTP do protocolo IOP (Interface Segregation & DIP).
 */
interface IopHttpClientInterface
{
    /**
     * Executa chamada IOP para endpoint de sistema/auth.
     *
     * @param string $endpoint  ex: '/auth/token/create' ou '/auth/token/refresh'
     * @param array<string, mixed> $params    ex: ['code' => '...']
     * @return array<string, mixed> Retorno decodificado da API
     * @throws \DomainSystem\Plugins\domain_drop\Exceptions\AliExpressApiException
     * @throws \RuntimeException
     */
    public function call(string $endpoint, array $params): array;
}
