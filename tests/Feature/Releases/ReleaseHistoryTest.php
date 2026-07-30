<?php

use App\Enums\ChangeType;
use App\Enums\RepositoryVisibility;
use App\Models\ChangeEntry;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('stable public release links enforce the complete visibility matrix', function (): void {
    $owner = User::factory()->create();

    $publicRepository = Repository::factory()->public()->for($owner, 'owner')->create();
    $publicRelease = Release::factory()->published()->for($publicRepository)->create(['visibility' => RepositoryVisibility::Public]);
    $unlistedRelease = Release::factory()->published()->for($publicRepository)->create(['visibility' => RepositoryVisibility::Unlisted]);
    $draft = Release::factory()->draft()->for($publicRepository)->create(['visibility' => RepositoryVisibility::Public]);

    $unlistedRepository = Repository::factory()->unlisted()->for($owner, 'owner')->create();
    $unlistedRepositoryRelease = Release::factory()->published()->for($unlistedRepository)->create(['visibility' => RepositoryVisibility::Unlisted]);

    $privateRepository = Repository::factory()->for($owner, 'owner')->create();
    $privateRelease = Release::factory()->published()->for($privateRepository)->create(['visibility' => RepositoryVisibility::Private]);

    $this->get(route('public.releases.show', $publicRelease))->assertOk();
    $this->get(route('public.releases.show', $unlistedRelease))->assertOk();
    $this->get(route('public.releases.show', $unlistedRepositoryRelease))->assertOk();
    $this->get(route('public.releases.show', $draft))->assertNotFound();
    $this->get(route('public.releases.show', $privateRelease))->assertNotFound();

    $publicRelease->delete();

    $this->get(route('public.releases.show', $publicRelease->public_id))->assertNotFound();
});

test('public release detail has only safe props and preserves ordered plain text entries', function (): void {
    $owner = User::factory()->create(['handle' => 'octavia']);
    $repository = Repository::factory()->public()->for($owner, 'owner')->create(['slug' => 'habits']);
    $release = Release::factory()->published()->for($repository)->create([
        'visibility' => RepositoryVisibility::Public,
        'body' => "First line\nSecond line",
        'edited_at' => now(),
    ]);
    $first = ChangeEntry::factory()->for($release)->create(['sort_order' => 0, 'change_type' => ChangeType::Added, 'content' => 'First entry']);
    $second = ChangeEntry::factory()->for($release)->create(['sort_order' => 1, 'change_type' => ChangeType::KnownIssue, 'content' => 'Second entry']);

    $this->actingAs(User::factory()->create())
        ->get(route('public.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('releases/public-show')
            ->where('profile.handle', 'octavia')
            ->where('repository.name', $repository->name)
            ->where('release.public_id', $release->public_id)
            ->where('release.body', "First line\nSecond line")
            ->where('release.edited_at', $release->edited_at?->toIso8601String())
            ->where('release.change_entries.0.content', $first->content)
            ->where('release.change_entries.1.content', $second->content)
            ->where('copyLink', route('public.releases.show', $release))
            ->missing('release.visibility')
            ->missing('release.state')
            ->missing('repository.visibility')
            ->missing('repository.public_id')
            ->missing('profile.email')
            ->missing('actions')
            ->where('auth.user', null),
        );
});

test('stable release links survive profile handle and repository slug changes', function (): void {
    $owner = User::factory()->create(['handle' => 'before']);
    $repository = Repository::factory()->public()->for($owner, 'owner')->create(['slug' => 'before-slug']);
    $release = Release::factory()->public()->for($repository)->create();
    $stableUrl = route('public.releases.show', $release);

    $owner->forceFill(['handle' => 'after'])->save();
    $repository->forceFill(['slug' => 'after-slug'])->save();

    $this->get($stableUrl)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('release.public_id', $release->public_id)
            ->where('profile.handle', 'after')
            ->where('repository.slug', 'after-slug'),
        );
});

test('repository timelines paginate after twenty publications without exposing owner drafts publicly', function (): void {
    $owner = User::factory()->create(['handle' => 'octavia']);
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $draft = Release::factory()->draft()->for($repository)->create(['title' => 'Private continuation']);

    Release::factory()->count(21)->published()->for($repository)->sequence(
        fn ($sequence) => [
            'visibility' => RepositoryVisibility::Public,
            'published_at' => now()->subMinutes($sequence->index),
        ],
    )->create();
    Release::factory()->unlisted()->for($repository)->create();

    $this->actingAs($owner)
        ->get(route('repositories.show', $repository))
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/show')
            ->has('drafts', 1)
            ->where('drafts.0.public_id', $draft->public_id)
            ->has('publishedReleases.data', 20)
            ->where('publishedReleases.current_page', 1)
            ->where('publishedReleases.last_page', 2)
            ->has('publishedReleases.data.0.change_summary')
            ->missing('publishedReleases.data.0.body'),
        );

    $this->get(route('public.repositories.show', [$owner, $repository]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('repositories/public-show')
            ->has('publishedReleases.data', 20)
            ->where('publishedReleases.last_page', 2)
            ->missing('drafts')
            ->missing('publishedReleases.data.0.body')
            ->missing('publishedReleases.data.0.visibility'),
        );
});

test('authenticated release detail is owner-aware and never gives private release data to another user', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->published()->for($repository)->create(['visibility' => RepositoryVisibility::Private]);

    $this->actingAs($owner)
        ->get(route('releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('releases/show')
            ->where('actions.isOwner', true)
            ->where('release.visibility', RepositoryVisibility::Private->value)
            ->where('copyLink', null),
        );

    $this->actingAs($otherUser)
        ->get(route('releases.show', $release))
        ->assertNotFound();
});
