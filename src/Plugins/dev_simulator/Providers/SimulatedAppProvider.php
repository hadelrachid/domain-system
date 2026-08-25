<?php
namespace DomainSystem\Plugins\dev_simulator\Providers;

use DomainSystem\Plugins\auth\Services\Providers\AppProvider;

class SimulatedAppProvider extends AppProvider
{
    public function challenge(array $user): void
    {
        if (!empty($user['two_factor_secret'])) {
            require_once dirname(__DIR__, 3) . '/auth/GoogleAuthenticator.php';
            $ga = new \PHPGangsta_GoogleAuthenticator();
            $currentCode = $ga->getCode($user['two_factor_secret']);
            
            $tempDir = dirname(__DIR__, 4) . '/temp';
            if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
            file_put_contents($tempDir . '/auth-2fa.txt', "Usuário: {$user['email']}\nCódigo App Válido Agora: {$currentCode}\n(Este código expira em 30 segundos)");
        }
    }
}
