<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Http\SessionManager;

class SessionManagerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSessionInitializationSetsSecureCookieParams()
    {
        $manager = new SessionManager();
        $manager->start();

        $params = session_get_cookie_params();
        
        $this->assertTrue($params['httponly'], 'A sessão deve ter a flag HttpOnly habilitada');
        $this->assertEquals('Strict', $params['samesite'], 'A sessão deve ter o SameSite=Strict');
        
        // Verifica se a flag strict_mode foi setada no INI
        $this->assertEquals('1', ini_get('session.use_strict_mode'), 'O session.use_strict_mode deve estar ativado');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionGeneratesCsrfToken()
    {
        $manager = new SessionManager();
        $manager->start();

        $token = $manager->getCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // bin2hex of 32 bytes = 64 chars
    }

    /**
     * @runInSeparateProcess
     */
    public function testCsrfTokenValidation()
    {
        $manager = new SessionManager();
        $manager->start();
        $token = $manager->getCsrfToken();

        $this->assertTrue($manager->validateCsrfToken($token));
        $this->assertFalse($manager->validateCsrfToken('invalid_token'));
        $this->assertFalse($manager->validateCsrfToken(null));
        $this->assertFalse($manager->validateCsrfToken(''));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFlashMessages()
    {
        $manager = new SessionManager();
        $manager->start();

        $manager->setFlash('success', 'Operação realizada');
        $flash = $manager->getFlash();

        $this->assertEquals(['type' => 'success', 'msg' => 'Operação realizada'], $flash);
        $this->assertNull($manager->getFlash(), 'O flash message deve ser removido após a primeira leitura');
    }
}
