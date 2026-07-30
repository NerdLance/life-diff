import { Head, Link } from '@inertiajs/react';
import { show as showPublicRepository } from '@/actions/App/Http/Controllers/PublicRepositoryController';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { profileStatusPresentation } from '@/types';
import type { ProfileStatus } from '@/types';

type Repository = {
    name: string;
    slug: string;
    description: string | null;
    status: ProfileStatus;
};
type Release = {
    version: string;
    title: string;
    release_type: string;
    published_at: string | null;
    repository: { name: string; slug: string };
};

export default function PublicProfile({
    profile,
    repositories,
    recentPublishedReleases,
}: {
    profile: {
        display_name: string;
        handle: string;
        bio: string | null;
        status: ProfileStatus;
    };
    repositories: Repository[];
    recentPublishedReleases: Release[];
}) {
    return (
        <>
            <Head title={`${profile.display_name} (@${profile.handle})`} />
            <div className="space-y-10">
                <header className="space-y-3 border-b border-border pb-8">
                    <p className="font-mono text-sm text-muted-foreground">
                        @{profile.handle}
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {profile.display_name}
                    </h1>
                    <Badge variant="secondary">
                        {profileStatusPresentation[profile.status].label}
                    </Badge>
                    {profile.bio && (
                        <p className="max-w-2xl whitespace-pre-line text-muted-foreground">
                            {profile.bio}
                        </p>
                    )}
                </header>
                <section
                    className="space-y-4"
                    aria-labelledby="public-repositories"
                >
                    <h2
                        id="public-repositories"
                        className="text-xl font-semibold"
                    >
                        Public repositories
                    </h2>
                    {repositories.length === 0 ? (
                        <Card>
                            <CardContent className="pt-6 text-sm text-muted-foreground">
                                No public repositories are available.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {repositories.map((repository) => (
                                <Card key={repository.slug}>
                                    <CardHeader>
                                        <CardTitle>
                                            <Link
                                                href={showPublicRepository({
                                                    user: profile.handle,
                                                    repository: repository.slug,
                                                })}
                                                className="rounded-sm hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                {repository.name}
                                            </Link>
                                        </CardTitle>
                                        <CardDescription>
                                            {
                                                profileStatusPresentation[
                                                    repository.status
                                                ].label
                                            }
                                        </CardDescription>
                                    </CardHeader>
                                    {repository.description && (
                                        <CardContent className="text-sm text-muted-foreground">
                                            {repository.description}
                                        </CardContent>
                                    )}
                                </Card>
                            ))}
                        </div>
                    )}
                </section>
                <section
                    className="space-y-4"
                    aria-labelledby="recent-public-releases"
                >
                    <h2
                        id="recent-public-releases"
                        className="text-xl font-semibold"
                    >
                        Recent public releases
                    </h2>
                    {recentPublishedReleases.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No public releases are available.
                        </p>
                    ) : (
                        <div className="divide-y rounded-lg border border-border">
                            {recentPublishedReleases.map((release) => (
                                <article
                                    key={`${release.repository.slug}-${release.version}`}
                                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {release.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {release.repository.name} ·{' '}
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
