<?php

namespace DomainSystem\Core\Contracts;

interface PageTreeRendererInterface
{
    public function renderTree(array $tree): string;
}
