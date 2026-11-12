<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Http\CachePolicy;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class CachePolicyTest extends TestCase
{
    public function testSensitivePathsReceiveNoCacheHeaders(): void
    {
        $policy = new CachePolicy();

        foreach (['/admin', '/admin/settings', '/login', '/setup', '/api/data'] as $path) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = $path;

            $request = Request::capture();
            $response = new Response('OK', 200);

            $modifiedResponse = $policy->apply($request, $response);

            $cacheControl = $modifiedResponse->getHeader('Cache-Control');
            $this->assertNotNull($cacheControl, "Cache-Control deve existir para {$path}");
            $this->assertStringContainsString('no-store', $cacheControl);
            $this->assertStringContainsString('no-cache', $cacheControl);
            $this->assertStringContainsString('private', $cacheControl);

            $this->assertEquals('no-cache', $modifiedResponse->getHeader('Pragma'));
            $this->assertStringContainsString('Cookie', (string)$modifiedResponse->getHeader('Vary'));
        }
    }

    public function testLogoutReceivesClearSiteData(): void
    {
        $policy = new CachePolicy();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/logout';

        $request = Request::capture();
        $response = new Response('Redirecting', 302);

        $modifiedResponse = $policy->apply($request, $response);

        $clearSiteData = $modifiedResponse->getHeader('Clear-Site-Data');
        $this->assertNotNull($clearSiteData, 'Clear-Site-Data deve existir no logout.');
        $this->assertStringContainsString('"cache"', $clearSiteData);
    }

    public function testDynamicStorefrontRoutesReceiveNoCacheHeaders(): void
    {
        $policy = new CachePolicy();
        $oldUserId = $_SESSION['user_id'] ?? null;
        $oldCustomerId = $_SESSION['customer_id'] ?? null;
        unset($_SESSION['user_id'], $_SESSION['customer_id']);

        try {
            foreach (['/', '/loja', '/produto/123', '/minha-conta', '/cadastro'] as $path) {
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = $path;

                $request = Request::capture();
                $response = new Response('<html>OK</html>', 200);

                $modifiedResponse = $policy->apply($request, $response);

                $cacheControl = $modifiedResponse->getHeader('Cache-Control');
                $this->assertNotNull($cacheControl, "Cache-Control deve existir para {$path}");
                $this->assertStringContainsString('no-store', $cacheControl);
                $this->assertStringContainsString('no-cache', $cacheControl);
                $this->assertStringContainsString('must-revalidate', $cacheControl);
                $this->assertSame('no-cache', $modifiedResponse->getHeader('Pragma'));
            }
        } finally {
            if ($oldUserId !== null) {
                $_SESSION['user_id'] = $oldUserId;
            }
            if ($oldCustomerId !== null) {
                $_SESSION['customer_id'] = $oldCustomerId;
            }
        }
    }
}
