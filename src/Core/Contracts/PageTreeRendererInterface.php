<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface PageTreeRendererInterface
{
    public function renderTree(array $tree): string;
}
