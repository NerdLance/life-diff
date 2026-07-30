<?php

namespace App\Actions\Releases;

use App\Models\Release;

class DeleteRelease
{
    public function __invoke(Release $release): void
    {
        $release->delete();
    }
}
