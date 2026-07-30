<?php

namespace App\Enums;

enum RepositoryVisibility: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';
}
