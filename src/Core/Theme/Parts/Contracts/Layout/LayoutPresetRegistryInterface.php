<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Layout;

/**
 * Contrato para o Registro Extensível de Presets de Layout (SOLID - OCP / DIP).
 */
interface LayoutPresetRegistryInterface
{
    /** Registra um novo preset de layout de forma extensível */
    public function register(LayoutPresetInterface $preset): void;

    /** Obtém um preset por ID */
    public function get(string $id): ?LayoutPresetInterface;

    /** Verifica se um preset existe */
    public function has(string $id): bool;

    /**
     * Retorna todos os presets instanciados.
     * @return array<string, LayoutPresetInterface>
     */
    public function all(): array;

    /**
     * Retorna todos os presets no formato de array associativo tradicional para views e controllers.
     * @return array<string, array<string, mixed>>
     */
    public function allAsArray(): array;

    /**
     * Retorna presets agrupados por categoria.
     * @return array<string, array<string, LayoutPresetInterface>>
     */
    public function allByCategory(): array;
}
