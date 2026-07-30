<?php

namespace App\Actions\Repositories;

use App\Models\Repository;

class ArchiveRepository
{
    public function __invoke(Repository $repository): void
    {
        $repository->archived_at = now();
        $repository->save();
    }
}
