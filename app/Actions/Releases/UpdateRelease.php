<?php

namespace App\Actions\Releases;

use App\Models\ChangeEntry;
use App\Models\Release;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateRelease
{
    /**
     * @param  array{body: string|null, change_entries: list<array{change_type: string, content: string, id?: int}>, release_type: string, title: string, version: string, visibility: string}  $attributes
     */
    public function __invoke(Release $release, array $attributes): Release
    {
        return DB::transaction(function () use ($release, $attributes): Release {
            $release->fill(Arr::except($attributes, 'change_entries'));
            $release->save();

            $this->synchronizeChangeEntries($release, $attributes['change_entries']);

            return $release;
        });
    }

    /**
     * @param  list<array{change_type: string, content: string, id?: int}>  $changeEntries
     */
    private function synchronizeChangeEntries(Release $release, array $changeEntries): void
    {
        $submittedIds = collect($changeEntries)->pluck('id')->filter()->all();

        $release->changeEntries()
            ->when($submittedIds === [], fn ($query) => $query->delete(), fn ($query) => $query->whereNotIn('id', $submittedIds)->delete());

        // Move existing rows out of the contiguous range before applying a new order.
        $release->changeEntries()->update([
            'sort_order' => DB::raw('sort_order + 100'),
        ]);

        foreach ($changeEntries as $sortOrder => $changeEntry) {
            $changeEntryModel = isset($changeEntry['id'])
                ? $release->changeEntries()->findOrFail($changeEntry['id'])
                : new ChangeEntry;

            $changeEntryModel->fill([
                'change_type' => $changeEntry['change_type'],
                'content' => $changeEntry['content'],
                'sort_order' => $sortOrder,
            ]);

            $release->changeEntries()->save($changeEntryModel);
        }
    }
}
