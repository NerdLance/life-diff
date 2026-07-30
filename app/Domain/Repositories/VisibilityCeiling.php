<?php

namespace App\Domain\Repositories;

use App\Enums\RepositoryVisibility;

final class VisibilityCeiling
{
    public static function allows(
        RepositoryVisibility $repositoryVisibility,
        RepositoryVisibility $releaseVisibility,
    ): bool {
        return match ($repositoryVisibility) {
            RepositoryVisibility::Private => $releaseVisibility === RepositoryVisibility::Private,
            RepositoryVisibility::Unlisted => in_array($releaseVisibility, [
                RepositoryVisibility::Private,
                RepositoryVisibility::Unlisted,
            ], true),
            RepositoryVisibility::Public => true,
        };
    }
}
