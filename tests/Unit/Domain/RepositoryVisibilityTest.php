<?php

use App\Domain\Repositories\RepositoryVisibilityPresentation;
use App\Domain\Repositories\VisibilityCeiling;
use App\Enums\RepositoryVisibility;

test('every repository visibility has a humane label and description', function (RepositoryVisibility $visibility, string $label): void {
    expect(RepositoryVisibilityPresentation::for($visibility))
        ->toBe([
            'label' => $label,
            'description' => RepositoryVisibilityPresentation::all()[$visibility->value]['description'],
        ])
        ->and(RepositoryVisibilityPresentation::for($visibility)['description'])->not->toBeEmpty();
})->with([
    [RepositoryVisibility::Private, 'Private'],
    [RepositoryVisibility::Unlisted, 'Unlisted'],
    [RepositoryVisibility::Public, 'Public'],
]);

test('visibility ceilings allow only contract combinations', function (
    RepositoryVisibility $repositoryVisibility,
    RepositoryVisibility $releaseVisibility,
    bool $allowed,
): void {
    expect(VisibilityCeiling::allows($repositoryVisibility, $releaseVisibility))->toBe($allowed);
})->with([
    'private repository / private release' => [RepositoryVisibility::Private, RepositoryVisibility::Private, true],
    'private repository / unlisted release' => [RepositoryVisibility::Private, RepositoryVisibility::Unlisted, false],
    'private repository / public release' => [RepositoryVisibility::Private, RepositoryVisibility::Public, false],
    'unlisted repository / private release' => [RepositoryVisibility::Unlisted, RepositoryVisibility::Private, true],
    'unlisted repository / unlisted release' => [RepositoryVisibility::Unlisted, RepositoryVisibility::Unlisted, true],
    'unlisted repository / public release' => [RepositoryVisibility::Unlisted, RepositoryVisibility::Public, false],
    'public repository / private release' => [RepositoryVisibility::Public, RepositoryVisibility::Private, true],
    'public repository / unlisted release' => [RepositoryVisibility::Public, RepositoryVisibility::Unlisted, true],
    'public repository / public release' => [RepositoryVisibility::Public, RepositoryVisibility::Public, true],
]);
