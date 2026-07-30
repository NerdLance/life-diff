<?php

namespace App\Actions\Repositories;

use App\Models\Repository;

class DeleteRepository
{
    public function __invoke(Repository $repository): void
    {
        $repository->delete();
    }
}
