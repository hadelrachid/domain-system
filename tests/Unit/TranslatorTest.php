<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\I18n\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_i18n_test_' . uniqid('', true);
        @mkdir($this->tempDir, 0755, true);

        file_put_contents($this->tempDir . '/pt_BR.json', json_encode([
            'hello'     => 'Olá, :name!',
            'welcome'   => 'Bem-vindo ao sistema',
            'save'      => 'Salvar',
        ]));

        file_put_contents($this->tempDir . '/en_US.json', json_encode([
            'hello'     => 'Hello, :name!',
            'welcome'   => 'Welcome to the system',
        ]));
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    public function testTranslatesInDefaultLocale(): void
    {
        $translator = new Translator($this->tempDir, 'pt_BR');
        $this->assertSame('Bem-vindo ao sistema', $translator->trans('welcome'));
    }

    public function testTranslatesInEnglishLocale(): void
    {
        $translator = new Translator($this->tempDir, 'en_US');
        $this->assertSame('Welcome to the system', $translator->trans('welcome'));
    }

    public function testInterpolatesVariables(): void
    {
        $translator = new Translator($this->tempDir, 'en_US');
        $this->assertSame('Hello, Rachid!', $translator->trans('hello', ['name' => 'Rachid']));
    }

    public function testFallsBackToDefaultLocaleWhenKeyMissing(): void
    {
        $translator = new Translator($this->tempDir, 'en_US');
        // 'save' existe em pt_BR mas não em en_US
        $this->assertSame('Salvar', $translator->trans('save'));
    }

    public function testReturnsKeyWhenTranslationNotFoundAnywhere(): void
    {
        $translator = new Translator($this->tempDir, 'en_US');
        $this->assertSame('untranslated_key', $translator->trans('untranslated_key'));
    }

    public function testNormalizesLocalePrefix(): void
    {
        $translator = new Translator($this->tempDir, 'en');
        $this->assertSame('en_US', $translator->getLocale());

        $translator->setLocale('es');
        $this->assertSame('es_ES', $translator->getLocale());

        $translator->setLocale('zh');
        $this->assertSame('zh_CN', $translator->getLocale());
    }
}
