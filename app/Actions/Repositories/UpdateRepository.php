<?php

namespace App\Actions\Repositories;

use App\Domain\Repositories\VisibilityCeiling;
use App\Models\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateRepository
{
    /**
     * @param  array{description: string|null, name: string, slug: string, status: string, visibility: string}  $attributes
     */
    public function __invoke(Repository $repository, array $attributes): Repository
    {
        return DB::transaction(function () use ($repository, $attributes): Repository {
            $repository->fill([
                ...$attributes,
                'normalized_name' => Str::lower(Str::squish($attributes['name'])),
            ]);
            $repository->save();

            foreach ($repository->releases()->get() as $release) {
                if (! VisibilityCeiling::allows($repository->visibility, $release->visibility)) {
                    $release->update(['visibility' => $repository->visibility]);
                }
            }

            return $repository;
        });
    }
}
