<?php

use App\Enums\ProfileStatus;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Str;

function repositoryWorkflowPayload(array $overrides = []): array
{
    return [
        'name' => 'Career',
        'slug' => '',
        'description' => 'A place to track professional changes.',
        ...$overrides,
    ];
}

test('authenticated users can create a private stable repository with a generated slug', function (): void {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('repositories.store'), repositoryWorkflowPayload())
        ->assertRedirect();

    $repository = Repository::query()->sole();

    expect($repository->owner->is($owner))->toBeTrue()
        ->and($repository->visibility)->toBe(RepositoryVisibility::Private)
        ->and($repository->status)->toBe(ProfileStatus::Stable)
        ->and($repository->slug)->toBe('career')
        ->and($repository->normalized_name)->toBe('career')
        ->and(Str::isUlid($repository->public_id))->toBeTrue();
});

test('repository names and slugs are unique only within their owner after normalization', function (): void {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    Repository::factory()->for($owner, 'owner')->create([
        'name' => 'Career',
        'normalized_name' => 'career',
        'slug' => 'career',
    ]);

    $this->actingAs($owner)
        ->from(route('repositories.create'))
        ->post(route('repositories.store'), repositoryWorkflowPayload([
            'name' => '  CAREER  ',
            'slug' => 'another-slug',
        ]))
        ->assertSessionHasErrors('normalized_name');

    $this->actingAs($owner)
        ->from(route('repositories.create'))
        ->post(route('repositories.store'), repositoryWorkflowPayload([
            'name' => 'Health',
            'slug' => 'career',
        ]))
        ->assertSessionHasErrors('slug');

    $this->actingAs($otherOwner)
        ->post(route('repositories.store'), repositoryWorkflowPayload([
            'name' => 'Career',
            'slug' => 'career',
        ]))
        ->assertRedirect();

    expect($otherOwner->ownedRepositories()->count())->toBe(1);
});

test('repository write routes reject another authenticated user', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create();

    $this->actingAs($otherUser)
        ->patch(route('repositories.update', $repository), repositoryWorkflowPayload())
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->post(route('repositories.archive', $repository))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete(route('repositories.destroy', $repository), ['confirmation' => $repository->name])
        ->assertForbidden();
});

test('archived repositories reject writes without changing release visibility', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $release = Release::factory()->public()->for($repository)->create();

    $this->actingAs($owner)
        ->post(route('repositories.archive', $repository))
        ->assertRedirect(route('repositories.show', $repository));

    expect($repository->refresh()->isArchived())->toBeTrue()
        ->and($release->refresh()->visibility)->toBe(RepositoryVisibility::Public);

    $this->actingAs($owner)
        ->patch(route('repositories.update', $repository), repositoryWorkflowPayload([
            'visibility' => RepositoryVisibility::Private->value,
            'status' => ProfileStatus::MaintenanceMode->value,
        ]))
        ->assertForbidden();
});

test('restoring a repository makes it writable again', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->archived()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('repositories.restore', $repository))
        ->assertRedirect(route('repositories.show', $repository));

    expect($repository->refresh()->isActive())->toBeTrue();

    $this->actingAs($owner)
        ->patch(route('repositories.update', $repository), repositoryWorkflowPayload([
            'name' => 'Health',
            'visibility' => RepositoryVisibility::Unlisted->value,
            'status' => ProfileStatus::Experimental->value,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('repositories.show', $repository));

    expect($repository->refresh()->name)->toBe('Health')
        ->and($repository->visibility)->toBe(RepositoryVisibility::Unlisted);
});

test('visibility reduction narrows only broader child releases in the same update', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->public()->for($owner, 'owner')->create();
    $privateRelease = Release::factory()->private()->for($repository)->create();
    $unlistedRelease = Release::factory()->unlisted()->for($repository)->create();
    $publicRelease = Release::factory()->public()->for($repository)->create();

    $this->actingAs($owner)
        ->patch(route('repositories.update', $repository), repositoryWorkflowPayload([
            'visibility' => RepositoryVisibility::Unlisted->value,
            'status' => ProfileStatus::Stable->value,
        ]))
        ->assertRedirect(route('repositories.show', $repository));

    expect($repository->refresh()->visibility)->toBe(RepositoryVisibility::Unlisted)
        ->and($privateRelease->refresh()->visibility)->toBe(RepositoryVisibility::Private)
        ->and($unlistedRelease->refresh()->visibility)->toBe(RepositoryVisibility::Unlisted)
        ->and($publicRelease->refresh()->visibility)->toBe(RepositoryVisibility::Unlisted);

    $this->actingAs($owner)
        ->patch(route('repositories.update', $repository), repositoryWorkflowPayload([
            'visibility' => RepositoryVisibility::Private->value,
            'status' => ProfileStatus::Stable->value,
        ]))
        ->assertRedirect(route('repositories.show', $repository));

    expect($privateRelease->refresh()->visibility)->toBe(RepositoryVisibility::Private)
        ->and($unlistedRelease->refresh()->visibility)->toBe(RepositoryVisibility::Private)
        ->and($publicRelease->refresh()->visibility)->toBe(RepositoryVisibility::Private);
});

test('repository deletion requires its exact typed name and soft deleted routes stop resolving', function (): void {
    $owner = User::factory()->create();
    $repository = Repository::factory()->for($owner, 'owner')->create(['name' => 'Career']);

    $this->actingAs($owner)
        ->from(route('repositories.edit', $repository))
        ->delete(route('repositories.destroy', $repository), ['confirmation' => 'career'])
        ->assertSessionHasErrors('confirmation')
        ->assertRedirect(route('repositories.edit', $repository));

    expect($repository->fresh())->not->toBeNull();

    $this->actingAs($owner)
        ->delete(route('repositories.destroy', $repository), ['confirmation' => 'Career'])
        ->assertRedirect(route('repositories.index'));

    expect(Repository::find($repository->id))->toBeNull()
        ->and(Repository::withTrashed()->find($repository->id)?->trashed())->toBeTrue();

    $this->actingAs($owner)
        ->get(route('repositories.show', $repository))
        ->assertNotFound();
});
