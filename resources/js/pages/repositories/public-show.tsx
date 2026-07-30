import { Head, Link } from '@inertiajs/react';
import { show as showPublicProfile } from '@/actions/App/Http/Controllers/PublicProfileController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    profileStatusPresentation,
    repositoryVisibilityPresentation,
} from '@/types';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

type Release = {
    version: string;
    title: string;
    release_type: string;
    published_at: string | null;
};

export default function PublicRepository({
    profile,
    repository,
    publishedReleases,
}: {
    profile: { display_name: string; handle: string };
    repository: {
        name: string;
        slug: string;
        description: string | null;
        status: ProfileStatus;
        visibility: RepositoryVisibility;
    };
    publishedReleases: Release[];
}) {
    return (
        <>
            <Head title={`${repository.name} · ${profile.display_name}`} />
            <div className="space-y-8">
                <header className="space-y-3 border-b border-border pb-8">
                    <Link
                        href={showPublicProfile(profile.handle)}
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        @{profile.handle}
                    </Link>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {repository.name}
                    </h1>
                    {repository.description && (
                        <p className="max-w-2xl whitespace-pre-line text-muted-foreground">
                            {repository.description}
                        </p>
                    )}
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="secondary">
                            {profileStatusPresentation[repository.status].label}
                        </Badge>
                        <Badge variant="outline">
                            {
                                repositoryVisibilityPresentation[
                                    repository.visibility
                                ].label
                            }
                        </Badge>
                    </div>
                </header>
                <section
                    className="space-y-4"
                    aria-labelledby="public-timeline"
                >
                    <div>
                        <h2
                            id="public-timeline"
                            className="text-xl font-semibold"
                        >
                            Public releases
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Only public published releases appear here.
                        </p>
                    </div>
                    {publishedReleases.length === 0 ? (
                        <Card>
                            <CardContent className="pt-6 text-sm text-muted-foreground">
                                No public releases are available.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="divide-y rounded-lg border border-border">
                            {publishedReleases.map((release) => (
                                <article
                                    key={release.version}
                                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                                >
                                    <div>
                                        <h3 className="font-medium">
                                            {release.title}
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            {release.release_type} ·{' '}
                                            {release.published_at}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        v{release.version}
                                    </Badge>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
