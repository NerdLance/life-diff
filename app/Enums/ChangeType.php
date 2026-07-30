<?php

namespace App\Enums;

enum ChangeType: string
{
    case Added = 'added';
    case Improved = 'improved';
    case Fixed = 'fixed';
    case Removed = 'removed';
    case Deprecated = 'deprecated';
    case KnownIssue = 'known_issue';
}
