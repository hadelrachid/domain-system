<?php
namespace DomainSystem\Plugins\dev_simulator\Providers;

use DomainSystem\Plugins\auth\Services\Providers\TwoFactorProviderInterface;
use DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface;

class SimulatedAppProvider implements TwoFactorProviderInterface
{
    private AuthenticatorInterface $authenticator;

    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function challenge(array $user): void
    {
        if (!empty($user['two_factor_secret'])) {
            $currentCode = $this->authenticator->getCode($user['two_factor_secret']);
            
            $tempDir = dirname(__DIR__, 4) . '/temp';
            if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
            file_put_contents($tempDir . '/auth-2fa.txt', "Usuário: {$user['email']}\nCódigo App Válido Agora: {$currentCode}\n(Este código expira em 30 segundos)");
        }
    }

    public function verify(array $user, string $code): bool
    {
        if (empty($user['two_factor_secret'])) return false;
        return $this->authenticator->verifyCode($user['two_factor_secret'], $code);
    }

    public function generateSecret(string $title): array
    {
        $secret = $this->authenticator->createSecret();
        $qrCodeUrl = $this->authenticator->getQRCodeUrl($title, $secret);
        
        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ];
    }
}
