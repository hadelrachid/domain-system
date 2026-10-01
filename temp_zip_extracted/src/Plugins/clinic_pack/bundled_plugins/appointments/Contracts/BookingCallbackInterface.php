<?php

namespace DomainSystem\Plugins\appointments\Contracts;

use DomainSystem\Core\Http\Response;

interface BookingCallbackInterface
{
    // Códigos
    public const ERR_MISSING_FIELDS = 1001;
    public const ERR_INVALID_EMAIL = 1002;
    public const ERR_SLOT_OCCUPIED = 1003;
    public const SUCCESS_BOOKED = 2000;

    /**
     * Retorna uma Response JSON padronizada dependendo do código.
     */
    public function respond(int|string $code, array $extraData = []): Response;
}
