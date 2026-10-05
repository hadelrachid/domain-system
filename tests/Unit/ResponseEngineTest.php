<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\RedirectResponse;

class ResponseEngineTest extends TestCase
{
    public function testViewResponseHandlesLayoutFlag()
    {
        $response = new ViewResponse("<h1>Hello</h1>", true);
        $this->assertTrue($response->shouldWrapLayout());
        $this->assertEquals("<h1>Hello</h1>", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());

        $noLayout = new ViewResponse("<p>Raw</p>", false);
        $this->assertFalse($noLayout->shouldWrapLayout());
    }

    public function testJsonResponseEncodesDataAndSetsHeaders()
    {
        $data = ['status' => 'ok', 'user_id' => 10];
        $response = new JsonResponse($data, 201);
        
        $this->assertEquals('{"status":"ok","user_id":10}', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        // JsonResponse should never be wrapped in layout
        if (method_exists($response, 'shouldWrapLayout')) {
            $this->assertFalse($response->shouldWrapLayout());
        }
    }

    public function testRedirectResponseSetsLocationHeader()
    {
        $response = new RedirectResponse('/admin/dashboard', 301);
        
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/admin/dashboard', $response->getHeader('Location'));
    }
}
