<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Enum;

enum UpdateKind: string
{
    case Core = 'core';
    case Plugin = 'plugin';
}
