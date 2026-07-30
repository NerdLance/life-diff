<?php

namespace App\Enums;

enum ReleaseType: string
{
    case Major = 'major';
    case Minor = 'minor';
    case Patch = 'patch';
    case Hotfix = 'hotfix';
    case Experimental = 'experimental';
    case Rollback = 'rollback';
}
