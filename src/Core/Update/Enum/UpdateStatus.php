<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Enum;

enum UpdateStatus: string
{
    case UpToDate = 'up_to_date';
    case Available = 'available';
    case Incompatible = 'incompatible';
    case Unknown = 'unknown';
}
