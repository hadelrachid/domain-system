<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Http\SessionManager;

class SessionManagerDestroyTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    public function testDestroyClearsSessionArrayInCurrentRequest(): void
    {
        $session = new SessionManager();
        $_SESSION['user_id'] = 42;
        $_SESSION['role'] = 'admin';

        $session->destroy();

        $this->assertEmpty($_SESSION, 'O array $_SESSION deve ser esvaziado no destroy().');
        $this->assertNull($session->get('user_id'));
    }

    public function testIsSecureRequestDetectsForwardedHeaders(): void
    {
        $session = new SessionManager();

        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertTrue(
            $session->isSecureRequest(),
            'Deve identificar HTTPS quando HTTP_X_FORWARDED_PROTO for https (Hostinger / CDN).'
        );
    }
}
