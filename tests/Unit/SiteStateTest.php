<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\Core\Theme\SafeThemeResolver;

class SiteStateTest extends TestCase
{
    private array $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = [
            'SITE_STATUS' => getenv('SITE_STATUS') ?: null,
            'ACTIVE_THEME' => getenv('ACTIVE_THEME') ?: null,
        ];
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv['SITE_STATUS'] !== null) {
            putenv("SITE_STATUS={$this->originalEnv['SITE_STATUS']}");
        } else {
            putenv('SITE_STATUS');
        }
        if ($this->originalEnv['ACTIVE_THEME'] !== null) {
            putenv("ACTIVE_THEME={$this->originalEnv['ACTIVE_THEME']}");
        } else {
            putenv('ACTIVE_THEME');
        }
    }

    public function testSiteStatusDefaultsToLiveWhenNotConfigured(): void
    {
        putenv('SITE_STATUS');
        unset($_ENV['SITE_STATUS']);

        $state = new EnvSiteState();

        $this->assertEquals(SiteStateInterface::STATUS_LIVE, $state->getStatus());
        $this->assertTrue($state->isLive());
        $this->assertFalse($state->isComingSoon());
    }

    public function testSiteStatusComingSoon(): void
    {
        putenv('SITE_STATUS=coming_soon');
        $_ENV['SITE_STATUS'] = 'coming_soon';

        $state = new EnvSiteState();

        $this->assertEquals(SiteStateInterface::STATUS_COMING_SOON, $state->getStatus());
        $this->assertTrue($state->isComingSoon());
        $this->assertFalse($state->isLive());
    }

    public function testSafeThemeResolverFallsBackWhenThemeIsMissing(): void
    {
        $tempThemes = sys_get_temp_dir() . '/ds_themes_test_' . bin2hex(random_bytes(5));
        mkdir($tempThemes . '/default', 0777, true);

        try {
            $resolver = new SafeThemeResolver($tempThemes);

            // Tenta um tema inexistente (ex: 'rachidd' que estava no .gitignore)
            $resolved = $resolver->resolveThemePath('rachidd');

            // Deve cair com segurança no tema 'default'
            $this->assertDirectoryExists($resolved);
            $this->assertStringEndsWith('default', str_replace('\\', '/', $resolved));
        } finally {
            @rmdir($tempThemes . '/default');
            @rmdir($tempThemes);
        }
    }
}
