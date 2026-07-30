<?php

namespace App\Domain\Profiles;

use App\Enums\ProfileStatus;

final class ProfileStatusPresentation
{
    /**
     * @var array<string, array{label: string, description: string}>
     */
    private const MAP = [
        'stable' => [
            'label' => 'Stable',
            'description' => 'Current systems are functioning with no major active change.',
        ],
        'experimental' => [
            'label' => 'Experimental',
            'description' => 'Trying uncertain changes and documenting the results.',
        ],
        'active_development' => [
            'label' => 'Under active development',
            'description' => 'Several meaningful changes are in progress.',
        ],
        'maintenance_mode' => [
            'label' => 'Maintenance mode',
            'description' => 'Energy is intentionally limited to essential upkeep.',
        ],
        'breaking_changes_expected' => [
            'label' => 'Breaking changes expected',
            'description' => 'A major transition is underway.',
        ],
        'long_term_support' => [
            'label' => 'Long-term support',
            'description' => 'A mature routine or practice is being sustained.',
        ],
        'needs_hotfix' => [
            'label' => 'Needs hotfix',
            'description' => 'A current issue needs attention without implying personal failure.',
        ],
    ];

    /**
     * @return array{label: string, description: string}
     */
    public static function for(ProfileStatus $status): array
    {
        return self::MAP[$status->value];
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function all(): array
    {
        return self::MAP;
    }
}
