<?php

namespace App\Actions\Repositories;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Str;

class CreateRepository
{
    /**
     * @param  array{description: string|null, name: string, slug: string, status: string, visibility: string}  $attributes
     */
    public function __invoke(User $owner, array $attributes): Repository
    {
        $repository = new Repository([
            ...$attributes,
            'normalized_name' => Str::lower(Str::squish($attributes['name'])),
        ]);

        $owner->ownedRepositories()->save($repository);

        return $repository;
    }
}
