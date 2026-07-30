<?php

namespace App\Domain\Repositories;

use App\Enums\RepositoryVisibility;

final class RepositoryVisibilityPresentation
{
    /**
     * @var array<string, array{label: string, description: string}>
     */
    private const MAP = [
        'private' => [
            'label' => 'Private',
            'description' => 'Only you can view this repository and its releases.',
        ],
        'unlisted' => [
            'label' => 'Unlisted',
            'description' => 'Anyone with the direct link can view it, but it stays out of public listings.',
        ],
        'public' => [
            'label' => 'Public',
            'description' => 'Anyone can view it, and it may appear on your public profile.',
        ],
    ];

    /**
     * @return array{label: string, description: string}
     */
    public static function for(RepositoryVisibility $visibility): array
    {
        return self::MAP[$visibility->value];
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function all(): array
    {
        return self::MAP;
    }
}
