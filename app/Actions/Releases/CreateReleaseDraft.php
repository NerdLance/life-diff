<?php

namespace App\Actions\Releases;

use App\Enums\ReleaseState;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateReleaseDraft
{
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

            $this->synchronizeChangeEntries($release, $attributes['change_entries']);

            return $release;
        });
    }

    /**
     * @param  list<array{change_type: string, content: string}>  $changeEntries
     */
    private function synchronizeChangeEntries(Release $release, array $changeEntries): void
    {
        foreach ($changeEntries as $sortOrder => $changeEntry) {
            $release->changeEntries()->create([
                ...$changeEntry,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
