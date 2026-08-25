<?php
namespace DomainSystem\Plugins\auth\Services\Providers;

class AppProvider implements TwoFactorProviderInterface
{
    public function challenge(array $user): void
    {
        // Padrão: O App não gera código ativamente (quem gera é o celular do usuário).
        // Em produção, isso é vazio. O dev_simulator vai sobrescrever isso se ativado.
    }

    public function verify(array $user, string $code): bool
    {
        if (empty($user['two_factor_secret'])) return false;
        require_once dirname(__DIR__, 2) . '/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        return $ga->verifyCode($user['two_factor_secret'], $code, 2);
    }

    public function generateSecret(string $title): array
    {
        require_once dirname(__DIR__, 2) . '/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($title, $secret);
        
        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ];
    }
}
