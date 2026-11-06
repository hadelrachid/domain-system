<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services;

use DomainSystem\Plugins\commerce\Contracts\WebhookSignatureVerifierInterface;

final class StripeSignatureVerifier implements WebhookSignatureVerifierInterface
{
    public function __construct(
        private string $secret,
        private int $toleranceSeconds = 300,
        private ?int $now = null
    ) {}

    public function verify(string $rawBody, array $headers): bool
    {
        $header = (string)($headers['stripe-signature'] ?? '');
        if ($this->secret === '' || $header === '' || $rawBody === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $timestamp = (int)$v;
            }
            if ($k === 'v1') {
                $signatures[] = $v;
            }
        }

        if (!$timestamp || $signatures === []) {
            return false;
        }

        if (abs(($this->now ?? time()) - $timestamp) > $this->toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
