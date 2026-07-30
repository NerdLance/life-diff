<?php

namespace App\Actions\Releases;

use App\Domain\Releases\ReleaseVersionSuggester;
use App\Domain\Releases\SemanticVersion;
use App\Enums\ReleaseType;
use App\Models\Repository;

class SuggestReleaseVersion
{
    public function __construct(private ReleaseVersionSuggester $suggester) {}

    public function __invoke(Repository $repository, ReleaseType $releaseType): string
    {
        $latestVersion = $repository->releases()
            ->published()
            ->chronological()
            ->value('version');

        return $this->suggester->suggest(
            $latestVersion === null ? null : SemanticVersion::fromString($latestVersion),
            $releaseType,
        )->toString();
    }
}
