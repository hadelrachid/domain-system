<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\commerce\Services\StripeSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class StripeSignatureVerifierTest extends TestCase
{
    private function sign(string $body, string $secret, int $ts): string
    {
        return 't=' . $ts . ',v1=' . hash_hmac('sha256', $ts . '.' . $body, $secret);
    }

    public function testValidSignatureIsAccepted(): void
    {
        $secret = 'whsec_test_secret_123';
        $now = 1_700_000_000;
        $body = '{"id":"evt_123","type":"checkout.session.completed"}';
        $sigHeader = $this->sign($body, $secret, $now);

        $verifier = new StripeSignatureVerifier($secret, 300, $now);
        $this->assertTrue($verifier->verify($body, ['stripe-signature' => $sigHeader]));
    }

    public function testTamperedBodyIsRejected(): void
    {
        $secret = 'whsec_test_secret_123';
        $now = 1_700_000_000;
        $originalBody = '{"amount":100}';
        $tamperedBody = '{"amount":10}';
        $sigHeader = $this->sign($originalBody, $secret, $now);

        $verifier = new StripeSignatureVerifier($secret, 300, $now);
        $this->assertFalse($verifier->verify($tamperedBody, ['stripe-signature' => $sigHeader]));
    }

    public function testOldTimestampIsRejected(): void
    {
        $secret = 'whsec_test_secret_123';
        $now = 1_700_000_000;
        $oldTs = $now - 301; // Fora da tolerância de 300s
        $body = '{"event":"ok"}';
        $sigHeader = $this->sign($body, $secret, $oldTs);

        $verifier = new StripeSignatureVerifier($secret, 300, $now);
        $this->assertFalse($verifier->verify($body, ['stripe-signature' => $sigHeader]));
    }

    public function testEmptySecretIsRejected(): void
    {
        $verifier = new StripeSignatureVerifier('', 300, 1_000_000);
        $this->assertFalse($verifier->verify('{"a":1}', ['stripe-signature' => 't=1000000,v1=abc']));
    }

    public function testMissingHeaderIsRejected(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_xyz', 300, 1_000_000);
        $this->assertFalse($verifier->verify('{"a":1}', []));
        $this->assertFalse($verifier->verify('{"a":1}', ['stripe-signature' => '']));
    }
}
