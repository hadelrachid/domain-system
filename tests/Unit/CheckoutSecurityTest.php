<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use PHPUnit\Framework\TestCase;

final class CheckoutSecurityTest extends TestCase
{
    public function testUnconfiguredGatewayCannotCreatePaidOrder(): void
    {
        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('stripe');
        $gateway->method('isConfigured')->willReturn(false);
        $gateway->expects($this->never())->method('processPayment');

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('stripe')->willReturn($gateway);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->never())->method('createOrder');

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($key) {
            return match ($key) {
                'academy_student_id', 'user_id' => 10,
                default => null,
            };
        });
        $session->method('getCsrfToken')->willReturn('valid_token');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(1)->willReturn([
            'id'     => 1,
            'title'  => 'Curso PHP Avançado',
            'price'  => 199.90,
            'status' => 'published',
        ]);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('get')->with('commerce_demo_mode', false)->willReturn(false);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            $courseRepo,
            null,
            null,
            $settings
        );

        $request = new Request([], [
            'csrf_token'     => 'valid_token',
            'course_id'      => 1,
            'gateway'        => 'stripe',
            'payment_method' => 'credit_card',
        ], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->process($request);

        $this->assertSame(409, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('não está disponível no momento', $data['message']);
    }

    public function testInvalidGatewayReturns422(): void
    {
        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('invalid_gw')->willReturn(null);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->never())->method('createOrder');

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(fn($k) => match ($k) {
            'academy_student_id' => 5,
            default => null,
        });
        $session->method('getCsrfToken')->willReturn('token');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(1)->willReturn([
            'id'     => 1,
            'title'  => 'Curso Básico',
            'price'  => 50.00,
            'status' => 'published',
        ]);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            $courseRepo
        );

        $request = new Request([], [
            'csrf_token' => 'token',
            'course_id'  => 1,
            'gateway'    => 'invalid_gw',
        ], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->process($request);
        $this->assertSame(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Meio de pagamento inválido.', $data['message']);
    }
}
