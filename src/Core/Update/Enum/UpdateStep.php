<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Enum;

enum UpdateStep: string
{
    case Idle = 'idle';
    case Checked = 'checked';
    case Downloaded = 'downloaded';
    case Verified = 'verified';
    case Preflighted = 'preflighted';
    case BackedUp = 'backed_up';
    case MaintenanceOn = 'maintenance_on';
    case Applied = 'applied';
    case Migrated = 'migrated';
    case SchemaVerified = 'schema_verified';
    case HealthChecked = 'health_checked';
    case Done = 'done';
    case RollingBack = 'rolling_back';
    case RolledBack = 'rolled_back';
    case Failed = 'failed';
}
