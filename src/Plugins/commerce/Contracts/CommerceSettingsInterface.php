<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato para Gerenciamento de Configurações do DomainCommerce (DIP / SOLID).
 */
interface CommerceSettingsInterface
{
    /**
     * Obtém uma chave de configuração.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Define uma chave de configuração.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Retorna todas as configurações.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Salva em lote as configurações informadas.
     *
     * @param array<string, mixed> $settings
     */
    public function save(array $settings): bool;
}
