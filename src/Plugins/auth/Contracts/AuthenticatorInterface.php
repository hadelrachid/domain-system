<?php

namespace DomainSystem\Plugins\auth\Contracts;

interface AuthenticatorInterface
{
    public function verifyCode(string $secret, string $code): bool;
    public function createSecret(): string;
    public function getQRCodeUrl(string $title, string $secret): string;
    public function getCode(string $secret): string;
}
