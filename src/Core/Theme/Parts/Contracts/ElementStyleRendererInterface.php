<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato para renderização e higienização de estilos CSS de elementos de bloco (SOLID - SRP / ISP).
 */
interface ElementStyleRendererInterface
{
    /**
     * Gera a string de atributos de estilo CSS (ex: "margin: 10px; padding: 5px;")
     * a partir de um array de propriedades de dados do bloco.
     *
     * @param array<string, mixed> $data
     */
    public function renderStyleString(array $data): string;

    /**
     * Decompõe uma string de espaçamento nos 4 lados (superior, direita, inferior, esquerda).
     *
     * @return array{top: string, right: string, bottom: string, left: string}
     */
    public function parse4Sides(string $spacing): array;
}
