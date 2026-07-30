<?php

namespace App\Enums;

enum ProfileStatus: string
{
    case Stable = 'stable';
    case Experimental = 'experimental';
    case ActiveDevelopment = 'active_development';
    case MaintenanceMode = 'maintenance_mode';
    case BreakingChangesExpected = 'breaking_changes_expected';
    case LongTermSupport = 'long_term_support';
    case NeedsHotfix = 'needs_hotfix';
}
