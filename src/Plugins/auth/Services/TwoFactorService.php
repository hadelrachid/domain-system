<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\Database\QueryBuilder;

class TwoFactorService
{
    private QueryBuilder ;

    public function __construct(QueryBuilder )
    {
        ->db = ;
    }

    /**
     * Valida um código 2FA via APP (Google Authenticator)
     */
    public function verifyAppCode(string , string ): bool
    {
        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
         = new \PHPGangsta_GoogleAuthenticator();
        return ->verifyCode(, , 2);
    }

    /**
     * Gera um código de 6 dígitos e salva no banco de dados para o usuário,
     * simulando também o envio do e-mail.
     */
    public function generateAndSendEmailCode(array ): void
    {
         = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
         = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        ->db->table('users')->where('id', '=', ['id'])->update([
            'email_2fa_code' => ,
            'email_2fa_expiry' => 
        ]);
        
        // Simula o envio de e-mail escrevendo no arquivo temporário
         = dirname(__DIR__, 4) . '/temp';
        if (!is_dir()) { mkdir(, 0777, true); }
        file_put_contents( . '/auth-2fa.txt', "--- SIMULAÇÃO DE E-MAIL ---\nPara: {['email']}\nAssunto: Seu código de acesso\n\nSeu código é: {}\nExpira em 5 minutos.");
    }

    /**
     * Valida o código enviado por e-mail, verificando também a expiração
     */
    public function verifyEmailCode(array , string ): bool
    {
        if (empty(['email_2fa_code']) || empty(['email_2fa_expiry'])) {
            return false;
        }

        if ( !== ['email_2fa_code']) {
            return false;
        }

        if (date('Y-m-d H:i:s') > ['email_2fa_expiry']) {
            return false;
        }

        return true;
    }

    /**
     * Apenas para ambiente de desenvolvimento: gera um código válido do App 
     * e escreve no log txt para testes locais rápidos.
     */
    public function simulateAppCodeGeneration(array ): void
    {
        if (empty(['two_factor_secret'])) return;

        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
         = new \PHPGangsta_GoogleAuthenticator();
         = ->getCode(['two_factor_secret']);
        
         = dirname(__DIR__, 4) . '/temp';
        if (!is_dir()) { mkdir(, 0777, true); }
        file_put_contents( . '/auth-2fa.txt', "Usuário: {['email']}\nCódigo Válido Agora: {}\n(Este código expira em 30 segundos)");
    }

    /**
     * Gera um novo segredo e URL do QR Code para o Google Authenticator
     */
    public function generateAppSecret(string ): array
    {
        require_once dirname(__DIR__) . '/GoogleAuthenticator.php';
         = new \PHPGangsta_GoogleAuthenticator();
         = ->createSecret();
         = ->getQRCodeGoogleUrl(, );
        
        return [
            'secret' => ,
            'qrCodeUrl' => 
        ];
    }
}
