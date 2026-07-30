<?php

namespace App\Actions\Releases;

use App\Enums\ReleaseState;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateReleaseDraft
{
    public function __construct(private SynchronizeChangeEntries $synchronizeChangeEntries) {}

    /**
     * @param  array{body: string|null, change_entries: list<array{change_type: string, content: string}>, release_type: string, title: string, version: string, visibility: string}  $attributes
     */
    public function __invoke(Repository $repository, array $attributes): Release
    {
        return DB::transaction(function () use ($repository, $attributes): Release {
            $release = $repository->releases()->create([
                ...Arr::except($attributes, 'change_entries'),
                'state' => ReleaseState::Draft,
                'published_at' => null,
            ]);

            ($this->synchronizeChangeEntries)($release, $attributes['change_entries']);

            return $release;
        });
    }
}
