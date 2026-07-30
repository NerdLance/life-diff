<?php

use App\Enums\ChangeType;
use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('the local development seed provides complete fictional Phase 1 scenarios', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->whereIn('handle', ['mara-chen', 'jonah-rivera'])->count())->toBe(2)
        ->and(Repository::query()->count())->toBe(4)
        ->and(Repository::query()->pluck('visibility')->all())
        ->toEqualCanonicalizing([
            RepositoryVisibility::Private,
            RepositoryVisibility::Unlisted,
            RepositoryVisibility::Public,
            RepositoryVisibility::Public,
        ])
        ->and(Release::query()->drafts()->count())->toBe(1)
        ->and(Release::query()->published()->count())->toBe(6)
        ->and(Release::query()->published()->pluck('release_type')->all())
        ->toContain(
            ReleaseType::Major,
            ReleaseType::Minor,
            ReleaseType::Patch,
            ReleaseType::Hotfix,
            ReleaseType::Experimental,
            ReleaseType::Rollback,
        )
        ->and(Release::query()->where('state', ReleaseState::Published)->with('changeEntries')->get()->flatMap->changeEntries->pluck('change_type')->all())
        ->toContain(
            ChangeType::Added,
            ChangeType::Improved,
            ChangeType::Fixed,
            ChangeType::Removed,
            ChangeType::Deprecated,
            ChangeType::KnownIssue,
        );
});
