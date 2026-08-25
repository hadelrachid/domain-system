<?php
namespace DomainSystem\Plugins\dev_simulator\Providers;

use DomainSystem\Plugins\auth\Services\Providers\EmailProvider;

class SimulatedEmailProvider extends EmailProvider
{
    protected function sendEmail(string $email, string $code): void
    {
        $tempDir = dirname(__DIR__, 4) . '/temp';
        if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
        file_put_contents($tempDir . '/auth-2fa.txt', "--- SIMULAÇÃO DE E-MAIL ---\nPara: {$email}\nAssunto: Seu código de acesso\n\nSeu código é: {$code}\nExpira em 5 minutos.");
    }
}
