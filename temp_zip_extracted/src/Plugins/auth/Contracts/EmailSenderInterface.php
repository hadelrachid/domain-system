<?php

namespace DomainSystem\Plugins\auth\Contracts;

interface EmailSenderInterface
{
    public function send(string $to, string $subject, string $message): void;
}
