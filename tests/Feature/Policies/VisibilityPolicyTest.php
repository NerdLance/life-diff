<?php

use App\Enums\ReleaseState;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;

function inspectAs(?User $viewer, string $ability, mixed $arguments): Response
{
    return app(Gate::class)->forUser($viewer)->inspect($ability, $arguments);
}

test('registered user policy permits public profile viewing and self updates only', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    expect(inspectAs(null, 'view', $user)->allowed())->toBeTrue()
        ->and(inspectAs($otherUser, 'view', $user)->allowed())->toBeTrue()
        ->and(inspectAs($user, 'update', $user)->allowed())->toBeTrue()
        ->and(inspectAs($otherUser, 'update', $user)->denied())->toBeTrue();
});

test('repository views enforce owner and direct-link visibility for all viewer types', function (
    RepositoryVisibility $visibility,
    ?string $viewerType,
    bool $allowed,
): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create(['visibility' => $visibility]);
    $viewer = match ($viewerType) {
        'owner' => $owner,
        'verified' => User::factory()->create(),
        'unverified' => User::factory()->unverified()->create(),
        null => null,
    };

    $response = inspectAs($viewer, 'view', $repository);

    expect($response->allowed())->toBe($allowed);

    if (! $allowed) {
        expect($response->status())->toBe(404);
    }
})->with([
    'private owner' => [RepositoryVisibility::Private, 'owner', true],
    'private verified user' => [RepositoryVisibility::Private, 'verified', false],
    'private unverified user' => [RepositoryVisibility::Private, 'unverified', false],
    'private guest' => [RepositoryVisibility::Private, null, false],
    'unlisted owner' => [RepositoryVisibility::Unlisted, 'owner', true],
    'unlisted verified user' => [RepositoryVisibility::Unlisted, 'verified', true],
    'unlisted unverified user' => [RepositoryVisibility::Unlisted, 'unverified', true],
    'unlisted guest' => [RepositoryVisibility::Unlisted, null, true],
    'public owner' => [RepositoryVisibility::Public, 'owner', true],
    'public verified user' => [RepositoryVisibility::Public, 'verified', true],
    'public unverified user' => [RepositoryVisibility::Public, 'unverified', true],
    'public guest' => [RepositoryVisibility::Public, null, true],
]);

test('repository write abilities enforce ownership and archived state', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $active = Repository::factory()->for($owner, 'owner')->create();
    $archived = Repository::factory()->archived()->for($owner, 'owner')->create();

    expect(inspectAs($owner, 'create', Repository::class)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'update', $active)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'archive', $active)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'delete', $active)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'restore', $archived)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'update', $archived)->denied())->toBeTrue()
        ->and(inspectAs($owner, 'archive', $archived)->denied())->toBeTrue()
        ->and(inspectAs($otherUser, 'update', $active)->denied())->toBeTrue()
        ->and(inspectAs($otherUser, 'restore', $archived)->denied())->toBeTrue();
});

test('release views enforce publication, visibility ceilings, and repository access', function (
    RepositoryVisibility $repositoryVisibility,
    RepositoryVisibility $releaseVisibility,
    ReleaseState $state,
    ?string $viewerType,
    bool $allowed,
): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create(['visibility' => $repositoryVisibility]);
    $release = Release::factory()->for($repository)->create([
        'visibility' => $releaseVisibility,
        'state' => $state,
        'published_at' => $state === ReleaseState::Published ? now() : null,
    ]);
    $viewer = match ($viewerType) {
        'owner' => $owner,
        'verified' => User::factory()->create(),
        'unverified' => User::factory()->unverified()->create(),
        null => null,
    };

    $response = inspectAs($viewer, 'view', $release);

    expect($response->allowed())->toBe($allowed);

    if (! $allowed) {
        expect($response->status())->toBe(404);
    }
})->with([
    'owner sees private draft' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Draft, 'owner', true],
    'verified user cannot see private draft' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Draft, 'verified', false],
    'unverified user cannot see private draft' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Draft, 'unverified', false],
    'guest cannot see private draft' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Draft, null, false],
    'owner sees draft with public future visibility' => [RepositoryVisibility::Public, RepositoryVisibility::Public, ReleaseState::Draft, 'owner', true],
    'guest cannot see draft with public future visibility' => [RepositoryVisibility::Public, RepositoryVisibility::Public, ReleaseState::Draft, null, false],
    'owner sees private published release' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Published, 'owner', true],
    'guest cannot see private published release' => [RepositoryVisibility::Private, RepositoryVisibility::Private, ReleaseState::Published, null, false],
    'guest sees unlisted release through unlisted repository' => [RepositoryVisibility::Unlisted, RepositoryVisibility::Unlisted, ReleaseState::Published, null, true],
    'verified user sees unlisted release through public repository' => [RepositoryVisibility::Public, RepositoryVisibility::Unlisted, ReleaseState::Published, 'verified', true],
    'unverified user sees public release through public repository' => [RepositoryVisibility::Public, RepositoryVisibility::Public, ReleaseState::Published, 'unverified', true],
    'guest sees public release through public repository' => [RepositoryVisibility::Public, RepositoryVisibility::Public, ReleaseState::Published, null, true],
    'guest cannot see public release exceeding unlisted repository ceiling' => [RepositoryVisibility::Unlisted, RepositoryVisibility::Public, ReleaseState::Published, null, false],
    'guest cannot see unlisted release exceeding private repository ceiling' => [RepositoryVisibility::Private, RepositoryVisibility::Unlisted, ReleaseState::Published, null, false],
]);

test('release write abilities enforce ownership repository state and visibility ceilings', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $repository = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    $draft = Release::factory()->draft()->for($repository)->create(['visibility' => RepositoryVisibility::Unlisted]);
    $tooPublic = Release::factory()->draft()->for($repository)->create(['visibility' => RepositoryVisibility::Public]);
    $archivedRepository = Repository::factory()->archived()->for($owner, 'owner')->create();
    $archivedRelease = Release::factory()->draft()->for($archivedRepository)->create();

    expect(inspectAs($owner, 'create', [Release::class, $repository])->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'update', $draft)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'publish', $draft)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'delete', $draft)->allowed())->toBeTrue()
        ->and(inspectAs($owner, 'publish', $tooPublic)->denied())->toBeTrue()
        ->and(inspectAs($otherUser, 'update', $draft)->denied())->toBeTrue()
        ->and(inspectAs($owner, 'create', [Release::class, $archivedRepository])->denied())->toBeTrue()
        ->and(inspectAs($owner, 'update', $archivedRelease)->denied())->toBeTrue();
});

test('soft deleted resources deny public views and do not resolve through visibility queries', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $release = Release::factory()->public()->for($repository)->create();

    $repository->delete();
    $release->delete();

    expect(inspectAs(null, 'view', $repository)->status())->toBe(404)
        ->and(inspectAs(null, 'view', $release)->status())->toBe(404)
        ->and(Repository::query()->visibleTo(null)->find($repository->id))->toBeNull()
        ->and(Release::query()->visibleTo(null)->find($release->id))->toBeNull();
});

test('public listing queries exclude unlisted private draft and archived content', function (): void {
    $owner = User::factory()->create();
    $publicRepository = Repository::factory()->public()->for($owner, 'owner')->create();
    $unlistedRepository = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    $archivedRepository = Repository::factory()->public()->archived()->for($owner, 'owner')->create();
    $publicRelease = Release::factory()->public()->for($publicRepository)->create();
    $unlistedRelease = Release::factory()->unlisted()->for($publicRepository)->create();
    $draft = Release::factory()->draft()->for($publicRepository)->create();
    $unlistedRepositoryRelease = Release::factory()->unlisted()->for($unlistedRepository)->create();
    $archivedRepositoryRelease = Release::factory()->public()->for($archivedRepository)->create();

    expect(Repository::query()->publiclyListed()->pluck('id')->all())->toBe([$publicRepository->id])
        ->and(Release::query()->publiclyListed()->pluck('id')->all())->toBe([$publicRelease->id])
        ->and(inspectAs(null, 'view', $archivedRepository)->allowed())->toBeTrue()
        ->and(inspectAs(null, 'view', $archivedRepositoryRelease)->allowed())->toBeTrue()
        ->and(Release::query()->visibleTo(null)->pluck('id')->all())
        ->toContain($publicRelease->id, $unlistedRelease->id, $unlistedRepositoryRelease->id, $archivedRepositoryRelease->id)
        ->not->toContain($draft->id);
});
