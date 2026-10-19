<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\SystemApps\installer\Services\InstallerService;
use PHPUnit\Framework\TestCase;

class InstallerServiceTest extends TestCase
{
    private string $testTokenDir;
    private string $testTokenFile;

    protected function setUp(): void
    {
        $this->testTokenDir = DOMAIN_SYSTEM_ROOT . '/token';
        $this->testTokenFile = $this->testTokenDir . '/setup.token';
    }

    public function testGetOrCreateAndRegenerateSetupToken(): void
    {
        $service = new InstallerService();

        // 1. Gera ou recupera token
        $token1 = $service->getOrCreateSetupToken();
        $this->assertNotEmpty($token1);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{4}-[A-F0-9]{4}$/', $token1);
        $this->assertFileExists($this->testTokenFile);
        $this->assertFileExists($this->testTokenDir . '/.htaccess');

        // 2. Segunda chamada retorna o mesmo token sem alterar o arquivo
        $token2 = $service->getOrCreateSetupToken();
        $this->assertSame($token1, $token2);

        // 3. Validação com sucesso e com falha
        $this->assertTrue($service->validateSetupToken($token1));
        $this->assertTrue($service->validateSetupToken(strtolower($token1))); // Case-insensitive
        $this->assertFalse($service->validateSetupToken('WRONG-TOKEN'));

        // 4. Regenerar token cria um novo código
        $token3 = $service->regenerateSetupToken();
        $this->assertNotEmpty($token3);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{4}-[A-F0-9]{4}$/', $token3);
        $this->assertTrue($service->validateSetupToken($token3));
    }
}
