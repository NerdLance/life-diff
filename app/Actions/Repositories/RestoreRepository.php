<?php

namespace App\Actions\Repositories;

use App\Models\Repository;

class RestoreRepository
{
    public function __invoke(Repository $repository): void
    {
        $repository->archived_at = null;
        $repository->save();
    }
}
