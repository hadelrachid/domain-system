<?php
namespace DomainSystem\Plugins\auth\Services\Providers;

use DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface;

class AppProvider implements TwoFactorProviderInterface
{
    private AuthenticatorInterface $authenticator;

    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function challenge(array $user): void
    {
        // Padrão: O App não gera código ativamente (quem gera é o celular do usuário).
        // Em produção, isso é vazio. O dev_simulator vai sobrescrever isso se ativado.
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
