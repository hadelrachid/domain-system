<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Routing\Middlewares\CsrfMiddleware;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\SessionManager;

class CsrfMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        $logFile = DOMAIN_SYSTEM_ROOT . '/temp/csrf_debug.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function testCsrfFailureDoesNotLogActualToken()
    {
        $sessionMock = $this->createMock(SessionManager::class);
        $sessionMock->method('validateCsrfToken')->willReturn(false);
        $sessionMock->method('get')->willReturn('SECRET_REAL_TOKEN_123');

        $middleware = new CsrfMiddleware($sessionMock);
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/admin/test';
        $_POST['csrf_token'] = 'BAD_TOKEN_456';
        
        $request = Request::capture();
        
        // This will block and set a 403, maybe die or output.
        // The current middleware might send a view or die if not ajax.
        // Let's just catch the output or execution.
        ob_start();
        $middleware->handle($request, function($req) { return true; }, ['roles' => ['admin']]);
        ob_get_clean();

        $logFile = DOMAIN_SYSTEM_ROOT . '/temp/csrf_debug.log';
        $this->assertFileExists($logFile);
        
        $logContent = file_get_contents($logFile);
        
        // Must NOT contain the real token or the bad token
        $this->assertStringNotContainsString('SECRET_REAL_TOKEN_123', $logContent, 'O token real vazou no log!');
        $this->assertStringNotContainsString('BAD_TOKEN_456', $logContent, 'O token injetado vazou no log!');
        
        // Must contain safe metadata
        $this->assertStringContainsString('token_present=yes', $logContent);
        $this->assertStringContainsString('route=/admin/test', $logContent);
    }
}
