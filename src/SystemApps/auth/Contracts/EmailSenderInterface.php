<?php

namespace DomainSystem\SystemApps\auth\Contracts;

interface EmailSenderInterface
{
    public function send(string $to, string $subject, string $message): void;
}
