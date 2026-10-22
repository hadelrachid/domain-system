<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ThemePartsFoundationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_theme_parts_test_' . uniqid('', true);
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testThemePartModelValidationAndSerialization(): void
    {
        $part = new ThemePart(
            'header_principal',
            ThemePartInterface::TYPE_HEADER,
            'Header Principal Pro',
            '3_cols_header',
            ['boxed' => true, 'gap' => 'medium'],
            [
                0 => [['type' => 'logo', 'data' => ['text' => 'DomainSystem']]],
                1 => [['type' => 'nav_menu', 'data' => ['menu_id' => 1]]],
                2 => [['type' => 'cta_button', 'data' => ['label' => 'Acessar']]],
            ],
            MobileToggleStyleInterface::STYLE_PILL,
            true
        );

        $this->assertSame('header_principal', $part->getId());
        $this->assertSame(ThemePartInterface::TYPE_HEADER, $part->getType());
        $this->assertSame('Header Principal Pro', $part->getTitle());
        $this->assertSame('3_cols_header', $part->getLayoutPreset());
        $this->assertSame(MobileToggleStyleInterface::STYLE_PILL, $part->getMobileToggleStyle());
        $this->assertSame('right', $part->getMobileToggleAlign());
        $this->assertTrue($part->isDefault());
        $this->assertCount(3, $part->getColumnsData());

        $serialized = $part->toArray();
        $this->assertIsArray($serialized);
        $this->assertSame('header_principal', $serialized['id']);
        $this->assertSame('right', $serialized['mobile_toggle_align']);

        $serialized['mobile_toggle_align'] = 'center';
        $restored = ThemePart::fromArray($serialized);
        $this->assertSame($part->getId(), $restored->getId());
        $this->assertSame($part->getTitle(), $restored->getTitle());
        $this->assertSame($part->getLayoutPreset(), $restored->getLayoutPreset());
        $this->assertSame($part->getMobileToggleStyle(), $restored->getMobileToggleStyle());
        $this->assertSame('center', $restored->getMobileToggleAlign());
    }

    public function testThemePartModelThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ThemePart('id_invalido', 'tipo_inexistente', 'Titulo');
    }

    public function testDesignTokensGenerationAndPersistence(): void
    {
        $storage = $this->tempDir . '/design_tokens.json';
        $tokens = new DesignTokens($storage);

        // 1. Valores padrão
        $palette = $tokens->getColorPalette();
        $this->assertArrayHasKey('primary', $palette);
        $this->assertArrayHasKey('background', $palette);

        $typography = $tokens->getTypography();
        $this->assertArrayHasKey('primary_font', $typography);

        // 2. Geração de CSS Variables
        $css = $tokens->generateCssVariables();
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--primary: #45f3ff;', $css);
        $this->assertStringContainsString('--font-primary: Inter', $css);

        // 3. Atualização e persistência
        $updated = $tokens->updateTokens([
            'palette' => ['primary' => '#ff0055'],
            'typography' => ['primary_font' => 'Poppins, sans-serif'],
        ]);
        $this->assertTrue($updated);

        // 4. Recarrega do arquivo
        $tokensReloaded = new DesignTokens($storage);
        $newCss = $tokensReloaded->generateCssVariables();
        $this->assertStringContainsString('--primary: #ff0055;', $newCss);
        $this->assertStringContainsString('--font-primary: Poppins', $newCss);
    }

    public function testJsonThemePartRepositoryCrud(): void
    {
        $repoDir = $this->tempDir . '/parts';
        $repo = new JsonThemePartRepository($repoDir);

        $header = new ThemePart('header_1', ThemePartInterface::TYPE_HEADER, 'Header 1');
        $footer = new ThemePart('footer_1', ThemePartInterface::TYPE_FOOTER, 'Footer 1');

        $this->assertTrue($repo->save($header));
        $this->assertTrue($repo->save($footer));

        $this->assertCount(2, $repo->all());
        $this->assertCount(1, $repo->findByType(ThemePartInterface::TYPE_HEADER));
        $this->assertCount(1, $repo->findByType(ThemePartInterface::TYPE_FOOTER));

        $found = $repo->findById('header_1');
        $this->assertNotNull($found);
        $this->assertSame('Header 1', $found->getTitle());

        $this->assertTrue($repo->delete('header_1'));
        $this->assertNull($repo->findById('header_1'));
        $this->assertCount(1, $repo->all());
    }

    public function testJsonThemePartAssociationResolutionAndFallback(): void
    {
        $assocFile = $this->tempDir . '/associations.json';
        $assoc = new JsonThemePartAssociation($assocFile);

        // Inicialmente nada atribuído (retorna null -> aciona fallback nativo)
        $this->assertNull($assoc->getAssignedPartId('default', 'header'));

        // Atribui global default
        $this->assertTrue($assoc->setGlobalDefault('header', 'header_global'));
        $this->assertSame('header_global', $assoc->getAssignedPartId('default', 'header'));

        // Atribui header específico ao tema 'flextheme_dark' (sobrescreve o global)
        $this->assertTrue($assoc->assignPart('flextheme_dark', 'header', 'header_dark_exclusivo'));
        $this->assertSame('header_dark_exclusivo', $assoc->getAssignedPartId('flextheme_dark', 'header'));
        $this->assertSame('header_global', $assoc->getAssignedPartId('default', 'header')); // default continua com o global

        // Remove a associação específica do tema (restaura o global)
        $this->assertTrue($assoc->assignPart('flextheme_dark', 'header', null));
        $this->assertSame('header_global', $assoc->getAssignedPartId('flextheme_dark', 'header'));

        // Remove o global (restaura null / fallback nativo de arquivo)
        $this->assertTrue($assoc->setGlobalDefault('header', null));
        $this->assertNull($assoc->getAssignedPartId('flextheme_dark', 'header'));
    }
}
