<?php

namespace App\Actions\Releases;

use App\Models\ChangeEntry;
use App\Models\Release;
use Illuminate\Support\Facades\DB;

class SynchronizeChangeEntries
{
    /**
     * @param  list<array{change_type: string, content: string, id?: int}>  $changeEntries
     */
    public function __invoke(Release $release, array $changeEntries): void
    {
        $submittedIds = collect($changeEntries)->pluck('id')->filter()->all();

        $release->changeEntries()
            ->when($submittedIds === [], fn ($query) => $query->delete(), fn ($query) => $query->whereNotIn('id', $submittedIds)->delete());

        // Existing rows move out of the unique contiguous range before their new order is saved.
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
