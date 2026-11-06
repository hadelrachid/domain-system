<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Contracts;

/**
 * Contrato de serialização segura de DTOs de widgets em HTML sanitizado (SOLID - DIP / ISP).
 */
interface DtoRendererInterface
{
    /**
     * @param array<string, mixed> $dto
     */
    public function render(array $dto): string;

    /**
     * @param array<int, array<string, mixed>> $tree
     */
    public function renderTree(array $tree): string;
}
