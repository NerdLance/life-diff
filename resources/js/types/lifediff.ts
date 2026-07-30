export type PresentationMetadata = {
    label: string;
    description: string;
};

export const profileStatuses = [
    'stable',
    'experimental',
    'active_development',
    'maintenance_mode',
    'breaking_changes_expected',
    'long_term_support',
    'needs_hotfix',
] as const;

export type ProfileStatus = (typeof profileStatuses)[number];
export type RepositoryStatus = ProfileStatus;

export const profileStatusPresentation = {
    stable: {
        label: 'Stable',
        description:
            'Current systems are functioning with no major active change.',
    },
    experimental: {
        label: 'Experimental',
        description: 'Trying uncertain changes and documenting the results.',
    },
    active_development: {
        label: 'Under active development',
        description: 'Several meaningful changes are in progress.',
    },
    maintenance_mode: {
        label: 'Maintenance mode',
        description: 'Energy is intentionally limited to essential upkeep.',
    },
    breaking_changes_expected: {
        label: 'Breaking changes expected',
        description: 'A major transition is underway.',
    },
    long_term_support: {
        label: 'Long-term support',
        description: 'A mature routine or practice is being sustained.',
    },
    needs_hotfix: {
        label: 'Needs hotfix',
        description:
            'A current issue needs attention without implying personal failure.',
    },
} satisfies Record<ProfileStatus, PresentationMetadata>;

export const repositoryVisibilities = [
    'private',
    'unlisted',
    'public',
] as const;

export type RepositoryVisibility = (typeof repositoryVisibilities)[number];

export const repositoryVisibilityPresentation = {
    private: {
        label: 'Private',
        description: 'Only you can view this repository and its releases.',
    },
    unlisted: {
        label: 'Unlisted',
        description:
            'Anyone with the direct link can view it, but it stays out of public listings.',
    },
    public: {
        label: 'Public',
        description:
            'Anyone can view it, and it may appear on your public profile.',
    },
} satisfies Record<RepositoryVisibility, PresentationMetadata>;

export const releaseStates = ['draft', 'published'] as const;

export type ReleaseState = (typeof releaseStates)[number];

export const releaseTypes = [
    'major',
    'minor',
    'patch',
    'hotfix',
    'experimental',
    'rollback',
] as const;

export type ReleaseType = (typeof releaseTypes)[number];

export const releaseTypePresentation = {
    major: {
        label: 'Major',
        description: 'A substantial shift or new chapter.',
    },
    minor: {
        label: 'Minor',
        description: 'A meaningful, contained development.',
    },
    patch: {
        label: 'Patch',
        description: 'A small adjustment or continuation.',
    },
    hotfix: {
        label: 'Hotfix',
        description: 'A focused response to something that needs attention.',
    },
    experimental: {
        label: 'Experimental',
        description: 'A trial worth documenting, whatever the outcome.',
    },
    rollback: {
        label: 'Rollback',
        description: 'A deliberate return to a prior approach.',
    },
} satisfies Record<ReleaseType, PresentationMetadata>;

export const changeTypes = [
    'added',
    'improved',
    'fixed',
    'removed',
    'deprecated',
    'known_issue',
] as const;

export type ChangeType = (typeof changeTypes)[number];

export const changeTypePresentation = {
    added: {
        label: 'Added',
        description: 'Something new entered the picture.',
    },
    improved: {
        label: 'Improved',
        description: 'Something changed in a helpful way.',
    },
    fixed: {
        label: 'Fixed',
        description: 'A problem received attention or resolution.',
    },
    removed: {
        label: 'Removed',
        description: 'Something was intentionally taken away.',
    },
    deprecated: {
        label: 'Deprecated',
        description: 'Something is being phased out or reconsidered.',
    },
    known_issue: {
        label: 'Known issue',
        description: 'An unresolved issue is being recorded clearly.',
    },
} satisfies Record<ChangeType, PresentationMetadata>;
