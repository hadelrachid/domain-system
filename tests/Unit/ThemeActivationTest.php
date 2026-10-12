<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Core\Theme\ThemeActivator;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\SystemApps\SystemAdmin\Controllers\AdminController;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;

class ThemeActivationTest extends TestCase
{
    private string $tempConfigDir;
    private array $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = [
            'ACTIVE_THEME' => getenv('ACTIVE_THEME') ?: null,
            'SITE_STATUS'  => getenv('SITE_STATUS') ?: null,
        ];
        $this->tempConfigDir = DOMAIN_SYSTEM_ROOT . '/temp/test_themes_' . uniqid();
        @mkdir($this->tempConfigDir . '/config', 0755, true);
        @mkdir($this->tempConfigDir . '/themes/tema_alpha', 0755, true);
        @mkdir($this->tempConfigDir . '/themes/tema_beta', 0755, true);
        @mkdir($this->tempConfigDir . '/src/Plugins/flextheme/themes/default', 0755, true);

        file_put_contents($this->tempConfigDir . '/themes/tema_alpha/theme.json', json_encode(['name' => 'Tema Alpha']));
        file_put_contents($this->tempConfigDir . '/themes/tema_beta/theme.json', json_encode(['name' => 'Tema Beta']));
        file_put_contents($this->tempConfigDir . '/src/Plugins/flextheme/themes/default/theme.json', json_encode(['name' => 'Default']));
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv['ACTIVE_THEME'] !== null) {
            putenv("ACTIVE_THEME={$this->originalEnv['ACTIVE_THEME']}");
            $_ENV['ACTIVE_THEME'] = $this->originalEnv['ACTIVE_THEME'];
        } else {
            putenv('ACTIVE_THEME');
            unset($_ENV['ACTIVE_THEME']);
        }
        if ($this->originalEnv['SITE_STATUS'] !== null) {
            putenv("SITE_STATUS={$this->originalEnv['SITE_STATUS']}");
            $_ENV['SITE_STATUS'] = $this->originalEnv['SITE_STATUS'];
        } else {
            putenv('SITE_STATUS');
            unset($_ENV['SITE_STATUS']);
        }
        $this->recursiveDelete($this->tempConfigDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $p = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($p) ? $this->recursiveDelete($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    public function testSafeThemeResolverDiscoversThemesInMultipleRoots(): void
    {
        $resolver = new SafeThemeResolver([
            $this->tempConfigDir . '/themes',
            $this->tempConfigDir . '/src/Plugins/flextheme/themes'
        ]);

        $this->assertTrue($resolver->themeExists('tema_alpha'));
        $this->assertTrue($resolver->themeExists('tema_beta'));
        $this->assertTrue($resolver->themeExists('default'));
        $this->assertFalse($resolver->themeExists('tema_fantasma'));

        $pathAlpha = $resolver->resolveThemePath('tema_alpha');
        $this->assertStringContainsString('tema_alpha', $pathAlpha);

        $pathDefault = $resolver->resolveThemePath('default');
        $this->assertStringContainsString('default', $pathDefault);

        // Fallback resiliente quando solicitado tema inexistente
        $fallback = $resolver->resolveThemePath('inexistente');
        $this->assertStringContainsString('default', $fallback);
    }

    public function testThemeActivatorActivatesExistingThemeAndPersistsState(): void
    {
        $resolver = new SafeThemeResolver([
            $this->tempConfigDir . '/themes',
            $this->tempConfigDir . '/src/Plugins/flextheme/themes'
        ]);

        $activator = new ThemeActivator($this->tempConfigDir, $resolver);

        // Estado inicial
        $this->assertEquals('default', $activator->getActiveTheme());

        // Ativa tema_beta
        $result = $activator->activate('tema_beta');
        $this->assertTrue($result['success']);
        $this->assertEquals('tema_beta', $result['theme']);

        // Verifica persistência
        $this->assertEquals('tema_beta', $activator->getActiveTheme());

        $configFile = $this->tempConfigDir . '/config/active_theme.json';
        $this->assertFileExists($configFile);

        $saved = json_decode((string)file_get_contents($configFile), true);
        $this->assertEquals('tema_beta', $saved['active_theme']);
        $this->assertEquals('live', $saved['status']);
    }

    public function testThemeActivatorRejectsNonexistentTheme(): void
    {
        $resolver = new SafeThemeResolver([
            $this->tempConfigDir . '/themes',
            $this->tempConfigDir . '/src/Plugins/flextheme/themes'
        ]);

        $activator = new ThemeActivator($this->tempConfigDir, $resolver);
        $result = $activator->activate('tema_fantasma');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('não foi encontrado', $result['message']);
    }

    public function testEnvSiteStateReadsThemeFromActiveThemeJson(): void
    {
        $configFile = $this->tempConfigDir . '/config/active_theme.json';
        file_put_contents($configFile, json_encode([
            'active_theme' => 'tema_alpha',
            'status' => 'live'
        ]));

        $siteState = new EnvSiteState(null, null, $this->tempConfigDir);

        $this->assertEquals('tema_alpha', $siteState->getActiveTheme());
        $this->assertTrue($siteState->isLive());
        $this->assertFalse($siteState->isComingSoon());
    }

    public function testAdminControllerActivateThemeEndpoint(): void
    {
        $resolver = new SafeThemeResolver([
            $this->tempConfigDir . '/themes',
            $this->tempConfigDir . '/src/Plugins/flextheme/themes'
        ]);
        $activator = new ThemeActivator($this->tempConfigDir, $resolver);

        $pluginManager = $this->createMock(PluginManager::class);
        $themeManager = $this->createMock(ThemeManagerInterface::class);
        $shortcodes = $this->createMock(ShortcodeManager::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $session = $this->createMock(SessionManagerInterface::class);

        $session->expects($this->once())
            ->method('setFlash')
            ->with('success', $this->stringContains('tema_alpha'));

        $controller = new AdminController($pluginManager, $themeManager, $shortcodes, $dispatcher, $session, $activator);

        $request = new Request([], ['theme_folder' => 'tema_alpha'], ['REQUEST_METHOD' => 'POST']);
        $response = $controller->activateTheme($request);

        $this->assertInstanceOf(\DomainSystem\Core\Http\Response::class, $response);
        $this->assertEquals('tema_alpha', $activator->getActiveTheme());
    }

    public function testAdminControllerPreviewThemeRendersRequestedTheme(): void
    {
        // 1. Cria index.php específico para tema_alpha e tema_beta
        file_put_contents($this->tempConfigDir . '/themes/tema_alpha/index.php', '<h1>Conteudo Exclusivo do Tema Alpha</h1>');
        file_put_contents($this->tempConfigDir . '/themes/tema_beta/index.php', '<h1>Conteudo Exclusivo do Tema Beta</h1>');

        $resolver = new SafeThemeResolver([
            $this->tempConfigDir . '/themes',
            $this->tempConfigDir . '/src/Plugins/flextheme/themes'
        ]);
        $activator = new ThemeActivator($this->tempConfigDir, $resolver);
        
        // Define tema_beta como ATIVO
        $activator->activate('tema_beta');

        $pluginManager = $this->createMock(PluginManager::class);
        $themeManager = $this->createMock(ThemeManagerInterface::class);
        $shortcodes = $this->createMock(ShortcodeManager::class);
        $shortcodes->method('parse')->willReturnArgument(0);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('token_teste_123');

        $controller = new AdminController($pluginManager, $themeManager, $shortcodes, $dispatcher, $session, $activator);

        // 2. Solicita preview de tema_alpha (que NÃO está ativo)
        $request = new Request(['theme' => 'tema_alpha']);
        $response = $controller->previewTheme($request);

        $this->assertInstanceOf(\DomainSystem\Core\Http\Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        
        $body = $response->getContent();
        // Deve renderizar tema_alpha, NUNCA tema_beta
        $this->assertStringContainsString('Conteudo Exclusivo do Tema Alpha', $body);
        $this->assertStringNotContainsString('Conteudo Exclusivo do Tema Beta', $body);
        
        // Deve conter a barra de preview com o botão de ativação
        $this->assertStringContainsString('ds-preview-topbar', $body);
        $this->assertStringContainsString('tema_alpha', $body);
        $this->assertStringContainsString('Ativar este Tema', $body);
    }
}
