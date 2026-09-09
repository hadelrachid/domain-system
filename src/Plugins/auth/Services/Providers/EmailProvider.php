<?php
namespace DomainSystem\Plugins\auth\Services\Providers;

use DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface;
use DomainSystem\Plugins\auth\Contracts\EmailSenderInterface;

class EmailProvider implements TwoFactorProviderInterface
{
    private TwoFactorCodeStoreInterface $store;
    private EmailSenderInterface $sender;

    public function __construct(TwoFactorCodeStoreInterface $store, EmailSenderInterface $sender)
    {
        $this->store = $store;
        $this->sender = $sender;
    }

    public function challenge(array $user): void
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $this->store->storeCode($user['id'], $code, $expiry);
        
        $subject = "Seu código de acesso do CockPIT";
        
        $message = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f9; padding: 40px 20px; text-align: center;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #2271b1;'>
                
                <h2 style='color: #1d2327; margin-top: 0; font-size: 22px; font-weight: 600;'>Autenticação em 2 Fatores</h2>
                
                <p style='color: #50575e; font-size: 15px; line-height: 1.6; margin-bottom: 30px;'>
                    Olá, <strong style='color:#1d2327'>" . htmlspecialchars($user['name']) . "</strong>.<br>
                    Foi solicitado um login na sua conta. Use o código abaixo para prosseguir:
                </p>
                
                <div style='background-color: #f0f6fc; border: 1px dashed #b6d4fe; padding: 20px; border-radius: 6px; margin: 30px 0;'>
                    <span style='font-size: 38px; font-weight: bold; color: #0a58ca; letter-spacing: 8px; font-family: monospace; display: block;'>" . $code . "</span>
                </div>
                
                <p style='color: #8c8f94; font-size: 13px; margin-top: 30px;'>
                    Este código expira em <strong>5 minutos</strong>.<br>
                    Se você não solicitou este acesso, ignore este e-mail.
                </p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #8c8f94; font-size: 12px;'>
                    Sistema de Gestão - Daher Clínica
                </div>
            </div>
        </div>";

        $this->sender->send($user['email'], $subject, $message);
    }

    public function verify(array $user, string $code): bool
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
}
