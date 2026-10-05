<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\SystemApps\SystemAdmin\Controllers\DevModeController;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;

class DevModeControllerTest extends TestCase
{
    private string $tempThemesPath;

    protected function setUp(): void
    {
        if (!defined('BASE_URL')) define('BASE_URL', '');
        $this->tempThemesPath = sys_get_temp_dir() . '/domain_system_devmode_' . uniqid();
        mkdir($this->tempThemesPath);
        mkdir($this->tempThemesPath . '/admin');
        
        file_put_contents(
            $this->tempThemesPath . '/admin/layout.php', 
            '<html><?= $content ?? "" ?></html>'
        );

        file_put_contents(
            $this->tempThemesPath . '/admin/dev_mode_prompt.php', 
            '<?php ob_start(); ?><h1>Prompt Dev Mode</h1><?php $content = ob_get_clean(); require __DIR__ . "/layout.php"; ?>'
        );
    }

    protected function tearDown(): void
    {
        unlink($this->tempThemesPath . '/admin/layout.php');
        unlink($this->tempThemesPath . '/admin/dev_mode_prompt.php');
        rmdir($this->tempThemesPath . '/admin');
        rmdir($this->tempThemesPath);
    }

    public function testPromptRendersCorrectly()
    {
        $themeManager = new ThemeManager($this->tempThemesPath . '/admin');
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturn('http://redirect.test');

        $controller = new DevModeController($themeManager, $sessionMock, $userRepoMock);
        $response = $controller->prompt($request);
        
        $this->assertStringContainsString('Prompt Dev Mode', $response->getContent());
    }

    public function testAuthenticateFailsWithoutUserId()
    {
        $themeManager = new ThemeManager($this->tempThemesPath . '/admin');
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);

        $sessionMock->expects($this->any())->method('get')->with('user_id')->willReturn(null);

        $request = $this->createMock(Request::class);

        $controller = new DevModeController($themeManager, $sessionMock, $userRepoMock);
        $response = $controller->authenticate($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/admin/login', $response->getHeader('Location'));
    }

    public function testAuthenticateFailsWithWrongPassword()
    {
        $themeManager = new ThemeManager($this->tempThemesPath . '/admin');
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);

        $sessionMock->expects($this->any())->method('get')->with('user_id')->willReturn(1);
        $userRepoMock->expects($this->any())->method('findById')->with(1)->willReturn([
            'id' => 1,
            'password' => password_hash('correct_password', PASSWORD_DEFAULT)
        ]);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturnCallback(function($key, $default) {
            if ($key === 'password') return 'wrong_password';
            if ($key === 'redirect') return '/admin/test';
            return $default;
        });

        // Expect setFlash to be called with error
        $sessionMock->expects($this->once())->method('setFlash')->with('error', 'Senha incorreta.');

        $controller = new DevModeController($themeManager, $sessionMock, $userRepoMock);
        $response = $controller->authenticate($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/admin/dev-mode?redirect=', $response->getHeader('Location'));
    }

    public function testAuthenticateSucceedsWithCorrectPassword()
    {
        $themeManager = new ThemeManager($this->tempThemesPath . '/admin');
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $userRepoMock = $this->createMock(UserRepositoryInterface::class);

        $sessionMock->expects($this->any())->method('get')->with('user_id')->willReturn(1);
        $userRepoMock->expects($this->any())->method('findById')->with(1)->willReturn([
            'id' => 1,
            'password' => password_hash('correct_password', PASSWORD_DEFAULT)
        ]);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('input')->willReturnCallback(function($key, $default) {
            if ($key === 'password') return 'correct_password';
            if ($key === 'redirect') return '/admin/test';
            return $default;
        });

        $sessionMock->expects($this->any())->method('set');
        $sessionMock->expects($this->once())->method('setFlash')->with('success', 'Modo Desenvolvedor ativado com sucesso.');

        $controller = new DevModeController($themeManager, $sessionMock, $userRepoMock);
        $response = $controller->authenticate($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/admin/test', $response->getHeader('Location'));
    }
}
