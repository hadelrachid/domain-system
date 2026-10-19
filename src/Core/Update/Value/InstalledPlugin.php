<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class InstalledPlugin
{
    public function __construct(
        public string $slug,
        public string $version,
        public string $osVersion,
        public bool $isCore,
        public bool $isActive,
    ) {
        if (trim($this->slug) === '') {
            throw new InvalidArgumentException('O slug de InstalledPlugin não pode ser vazio.');
        }
        if (trim($this->version) === '') {
            throw new InvalidArgumentException('A versão de InstalledPlugin não pode ser vazia.');
        }
        if (trim($this->osVersion) === '') {
            throw new InvalidArgumentException('O osVersion de InstalledPlugin não pode ser vazio.');
        }
    }
}
