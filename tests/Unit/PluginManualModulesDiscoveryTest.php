<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\ManualModuleInterface;
use DomainSystem\Core\Manual\PluginManualScanner;
use DomainSystem\Plugins\academy\manual\AcademyManualModule;
use DomainSystem\Plugins\commerce\manual\CommerceManualModule;
use PHPUnit\Framework\TestCase;

final class PluginManualModulesDiscoveryTest extends TestCase
{
    public function testPluginManualScannerDiscoversAcademyAndCommerceModules(): void
    {
        $rootDir = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 2);
        $scanner = new PluginManualScanner($rootDir);
        $modules = $scanner->discover();

        $this->assertNotEmpty($modules, 'O scanner deve descobrir módulos de manual na árvore do projeto.');

        // 1. Verifica Módulo Academy
        $this->assertArrayHasKey('academy', $modules, 'O módulo manual da Academy deve ser auto-descoberto.');
        $academy = $modules['academy'];
        $this->assertInstanceOf(ManualModuleInterface::class, $academy);
        $this->assertInstanceOf(AcademyManualModule::class, $academy);
        $this->assertStringContainsString('Academy', $academy->getTitle());
        $this->assertStringContainsString('/admin/academy', (string)$academy->getAdminUrl());

        $academyChecklist = $academy->getChecklistItems();
        $this->assertGreaterThanOrEqual(10, count($academyChecklist));

        $renderedAcademy = $academy->renderContent('http://localhost');
        $this->assertStringContainsString('Visualizador de PDF', $renderedAcademy);
        $this->assertStringContainsString('Cockpit do Aluno', $renderedAcademy);
        $this->assertStringContainsString('DomainCommerce', $renderedAcademy);

        // 2. Verifica Módulo DomainCommerce
        $this->assertArrayHasKey('commerce', $modules, 'O módulo manual do DomainCommerce deve ser auto-descoberto.');
        $commerce = $modules['commerce'];
        $this->assertInstanceOf(ManualModuleInterface::class, $commerce);
        $this->assertInstanceOf(CommerceManualModule::class, $commerce);
        $this->assertStringContainsString('DomainCommerce', $commerce->getTitle());
        $this->assertStringContainsString('/admin/commerce', (string)$commerce->getAdminUrl());
        $this->assertSame('#00d284', $commerce->getAccentColor());

        $commerceChecklist = $commerce->getChecklistItems();
        $this->assertGreaterThanOrEqual(7, count($commerceChecklist));

        $renderedCommerce = $commerce->renderContent('http://localhost');
        $this->assertStringContainsString('Mercado Pago', $renderedCommerce);
        $this->assertStringContainsString('PayPal', $renderedCommerce);
        $this->assertStringContainsString('Stripe', $renderedCommerce);
        $this->assertStringContainsString('payment.approved', $renderedCommerce);
    }
}
