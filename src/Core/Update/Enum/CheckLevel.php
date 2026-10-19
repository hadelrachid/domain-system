<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Enum;

enum CheckLevel: string
{
    case Ok = 'ok';
    case Warn = 'warn';
    case Block = 'block';
}
