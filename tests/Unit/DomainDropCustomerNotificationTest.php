<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService;
use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DomainDropCustomerNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
    }

    public function testNotifyOrderPaidSendsHtmlEmailWithDetails(): void
    {
        $sent = [];
        $mailer = new class ($sent) implements EmailSenderInterface {
            public function __construct(public array &$sent) {}
            public function send(string $to, string $subject, string $message): void
            {
                $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $message];
            }
        };

        $service = new DropshipCustomerNotificationService(
            mailer: $mailer,
            storeName: 'RachidD Store',
            supportEmail: 'contato@rachidd.com'
        );

        $order = [
            'order_ref'              => 'ORD_DROP_12345',
            'customer_name'          => 'Carlos Silva',
            'customer_email'         => 'carlos@cliente.com',
            'product_title'          => 'Smartwatch Ultra Pro 8',
            'total_paid_brl'         => 189.90,
            'expected_delivery_days' => 12,
        ];

        $result = $service->notifyOrderPaid($order);

        $this->assertTrue($result);
        $this->assertCount(1, $sent);
        $this->assertSame('carlos@cliente.com', $sent[0]['to']);
        $this->assertStringContainsString('ORD_DROP_12345', $sent[0]['subject']);
        $this->assertStringContainsString('RachidD Store', $sent[0]['subject']);
        $this->assertStringContainsString('Carlos Silva', $sent[0]['body']);
        $this->assertStringContainsString('Smartwatch Ultra Pro 8', $sent[0]['body']);
        $this->assertStringContainsString('189,90', $sent[0]['body']);
        $this->assertStringContainsString('minha-conta', $sent[0]['body']);
    }

    public function testNotifyOrderShippedSendsHtmlEmailWithTrackingCode(): void
    {
        $sent = [];
        $mailer = new class ($sent) implements EmailSenderInterface {
            public function __construct(public array &$sent) {}
            public function send(string $to, string $subject, string $message): void
            {
                $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $message];
            }
        };

        $service = new DropshipCustomerNotificationService(
            mailer: $mailer,
            storeName: 'RachidD Store',
            supportEmail: 'contato@rachidd.com'
        );

        $order = [
            'order_ref'      => 'ORD_DROP_998877',
            'customer_name'  => 'Ana Pereira',
            'customer_email' => 'ana@cliente.com',
            'product_title'  => 'Fone Bluetooth Noise Cancelling',
        ];

        $result = $service->notifyOrderShipped($order, 'NL123456789BR');

        $this->assertTrue($result);
        $this->assertCount(1, $sent);
        $this->assertSame('ana@cliente.com', $sent[0]['to']);
        $this->assertStringContainsString('NL123456789BR', $sent[0]['subject']);
        $this->assertStringContainsString('ORD_DROP_998877', $sent[0]['subject']);
        $this->assertStringContainsString('NL123456789BR', $sent[0]['body']);
        $this->assertStringContainsString('Ana Pereira', $sent[0]['body']);
        $this->assertStringContainsString('/minha-conta?rastrear=NL123456789BR', $sent[0]['body']);
    }

    public function testReturnsFalseWhenEmailIsInvalidOrMissing(): void
    {
        $sent = [];
        $mailer = new class ($sent) implements EmailSenderInterface {
            public function __construct(public array &$sent) {}
            public function send(string $to, string $subject, string $message): void
            {
                $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $message];
            }
        };

        $service = new DropshipCustomerNotificationService($mailer);

        $this->assertFalse($service->notifyOrderPaid(['customer_email' => 'invalido']));
        $this->assertFalse($service->notifyOrderShipped(['customer_email' => ''], 'NL123BR'));
        $this->assertFalse($service->notifyOrderShipped(['customer_email' => 'valid@domain.com'], ''));
        $this->assertEmpty($sent);
    }

    public function testHandlesMailerExceptionGracefully(): void
    {
        $mailer = new class implements EmailSenderInterface {
            public function send(string $to, string $subject, string $message): void
            {
                throw new RuntimeException("SMTP Connection Timeout");
            }
        };

        $service = new DropshipCustomerNotificationService($mailer);

        $order = [
            'order_ref'      => 'ORD_ERR_1',
            'customer_email' => 'cliente@dominio.com',
        ];

        $this->assertFalse($service->notifyOrderPaid($order));
        $this->assertFalse($service->notifyOrderShipped($order, 'TRACK123'));
    }
}
