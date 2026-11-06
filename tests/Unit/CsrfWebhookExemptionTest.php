<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Routing\Middlewares\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

final class CsrfWebhookExemptionTest extends TestCase
{
    private SessionManagerInterface $session;
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionManagerInterface::class);
        $this->session->method('validateCsrfToken')->willReturn(false);
        $this->middleware = new CsrfMiddleware($this->session);
    }

    public function testWebhookRouteWithoutCsrfTokenPassesToNextHandler(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/webhook/payment/hotmart',
        ]);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('ok', 200);
        };

        $response = $this->middleware->handle($request, $next, ['capabilities' => []]);
        $this->assertTrue($nextCalled);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckoutProcessRouteWithoutCsrfTokenIsBlocked(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/checkout/process',
        ]);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('ok', 200);
        };

        $response = $this->middleware->handle($request, $next, []);
        $this->assertFalse($nextCalled);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCommerceSettingsWithoutCsrfTokenIsBlocked(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/commerce/settings',
        ]);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('ok', 200);
        };

        $response = $this->middleware->handle($request, $next, ['capabilities' => ['admin']]);
        $this->assertFalse($nextCalled);
        $this->assertSame(403, $response->getStatusCode());
    }
}
