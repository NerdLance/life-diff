<?php

namespace App\Domain\Releases;

use App\Enums\ReleaseType;

final class ReleaseVersionSuggester
{
    public function suggest(?SemanticVersion $latestPublishedVersion, ReleaseType $releaseType): SemanticVersion
    {
        if ($latestPublishedVersion === null) {
            return new SemanticVersion(0, 1, 0);
        }

        return match ($releaseType) {
            ReleaseType::Major => $latestPublishedVersion->incrementMajor(),
            ReleaseType::Minor => $latestPublishedVersion->incrementMinor(),
            ReleaseType::Patch,
            ReleaseType::Hotfix,
            ReleaseType::Experimental,
            ReleaseType::Rollback => $latestPublishedVersion->incrementPatch(),
        };
    }
}
