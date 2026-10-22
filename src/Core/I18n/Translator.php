<?php

declare(strict_types=1);

namespace DomainSystem\Core\I18n;

use DomainSystem\Core\Contracts\TranslatorInterface;

/**
 * Motor central de internacionalização (i18n) do Domain-System.
 *
 * Carrega dicionários JSON leves, suporta fallback em cascata,
 * interpolação de variáveis e exportação de catálogo para frontend (JavaScript).
 */
class Translator implements TranslatorInterface
{
    public const DEFAULT_LOCALE = 'pt_BR';

    public const SUPPORTED_LOCALES = [
        'pt_BR' => 'Português (Brasil)',
        'en_US' => 'English (US)',
        'es_ES' => 'Español',
        'zh_CN' => '简体中文',
    ];

    private string $locale;
    private string $resourcesPath;
    /** @var array<string, array<string, string>> */
    private array $catalogs = [];

    public function __construct(?string $resourcesPath = null, string $defaultLocale = self::DEFAULT_LOCALE)
    {
        $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        $this->resourcesPath = rtrim($resourcesPath ?? ($root . '/resources/lang'), '/\\');
        $this->locale = $this->sanitizeLocale($defaultLocale);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $this->sanitizeLocale($locale);
    }

    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $targetLocale = $locale !== null ? $this->sanitizeLocale($locale) : $this->locale;
        $catalog = $this->getCatalog($targetLocale);

        $translation = $catalog[$key] ?? null;

        // Fallback em cascata: se não achar no idioma alvo, busca no padrão (pt_BR)
        if ($translation === null && $targetLocale !== self::DEFAULT_LOCALE) {
            $defaultCatalog = $this->getCatalog(self::DEFAULT_LOCALE);
            $translation = $defaultCatalog[$key] ?? null;
        }

        // Se ainda assim não achar, retorna a própria chave
        if ($translation === null) {
            $translation = $key;
        }

        if (!empty($replace)) {
            foreach ($replace as $placeholder => $value) {
                $translation = str_replace(
                    [':' . $placeholder, '{' . $placeholder . '}'],
                    (string)$value,
                    $translation
                );
            }
        }

        return $translation;
    }

    /**
     * @return array<string, string>
     */
    public function getCatalog(?string $locale = null): array
    {
        $targetLocale = $locale !== null ? $this->sanitizeLocale($locale) : $this->locale;

        if (isset($this->catalogs[$targetLocale])) {
            return $this->catalogs[$targetLocale];
        }

        $file = $this->resourcesPath . '/' . $targetLocale . '.json';
        if (file_exists($file)) {
            $content = (string)file_get_contents($file);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $this->catalogs[$targetLocale] = $data;
                return $this->catalogs[$targetLocale];
            }
        }

        $this->catalogs[$targetLocale] = [];
        return $this->catalogs[$targetLocale];
    }

    private function sanitizeLocale(string $locale): string
    {
        $normalized = trim($locale);
        if (isset(self::SUPPORTED_LOCALES[$normalized])) {
            return $normalized;
        }

        // Tenta matching por prefixo (ex: 'en' -> 'en_US', 'es' -> 'es_ES', 'zh' -> 'zh_CN')
        $langCode = strtolower(substr($normalized, 0, 2));
        foreach (array_keys(self::SUPPORTED_LOCALES) as $valid) {
            if (strtolower(substr($valid, 0, 2)) === $langCode) {
                return $valid;
            }
        }

        return self::DEFAULT_LOCALE;
    }
}
