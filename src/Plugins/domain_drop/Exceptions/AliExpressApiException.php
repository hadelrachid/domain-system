<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Exceptions;

use RuntimeException;

/**
 * Exceção tipada lançada em falhas de comunicação ou respostas com erro da AliExpress API.
 */
class AliExpressApiException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly string $type = 'API_ERROR'
    ) {
        parent::__construct($message, $code);
    }
}
