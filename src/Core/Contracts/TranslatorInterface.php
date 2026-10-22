<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato de Internacionalização e Tradução do Domain-System (Ring 0 / SOLID).
 */
interface TranslatorInterface
{
    /**
     * Traduz uma chave para o idioma corrente ou especificado.
     *
     * @param string $key Chave de tradução (ex.: 'spacing_and_dimensions')
     * @param array<string, string|int|float> $replace Valores para interpolação
     * @param string|null $locale Idioma forçado ou nulo para o idioma ativo
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string;

    /**
     * Retorna o código do idioma ativo (ex.: 'pt_BR', 'en_US', 'es_ES', 'zh_CN').
     */
    public function getLocale(): string;

    /**
     * Define o idioma ativo.
     */
    public function setLocale(string $locale): void;

    /**
     * Retorna o catálogo completo de termos do idioma para exportação ao JavaScript.
     *
     * @return array<string, string>
     */
    public function getCatalog(?string $locale = null): array;
}
