<?php

declare(strict_types=1);

namespace DomainSystem\Core\Exceptions;

use DomainSystem\Core\Http\Response;
use RuntimeException;

/**
 * Exceção de curto-circuito HTTP (ex: redirecionamentos em router.before_dispatch).
 * Permite encerrar o fluxo de roteamento retornando um objeto Response limpo e testável,
 * sem precisar invocar header() ou exit().
 */
class HttpResponseException extends RuntimeException
{
    private Response $response;

    public function __construct(Response $response, string $message = '', int $code = 0)
    {
        $statusCode = $code !== 0 ? $code : $response->getStatusCode();
        parent::__construct($message, $statusCode);
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
