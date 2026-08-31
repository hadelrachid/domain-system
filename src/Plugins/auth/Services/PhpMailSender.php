<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\auth\Contracts\EmailSenderInterface;

class PhpMailSender implements EmailSenderInterface
{
    public function send(string $to, string $subject, string $message): void
    {
        $headers = "From: no-reply@daherclinica.com.br";
        @mail($to, $subject, $message, $headers);
    }
}
