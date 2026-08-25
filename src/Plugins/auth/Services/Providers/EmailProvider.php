<?php
namespace DomainSystem\Plugins\auth\Services\Providers;

use DomainSystem\Plugins\Database\QueryBuilder;

class EmailProvider implements TwoFactorProviderInterface
{
    protected QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function challenge(array $user): void
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $this->db->table('users')->where('id', '=', $user['id'])->update([
            'email_2fa_code' => $code,
            'email_2fa_expiry' => $expiry
        ]);
        
        $this->sendEmail($user['email'], $code);
    }

    protected function sendEmail(string $email, string $code): void
    {
        $subject = "Seu código de acesso";
        $message = "Seu código de acesso é: " . $code . "\n\nEste código expira em 5 minutos.";
        $headers = "From: no-reply@daherclinica.com.br";
        @mail($email, $subject, $message, $headers);
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
