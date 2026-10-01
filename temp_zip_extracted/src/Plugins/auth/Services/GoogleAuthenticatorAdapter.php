<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface;

class GoogleAuthenticatorAdapter implements AuthenticatorInterface
{
    private $ga;

    public function __construct()
    {
        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
        $this->ga = new \PHPGangsta_GoogleAuthenticator();
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->ga->verifyCode($secret, $code, 2);
    }

    public function createSecret(): string
    {
        return $this->ga->createSecret();
    }

    public function getQRCodeUrl(string $title, string $secret): string
    {
        return $this->ga->getQRCodeGoogleUrl($title, $secret);
    }

    public function getCode(string $secret): string
    {
        return $this->ga->getCode($secret);
    }
}
