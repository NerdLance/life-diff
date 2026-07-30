<?php

namespace App\Actions\Releases;

use App\Domain\Repositories\VisibilityCeiling;
use App\Enums\ReleaseState;
use App\Models\Release;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class PublishRelease
{
    public function __construct(private SynchronizeChangeEntries $synchronizeChangeEntries) {}

    /**
     * @param  array{body: string|null, change_entries: list<array{change_type: string, content: string, id?: int}>, release_type: string, title: string, version: string, visibility: string}  $attributes
     */
    public function __invoke(Release $release, array $attributes): Release
    {
        return DB::transaction(function () use ($release, $attributes): Release {
            $release = Release::query()->whereKey($release->getKey())->lockForUpdate()->sole();
            $repository = $release->repository;

            if (! $release->isDraft() || $release->published_at !== null) {
                throw new LogicException('Only an unpublished draft can be published.');
            }

            if ($repository === null || $repository->trashed() || ! $repository->isActive()) {
                throw ValidationException::withMessages([
                    'release' => 'This release cannot be published from an archived or deleted repository.',
                ]);
            }

            $release->fill(Arr::except($attributes, 'change_entries'));

            if ($attributes['change_entries'] === []) {
                throw ValidationException::withMessages([
                    'change_entries' => 'A published release needs at least one change entry.',
                ]);
            }

            if (! VisibilityCeiling::allows($repository->visibility, $release->visibility)) {
                throw ValidationException::withMessages([
                    'visibility' => 'The release visibility cannot exceed the repository visibility.',
                ]);
            }

            if (Release::withTrashed()
                ->whereBelongsTo($repository)
                ->where('version', $release->version)
                ->whereKeyNot($release->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'version' => 'The version has already been used in this repository.',
                ]);
            }

            ($this->synchronizeChangeEntries)($release, $attributes['change_entries']);

            $release->state = ReleaseState::Published;
            $release->published_at = now();
            $release->edited_at = null;
            $release->save();

            return $release;
        });
    }
}
