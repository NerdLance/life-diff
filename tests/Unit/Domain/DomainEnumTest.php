<?php

use App\Enums\ChangeType;
use App\Enums\ProfileStatus;
use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;

test('domain enums expose every contract value', function (string $enum, array $values): void {
    expect(array_column($enum::cases(), 'value'))->toBe($values);
})->with([
    'profile statuses' => [ProfileStatus::class, [
        'stable',
        'experimental',
        'active_development',
        'maintenance_mode',
        'breaking_changes_expected',
        'long_term_support',
        'needs_hotfix',
    ]],
    'repository visibilities' => [RepositoryVisibility::class, ['private', 'unlisted', 'public']],
    'release states' => [ReleaseState::class, ['draft', 'published']],
    'release types' => [ReleaseType::class, ['major', 'minor', 'patch', 'hotfix', 'experimental', 'rollback']],
    'change types' => [ChangeType::class, ['added', 'improved', 'fixed', 'removed', 'deprecated', 'known_issue']],
]);
