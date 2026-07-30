<?php

use App\Actions\Releases\PublishRelease;
use App\Actions\Releases\SuggestReleaseVersion;
use App\Enums\ChangeType;
use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\ChangeEntry;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function releaseDraftPayload(array $overrides = []): array
{
    return [
        'title' => 'A meaningful change',
        'version' => '0.1.0',
        'release_type' => ReleaseType::Patch->value,
        'body' => 'A private draft body.',
        'visibility' => RepositoryVisibility::Private->value,
        'change_entries' => [],
        ...$overrides,
    ];
}

test('first release and every release type receive the contract version suggestion', function (): void {
    $repository = Repository::factory()->create();
    $suggest = app(SuggestReleaseVersion::class);

    expect($suggest($repository, ReleaseType::Patch))->toBe('0.1.0');

    Release::factory()->published()->for($repository)->create([
        'version' => '2.4.7',
        'published_at' => now(),
    ]);
    Release::factory()->draft()->for($repository)->create(['version' => '9.9.9']);

    expect($suggest($repository, ReleaseType::Major))->toBe('3.0.0')
        ->and($suggest($repository, ReleaseType::Minor))->toBe('2.5.0')
        ->and($suggest($repository, ReleaseType::Patch))->toBe('2.4.8')
        ->and($suggest($repository, ReleaseType::Hotfix))->toBe('2.4.8')
        ->and($suggest($repository, ReleaseType::Experimental))->toBe('2.4.8')
        ->and($suggest($repository, ReleaseType::Rollback))->toBe('2.4.8');

    $this->actingAs($repository->owner)
        ->get(route('repositories.releases.create', [
            'repository' => $repository,
            'release_type' => ReleaseType::Major->value,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('releases/create')
            ->where('suggestedVersion', '3.0.0'),
        );
});

test('the draft create route supplies a server generated version suggestion', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('repositories.releases.create', $repository))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('releases/create')
            ->where('repository.public_id', $repository->public_id)
            ->where('suggestedVersion', '0.1.0')
            ->where('release.visibility', RepositoryVisibility::Private->value)
            ->has('release.change_entries', 1),
        );
});

test('owners receive an editable composer with persisted entry identity and a suggestion', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create(['version' => '2.3.4']);
    $entry = ChangeEntry::factory()->for($release)->create(['sort_order' => 0]);

    $this->actingAs($owner)
        ->get(route('releases.edit', $release))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('releases/edit')
            ->where('repository.public_id', $repository->public_id)
            ->where('release.public_id', $release->public_id)
            ->where('release.version', '2.3.4')
            ->where('release.change_entries.0.id', $entry->id)
            ->where('release.change_entries.0.client_id', 'entry-'.$entry->id)
            ->where('suggestedVersion', '0.1.0'),
        );
});

test('owners can create a draft with a manual normalized version and no entries', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload([
            'version' => '01.002.0003',
            'visibility' => RepositoryVisibility::Public->value,
        ]))
        ->assertRedirect(route('repositories.show', $repository));

    $release = Release::query()->sole();

    expect($release->version)->toBe('1.2.3')
        ->and($release->state)->toBe(ReleaseState::Draft)
        ->and($release->published_at)->toBeNull()
        ->and($release->visibility)->toBe(RepositoryVisibility::Public)
        ->and($release->changeEntries)->toHaveCount(0);
});

test('draft validation returns to the relevant composer route', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();

    $this->actingAs($owner)
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload(['title' => '']))
        ->assertRedirect(route('repositories.releases.create', $repository))
        ->assertSessionHasErrors('title');

    $this->actingAs($owner)
        ->patch(route('releases.update', $release), releaseDraftPayload([
            'title' => '',
            'version' => $release->version,
        ]))
        ->assertRedirect(route('releases.edit', $release))
        ->assertSessionHasErrors('title');
});

test('drafts synchronize multiple change entries in submitted order and remove empty rows', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload([
            'change_entries' => [
                ['client_id' => 'first', 'change_type' => ChangeType::Added->value, 'content' => 'Started a routine.'],
                ['client_id' => 'empty', 'change_type' => ChangeType::Fixed->value, 'content' => '   '],
                ['client_id' => 'second', 'change_type' => ChangeType::KnownIssue->value, 'content' => 'Still learning what works.'],
            ],
        ]))
        ->assertRedirect();

    $release = Release::query()->sole();
    $entries = $release->changeEntries()->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->pluck('content')->all())->toBe(['Started a routine.', 'Still learning what works.'])
        ->and($entries->pluck('sort_order')->all())->toBe([0, 1]);
});

test('owners can reorder and synchronize draft entries while preserving accepted IDs', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();
    $first = ChangeEntry::factory()->for($release)->create(['sort_order' => 0, 'content' => 'First']);
    $second = ChangeEntry::factory()->for($release)->create(['sort_order' => 1, 'content' => 'Second']);

    $this->actingAs($owner)
        ->patch(route('releases.update', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [
                ['id' => $second->id, 'client_id' => 'second', 'change_type' => ChangeType::Fixed->value, 'content' => 'Second, updated'],
                ['id' => $first->id, 'client_id' => 'first', 'change_type' => ChangeType::Added->value, 'content' => 'First, updated'],
            ],
        ]))
        ->assertRedirect(route('releases.show', $release));

    expect($release->changeEntries()->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($release->changeEntries()->pluck('sort_order')->all())->toBe([0, 1]);
});

test('change entry IDs from another release are rejected without altering the draft', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();
    $foreignEntry = ChangeEntry::factory()->for(Release::factory()->draft()->for($repository))->create();

    $this->actingAs($owner)
        ->from(route('releases.edit', $release))
        ->patch(route('releases.update', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [
                ['id' => $foreignEntry->id, 'client_id' => 'foreign', 'change_type' => ChangeType::Added->value, 'content' => 'Injected'],
            ],
        ]))
        ->assertSessionHasErrors('change_entries.0.id');

    expect($release->refresh()->changeEntries)->toHaveCount(0);
});

test('draft routes reject other users and guests', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();

    $this->get(route('releases.edit', $release))
        ->assertRedirect(route('login'));

    $this->actingAs($otherUser)
        ->get(route('releases.edit', $release))
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->get(route('repositories.releases.create', $repository))
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload())
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->patch(route('releases.update', $release), releaseDraftPayload(['version' => $release->version]))
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->delete(route('releases.destroy', $release), ['confirmation' => $release->title])
        ->assertNotFound();

});

test('archived repositories reject draft creation and updates', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->archived()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();

    $this->actingAs($owner)
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload())
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch(route('releases.update', $release), releaseDraftPayload(['version' => $release->version]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('releases.destroy', $release), ['confirmation' => $release->title])
        ->assertForbidden();
});

test('versions collide only within the same repository', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $otherRepository = Repository::factory()->for($owner, 'owner')->create();
    Release::factory()->draft()->for($repository)->create(['version' => '1.2.3']);

    $this->actingAs($owner)
        ->from(route('repositories.releases.create', $repository))
        ->post(route('repositories.releases.store', $repository), releaseDraftPayload(['version' => '1.2.3']))
        ->assertSessionHasErrors('version');

    $this->actingAs($owner)
        ->post(route('repositories.releases.store', $otherRepository), releaseDraftPayload(['version' => '1.2.3']))
        ->assertRedirect();
});

test('deleting a draft requires its typed title and soft deletes the release', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create(['title' => 'A meaningful change']);

    $this->actingAs($owner)
        ->delete(route('releases.destroy', $release), ['confirmation' => 'Wrong title'])
        ->assertSessionHasErrors('confirmation');

    $this->actingAs($owner)
        ->delete(route('releases.destroy', $release), ['confirmation' => $release->title])
        ->assertRedirect(route('repositories.show', $repository));

    expect(Release::find($release->id))->toBeNull()
        ->and(Release::withTrashed()->find($release->id)?->trashed())->toBeTrue();
});

test('an owner can publish a complete draft atomically', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();

    $this->actingAs($owner)
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'visibility' => RepositoryVisibility::Public->value,
            'change_entries' => [
                ['client_id' => 'first', 'change_type' => ChangeType::Added->value, 'content' => 'Started documenting this change.'],
            ],
        ]))
        ->assertRedirect(route('releases.show', $release));

    $release->refresh();

    expect($release->state)->toBe(ReleaseState::Published)
        ->and($release->published_at)->not->toBeNull()
        ->and($release->edited_at)->toBeNull()
        ->and($release->changeEntries->pluck('sort_order')->all())->toBe([0]);
});

test('publishing rejects incomplete, archived, over-ceiling, and duplicate releases', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create();

    $this->actingAs($owner)
        ->from(route('releases.edit', $release))
        ->post(route('releases.publish', $release), releaseDraftPayload(['version' => $release->version]))
        ->assertSessionHasErrors('change_entries');

    $this->actingAs($owner)
        ->from(route('releases.edit', $release))
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'visibility' => RepositoryVisibility::Public->value,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'Valid entry']],
        ]))
        ->assertSessionHasErrors('visibility');

    $collision = Release::factory()->draft()->for($repository)->create(['version' => '9.9.9']);

    $this->actingAs($owner)
        ->from(route('releases.edit', $release))
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $collision->version,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'Valid entry']],
        ]))
        ->assertSessionHasErrors('version');

    $repository->archived_at = now();
    $repository->save();

    $this->actingAs($owner)
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'Valid entry']],
        ]))
        ->assertForbidden();
});

test('a failed publication rolls all writes back', function (): void {
    $repository = Repository::factory()->create();
    $release = Release::factory()->draft()->for($repository)->create([
        'title' => 'Original draft',
        'version' => '0.1.0',
    ]);
    ChangeEntry::factory()->for($release)->create(['sort_order' => 0, 'content' => 'Original entry']);
    Release::factory()->draft()->for($repository)->create(['version' => '1.0.0']);

    expect(fn () => app(PublishRelease::class)($release, releaseDraftPayload([
        'title' => 'Changed title',
        'version' => '1.0.0',
        'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'Changed entry']],
    ])))->toThrow(ValidationException::class);

    $release->refresh();

    expect($release->title)->toBe('Original draft')
        ->and($release->state)->toBe(ReleaseState::Draft)
        ->and($release->published_at)->toBeNull()
        ->and($release->changeEntries()->value('content'))->toBe('Original entry');
});

test('published releases retain their publication date, receive an edit date, and synchronize entries', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $publishedAt = now()->subDay();
    $release = Release::factory()->published()->for($repository)->create([
        'published_at' => $publishedAt,
        'visibility' => RepositoryVisibility::Public,
    ]);
    $first = ChangeEntry::factory()->for($release)->create(['sort_order' => 0, 'content' => 'First']);
    $second = ChangeEntry::factory()->for($release)->create(['sort_order' => 1, 'content' => 'Second']);

    $this->actingAs($owner)
        ->patch(route('releases.update', $release), releaseDraftPayload([
            'title' => 'Updated publication',
            'version' => $release->version,
            'visibility' => RepositoryVisibility::Public->value,
            'change_entries' => [
                ['id' => $second->id, 'change_type' => ChangeType::Fixed->value, 'content' => 'Second first'],
                ['id' => $first->id, 'change_type' => ChangeType::Added->value, 'content' => 'First second'],
            ],
        ]))
        ->assertRedirect(route('releases.show', $release));

    $release->refresh();

    expect($release->published_at?->toDateTimeString())->toBe($publishedAt->toDateTimeString())
        ->and($release->edited_at)->not->toBeNull()
        ->and($release->changeEntries()->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($release->changeEntries()->pluck('sort_order')->all())->toBe([0, 1]);
});

test('published releases require an entry, can be deleted with confirmation, and cannot be published twice', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->draft()->for($repository)->create(['title' => 'Publish once']);

    $this->actingAs($owner)
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'A valid entry']],
        ]))
        ->assertRedirect();

    expect($release->refresh()->isPublished())->toBeTrue();

    $this->actingAs($owner)
        ->patch(route('releases.update', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [],
        ]))
        ->assertSessionHasErrors('change_entries');

    $this->actingAs($owner)
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'A valid entry']],
        ]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('releases.destroy', $release), ['confirmation' => $release->title])
        ->assertRedirect(route('repositories.show', $repository));

    $this->actingAs($owner)
        ->get(route('releases.show', $release->public_id))
        ->assertNotFound();
});

test('private publications remain inaccessible to guests and non-owners', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();
    $release = Release::factory()->published()->for($repository)->create([
        'visibility' => RepositoryVisibility::Private,
    ]);
    ChangeEntry::factory()->for($release)->create();

    $this->get(route('releases.show', $release))
        ->assertRedirect(route('login'));

    $this->actingAs($otherUser)
        ->get(route('releases.show', $release))
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->post(route('releases.publish', $release), releaseDraftPayload([
            'version' => $release->version,
            'change_entries' => [['change_type' => ChangeType::Added->value, 'content' => 'No access']],
        ]))
        ->assertNotFound();
});
