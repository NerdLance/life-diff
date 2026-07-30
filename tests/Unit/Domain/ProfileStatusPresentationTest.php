<?php

use App\Domain\Profiles\ProfileStatusPresentation;
use App\Enums\ProfileStatus;

test('every profile status has a humane label and description', function (ProfileStatus $status, string $label): void {
    expect(ProfileStatusPresentation::for($status))
        ->toBe([
            'label' => $label,
            'description' => ProfileStatusPresentation::all()[$status->value]['description'],
        ])
        ->and(ProfileStatusPresentation::for($status)['description'])->not->toBeEmpty();
})->with([
    [ProfileStatus::Stable, 'Stable'],
    [ProfileStatus::Experimental, 'Experimental'],
    [ProfileStatus::ActiveDevelopment, 'Under active development'],
    [ProfileStatus::MaintenanceMode, 'Maintenance mode'],
    [ProfileStatus::BreakingChangesExpected, 'Breaking changes expected'],
    [ProfileStatus::LongTermSupport, 'Long-term support'],
    [ProfileStatus::NeedsHotfix, 'Needs hotfix'],
]);
