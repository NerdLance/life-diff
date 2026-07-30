<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('phase one tables contain the required columns', function (): void {
    expect(Schema::hasColumns('users', [
        'handle',
        'display_name',
        'bio',
        'status',
        'timezone',
    ]))->toBeTrue();

    expect(Schema::hasColumns('repositories', [
        'public_id',
        'owner_id',
        'name',
        'normalized_name',
        'slug',
        'description',
        'visibility',
        'status',
        'archived_at',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('releases', [
        'public_id',
        'repository_id',
        'version',
        'release_type',
        'state',
        'title',
        'body',
        'visibility',
        'published_at',
        'edited_at',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('change_entries', [
        'release_id',
        'change_type',
        'content',
        'sort_order',
    ]))->toBeTrue();
});

test('repository identifiers and owner-scoped names and slugs are unique', function (): void {
    $owner = User::factory()->create();

    insertRepository($owner->id, '01J00000000000000000000001', 'Career', 'career', 'career');

    expect(fn () => insertRepository(
        $owner->id,
        '01J00000000000000000000002',
        'Health',
        'health',
        'career',
    ))->toThrow(QueryException::class);

    expect(fn () => insertRepository(
        $owner->id,
        '01J00000000000000000000003',
        'Another career',
        'career',
        'another-career',
    ))->toThrow(QueryException::class);
});

test('release versions remain unique after a soft delete', function (): void {
    $owner = User::factory()->create();
    $repositoryId = insertRepository(
        $owner->id,
        '01J00000000000000000000004',
        'Career',
        'career',
        'career',
    );
    $releaseId = insertRelease($repositoryId, '01J00000000000000000000005', '1.0.0');

    DB::table('releases')->where('id', $releaseId)->update(['deleted_at' => now()]);

    expect(fn () => insertRelease($repositoryId, '01J00000000000000000000006', '1.0.0'))
        ->toThrow(QueryException::class);
});

test('change entry ordering is unique and owner deletion cascades through the release tree', function (): void {
    $owner = User::factory()->create();
    $repositoryId = insertRepository(
        $owner->id,
        '01J00000000000000000000007',
        'Career',
        'career',
        'career',
    );
    $releaseId = insertRelease($repositoryId, '01J00000000000000000000008', '1.0.0');

    DB::table('change_entries')->insert([
        'release_id' => $releaseId,
        'change_type' => 'added',
        'content' => 'Started a new role.',
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('change_entries')->insert([
        'release_id' => $releaseId,
        'change_type' => 'improved',
        'content' => 'Improved the onboarding notes.',
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    $owner->delete();

    expect(DB::table('repositories')->count())->toBe(0)
        ->and(DB::table('releases')->count())->toBe(0)
        ->and(DB::table('change_entries')->count())->toBe(0);
});

function insertRepository(int $ownerId, string $publicId, string $name, string $normalizedName, string $slug): int
{
    return DB::table('repositories')->insertGetId([
        'public_id' => $publicId,
        'owner_id' => $ownerId,
        'name' => $name,
        'normalized_name' => $normalizedName,
        'slug' => $slug,
        'visibility' => 'private',
        'status' => 'stable',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function insertRelease(int $repositoryId, string $publicId, string $version): int
{
    return DB::table('releases')->insertGetId([
        'public_id' => $publicId,
        'repository_id' => $repositoryId,
        'version' => $version,
        'release_type' => 'patch',
        'state' => 'draft',
        'title' => 'A release',
        'visibility' => 'private',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
