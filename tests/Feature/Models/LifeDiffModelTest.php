<?php

use App\Enums\ChangeType;
use App\Enums\ProfileStatus;
use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\ChangeEntry;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Str;
use LogicException;

test('lifediff model relationships are correctly connected', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->for($repository)->create();
    $lateEntry = ChangeEntry::factory()->for($release)->create(['sort_order' => 1]);
    $firstEntry = ChangeEntry::factory()->for($release)->create(['sort_order' => 0]);

    expect($owner->ownedRepositories)->toHaveCount(1)
        ->and($repository->owner->is($owner))->toBeTrue()
        ->and($repository->releases)->toHaveCount(1)
        ->and($release->repository->is($repository))->toBeTrue()
        ->and($release->changeEntries->pluck('id')->all())->toBe([$firstEntry->id, $lateEntry->id])
        ->and($firstEntry->release->is($release))->toBeTrue();
});

test('lifediff models cast domain enums and dates', function (): void {
    $user = User::factory()->create(['status' => ProfileStatus::Experimental]);
    $repository = Repository::factory()->archived()->create([
        'status' => ProfileStatus::MaintenanceMode,
        'visibility' => RepositoryVisibility::Unlisted,
    ]);
    $release = Release::factory()->published()->create([
        'release_type' => ReleaseType::Hotfix,
        'visibility' => RepositoryVisibility::Public,
        'edited_at' => now(),
    ]);
    $entry = ChangeEntry::factory()->create(['change_type' => ChangeType::KnownIssue]);

    expect($user->status)->toBe(ProfileStatus::Experimental)
        ->and($repository->status)->toBe(ProfileStatus::MaintenanceMode)
        ->and($repository->visibility)->toBe(RepositoryVisibility::Unlisted)
        ->and($repository->archived_at)->not->toBeNull()
        ->and($release->release_type)->toBe(ReleaseType::Hotfix)
        ->and($release->state)->toBe(ReleaseState::Published)
        ->and($release->visibility)->toBe(RepositoryVisibility::Public)
        ->and($release->published_at)->not->toBeNull()
        ->and($release->edited_at)->not->toBeNull()
        ->and($entry->change_type)->toBe(ChangeType::KnownIssue);
});

test('repositories and releases generate immutable ulid public ids', function (): void {
    $repository = Repository::factory()->create();
    $release = Release::factory()->create();

    expect(Str::isUlid($repository->public_id))->toBeTrue()
        ->and(Str::isUlid($release->public_id))->toBeTrue();

    $repository->public_id = (string) Str::ulid();
    $release->public_id = (string) Str::ulid();

    expect(fn () => $repository->save())->toThrow(LogicException::class)
        ->and(fn () => $release->save())->toThrow(LogicException::class);
});

test('repositories and releases bind routes by public id', function (): void {
    expect((new Repository)->getRouteKeyName())->toBe('public_id')
        ->and((new Release)->getRouteKeyName())->toBe('public_id');
});

test('repository scopes filter active ownership public listing and visibility', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $private = Repository::factory()->private()->for($owner, 'owner')->create();
    $unlisted = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    $public = Repository::factory()->public()->for($owner, 'owner')->create();
    $archivedPublic = Repository::factory()->public()->archived()->for($owner, 'owner')->create();
    $otherPrivate = Repository::factory()->private()->for($otherUser, 'owner')->create();

    expect(Repository::query()->active()->pluck('id')->all())
        ->toContain($private->id, $unlisted->id, $public->id, $otherPrivate->id)
        ->not->toContain($archivedPublic->id)
        ->and(Repository::query()->ownedBy($owner)->pluck('id')->all())
        ->toContain($private->id, $unlisted->id, $public->id, $archivedPublic->id)
        ->not->toContain($otherPrivate->id)
        ->and(Repository::query()->publiclyListed()->pluck('id')->all())
        ->toBe([$public->id])
        ->and(Repository::query()->visibleTo(null)->pluck('id')->all())
        ->toContain($unlisted->id, $public->id, $archivedPublic->id)
        ->not->toContain($private->id, $otherPrivate->id)
        ->and(Repository::query()->visibleTo($owner)->pluck('id')->all())
        ->toContain($private->id, $unlisted->id, $public->id, $archivedPublic->id)
        ->not->toContain($otherPrivate->id);
});

test('release scopes filter states visibility and chronology', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownerRepository = Repository::factory()->private()->for($owner, 'owner')->create();
    $publicRepository = Repository::factory()->public()->for($otherUser, 'owner')->create();
    $draft = Release::factory()->draft()->for($ownerRepository)->create();
    $private = Release::factory()->published()->private()->for($ownerRepository)->create();
    $olderPublic = Release::factory()->public()->for($publicRepository)->create([
        'published_at' => now()->subDay(),
    ]);
    $newerPublic = Release::factory()->public()->for($publicRepository)->create([
        'published_at' => now(),
    ]);
    $unlisted = Release::factory()->unlisted()->for($publicRepository)->create([
        'published_at' => now()->subDays(2),
    ]);

    expect(Release::query()->drafts()->pluck('id')->all())->toBe([$draft->id])
        ->and(Release::query()->published()->pluck('id')->all())
        ->toContain($private->id, $olderPublic->id, $newerPublic->id, $unlisted->id)
        ->and(Release::query()->visibleTo(null)->pluck('id')->all())
        ->toContain($olderPublic->id, $newerPublic->id, $unlisted->id)
        ->not->toContain($draft->id, $private->id)
        ->and(Release::query()->visibleTo($owner)->pluck('id')->all())
        ->toContain($draft->id, $private->id, $olderPublic->id, $newerPublic->id, $unlisted->id)
        ->and(Release::query()->published()->chronological()->first()?->id)->toBe($newerPublic->id);
});

test('factory states maintain valid visibility and publication defaults', function (): void {
    $privateRepository = Repository::factory()->private()->create();
    $publicRepository = Repository::factory()->public()->create();
    $unlistedRepository = Repository::factory()->unlisted()->create();
    $archivedRepository = Repository::factory()->archived()->create();
    $draft = Release::factory()->draft()->create();
    $published = Release::factory()->published()->create();
    $private = Release::factory()->private()->create();
    $public = Release::factory()->public()->create();
    $unlisted = Release::factory()->unlisted()->create();

    expect($privateRepository->visibility)->toBe(RepositoryVisibility::Private)
        ->and($publicRepository->visibility)->toBe(RepositoryVisibility::Public)
        ->and($unlistedRepository->visibility)->toBe(RepositoryVisibility::Unlisted)
        ->and($archivedRepository->isArchived())->toBeTrue()
        ->and($draft->isDraft())->toBeTrue()
        ->and($draft->published_at)->toBeNull()
        ->and($published->isPublished())->toBeTrue()
        ->and($private->visibility)->toBe(RepositoryVisibility::Private)
        ->and($public->visibility)->toBe(RepositoryVisibility::Public)
        ->and($public->isPublished())->toBeTrue()
        ->and($public->repository->visibility)->toBe(RepositoryVisibility::Public)
        ->and($unlisted->visibility)->toBe(RepositoryVisibility::Unlisted)
        ->and($unlisted->isPublished())->toBeTrue()
        ->and($unlisted->repository->visibility)->toBe(RepositoryVisibility::Unlisted);
});

test('soft deleted repositories and releases are excluded from default model queries', function (): void {
    $repository = Repository::factory()->create();
    $release = Release::factory()->create();

    $repository->delete();
    $release->delete();

    expect(Repository::find($repository->id))->toBeNull()
        ->and(Repository::withTrashed()->find($repository->id)?->trashed())->toBeTrue()
        ->and(Release::find($release->id))->toBeNull()
        ->and(Release::withTrashed()->find($release->id)?->trashed())->toBeTrue();
});
