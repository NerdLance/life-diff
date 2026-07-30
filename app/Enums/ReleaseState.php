<?php

namespace App\Enums;

enum ReleaseState: string
{
    case Draft = 'draft';
    case Published = 'published';
}
