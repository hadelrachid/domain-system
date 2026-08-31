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
        
        $subject = "Seu código de acesso";
        $message = "Seu código de acesso é: " . $code . "\n\nEste código expira em 5 minutos.";
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
