<?php
namespace DomainSystem\Plugins\dev_simulator\Providers;

use DomainSystem\Plugins\auth\Contracts\EmailSenderInterface;

class SimulatedEmailSender implements EmailSenderInterface
{
    public function send(string $to, string $subject, string $message): void
    {
        $tempDir = dirname(__DIR__, 4) . '/temp';
        if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
        file_put_contents($tempDir . '/auth-2fa.txt', "--- SIMULAÇÃO DE E-MAIL ---\nPara: {$to}\nAssunto: {$subject}\n\n{$message}");
    }
}
