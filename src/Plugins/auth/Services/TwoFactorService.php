<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\Database\QueryBuilder;

class TwoFactorService
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    /**
     * Valida um código 2FA via APP (Google Authenticator)
     */
    public function verifyAppCode(string $secret, string $code): bool
    {
        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        return $ga->verifyCode($secret, $code, 2);
    }

    /**
     * Gera um código de 6 dígitos e salva no banco de dados para o usuário,
     * simulando também o envio do e-mail.
     */
    public function generateAndSendEmailCode(array $user): void
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $this->db->table('users')->where('id', '=', $user['id'])->update([
            'email_2fa_code' => $code,
            'email_2fa_expiry' => $expiry
        ]);
        
        // Simula o envio de e-mail escrevendo no arquivo temporário
        $tempDir = dirname(__DIR__, 4) . '/temp';
        if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
        file_put_contents($tempDir . '/auth-2fa.txt', "--- SIMULAÇÃO DE E-MAIL ---\nPara: {$user['email']}\nAssunto: Seu código de acesso\n\nSeu código é: {$code}\nExpira em 5 minutos.");
    }

    /**
     * Valida o código enviado por e-mail, verificando também a expiração
     */
    public function verifyEmailCode(array $user, string $code): bool
    {
        if (empty($user['email_2fa_code']) || empty($user['email_2fa_expiry'])) {
            return false;
        }

        if ($code !== $user['email_2fa_code']) {
            return false;
        }

        if (date('Y-m-d H:i:s') > $user['email_2fa_expiry']) {
            return false;
        }

        return true;
    }

    /**
     * Apenas para ambiente de desenvolvimento: gera um código válido do App 
     * e escreve no log txt para testes locais rápidos.
     */
    public function simulateAppCodeGeneration(array $user): void
    {
        if (empty($user['two_factor_secret'])) return;

        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $currentCode = $ga->getCode($user['two_factor_secret']);
        
        $tempDir = dirname(__DIR__, 4) . '/temp';
        if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
        file_put_contents($tempDir . '/auth-2fa.txt', "Usuário: {$user['email']}\nCódigo Válido Agora: {$currentCode}\n(Este código expira em 30 segundos)");
    }

    /**
     * Gera um novo segredo e URL do QR Code para o Google Authenticator
     */
    public function generateAppSecret(string $title): array
    {
        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($title, $secret);
        
        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ];
    }
}
