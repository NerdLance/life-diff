<?php

use App\Enums\ReleaseState;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard renders an intentional empty journal state', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('repositories', [])
            ->where('drafts', [])
            ->where('recentPublishedReleases', []),
        );
});

test('dashboard includes only the owners active repositories drafts and recent releases', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $activeRepository = Repository::factory()->for($owner, 'owner')->create();
    $archivedRepository = Repository::factory()->archived()->for($owner, 'owner')->create();
    Release::factory()->draft()->for($activeRepository)->create();
    Release::factory()->published()->for($activeRepository)->create(['published_at' => now()]);
    Release::factory()->draft()->for($archivedRepository)->create();
    Release::factory()->draft()->for(Repository::factory()->for($otherUser, 'owner'))->create();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('repositories', 1)
            ->where('repositories.0.public_id', $activeRepository->public_id)
            ->has('drafts', 2)
            ->has('recentPublishedReleases', 1),
        );
});

test('owner repository pages provide essential props and authorized action flags', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    Release::factory()->draft()->for($repository)->create();
    Release::factory()->published()->for($repository)->create(['published_at' => now()]);

    $this->actingAs($owner)
        ->get(route('repositories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/index')
            ->has('activeRepositories', 1)
            ->where('activeRepositories.0.public_id', $repository->public_id)
            ->has('archivedRepositories', 0),
        );

    $this->actingAs($owner)
        ->get(route('repositories.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/create')
            ->where('repository.visibility', RepositoryVisibility::Private->value)
            ->where('repository.status', 'stable'),
        );

    $this->actingAs($owner)
        ->get(route('repositories.show', $repository))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/show')
            ->where('repository.public_id', $repository->public_id)
            ->has('drafts', 1)
            ->has('publishedReleases', 1)
            ->where('actions.canUpdate', true)
            ->where('actions.canArchive', true)
            ->where('actions.canRestore', false)
            ->where('actions.canDelete', true)
            ->where('actions.canCreateRelease', true),
        );
});

test('public profile exposes only public active repositories and public published releases', function (): void {
    $owner = User::factory()->create(['handle' => 'octavia']);
    $publicRepository = Repository::factory()->public()->for($owner, 'owner')->create();
    $unlistedRepository = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    Repository::factory()->for($owner, 'owner')->create();
    Repository::factory()->public()->archived()->for($owner, 'owner')->create();
    Release::factory()->published()->for($publicRepository)->create([
        'visibility' => RepositoryVisibility::Public,
        'published_at' => now(),
    ]);
    Release::factory()->published()->for($publicRepository)->create([
        'visibility' => RepositoryVisibility::Unlisted,
        'published_at' => now(),
    ]);
    Release::factory()->draft()->for($publicRepository)->create([
        'visibility' => RepositoryVisibility::Public,
    ]);
    Release::factory()->published()->for($unlistedRepository)->create([
        'visibility' => RepositoryVisibility::Unlisted,
        'published_at' => now(),
    ]);

    $this->get(route('profiles.show', $owner))
        ->assertInertia(fn (Assert $page) => $page
            ->component('profiles/show')
            ->where('profile.handle', 'octavia')
            ->has('repositories', 1)
            ->where('repositories.0.slug', $publicRepository->slug)
            ->has('recentPublishedReleases', 1)
            ->missing('profile.email')
            ->missing('repositories.0.visibility')
            ->missing('repositories.0.release_count')
            ->missing('recentPublishedReleases.0.body'),
        );
});

test('public and unlisted repositories resolve by their scoped direct routes without private timeline data', function (): void {
    $owner = User::factory()->create(['handle' => 'octavia']);
    $publicRepository = Repository::factory()->public()->for($owner, 'owner')->create();
    $unlistedRepository = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    Release::factory()->published()->for($publicRepository)->create([
        'visibility' => RepositoryVisibility::Public,
        'published_at' => now(),
    ]);
    Release::factory()->published()->for($publicRepository)->create([
        'visibility' => RepositoryVisibility::Unlisted,
        'published_at' => now(),
    ]);
    Release::factory()->create([
        'repository_id' => $publicRepository->id,
        'state' => ReleaseState::Draft,
        'visibility' => RepositoryVisibility::Private,
        'published_at' => null,
    ]);

    $this->get(route('public.repositories.show', [$owner, $publicRepository]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/public-show')
            ->where('profile.handle', 'octavia')
            ->where('repository.slug', $publicRepository->slug)
            ->has('publishedReleases', 1)
            ->missing('repository.public_id')
            ->missing('repository.release_count')
            ->missing('drafts')
            ->missing('actions')
            ->missing('publishedReleases.0.visibility'),
        );

    $this->get(route('public.repositories.show', [$owner, $unlistedRepository]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/public-show')
            ->where('repository.slug', $unlistedRepository->slug)
            ->where('publishedReleases', []),
        );
});

test('private repositories remain absent from public profile and direct routes', function (): void {
    $owner = User::factory()->create(['handle' => 'octavia']);
    $privateRepository = Repository::factory()->for($owner, 'owner')->create();

    $this->get(route('profiles.show', $owner))
        ->assertInertia(fn (Assert $page) => $page
            ->component('profiles/show')
            ->where('repositories', []),
        );

    $this->get(route('public.repositories.show', [$owner, $privateRepository]))
        ->assertNotFound();
});
