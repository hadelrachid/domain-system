<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\SystemApps\SystemAdmin\Controllers\AdminController;

class AdminManualTest extends TestCase
{
    public function testManualFilesExistAndContainRequiredSections(): void
    {
        $manualFile = DOMAIN_SYSTEM_ROOT . '/manual/index.php';
        $adminView = DOMAIN_SYSTEM_ROOT . '/themes/admin/manual.php';

        $this->assertFileExists($manualFile, "O arquivo manual/index.php deve existir.");
        $this->assertFileExists($adminView, "A view themes/admin/manual.php deve existir.");

        $content = file_get_contents($manualFile);
        $this->assertStringContainsString('Manual de Instruções', $content);
        $this->assertStringContainsString('Gerenciador de Temas', $content);
        $this->assertStringContainsString('Logout e Blindagem Anti-Cache', $content);
        $this->assertStringContainsString('Estado do Site', $content);
        $this->assertStringContainsString('Gerenciador de Menus', $content);
        $this->assertStringContainsString('Plugin Academy', $content);
        $this->assertStringContainsString('Instalador Wizard', $content);
        $this->assertStringContainsString('Suíte de Testes Automatizados', $content);
        $this->assertStringContainsString('ds_manual_checklist_state', $content);
    }

    public function testAdminControllerRendersManualView(): void
    {
        $manager = $this->createMock(PluginManager::class);
        $theme = new ThemeManager(DOMAIN_SYSTEM_ROOT . '/themes/admin');
        $shortcodes = new ShortcodeManager();
        $dispatcher = new EventDispatcher();
        $session = $this->createMock(SessionManager::class);

        $controller = new AdminController($manager, $theme, $shortcodes, $dispatcher, $session);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/manual']);

        $html = $controller->manual($request);

        $this->assertIsString($html);
        $this->assertStringContainsString('Manual de Instruções e Testabilidade', $html);
        $this->assertStringContainsString('Índice Rápido dos Módulos', $html);
        $this->assertStringContainsString('adminProgressBar', $html);
    }
}
