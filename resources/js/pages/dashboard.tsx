import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FolderPlus } from 'lucide-react';
import {
    create,
    index,
    show,
} from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryBadges } from '@/components/repositories/repository-badges';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

type Repository = {
    public_id: string;
    name: string;
    status: ProfileStatus;
    visibility: RepositoryVisibility;
};

type Release = {
    public_id: string;
    version: string;
    title: string;
    release_type: string;
    published_at: string | null;
    updated_at: string;
    repository: { public_id: string; name: string };
};

export default function Dashboard({
    repositories,
    drafts,
    recentPublishedReleases,
}: {
    repositories: Repository[];
    drafts: Release[];
    recentPublishedReleases: Release[];
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="space-y-2">
                        <p className="text-sm font-medium text-muted-foreground">
                            Your journal
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Life, documented as it changes.
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Keep a clear record of the areas you are
                            maintaining, changing, or rebuilding.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <FolderPlus />
                            Create repository
                        </Link>
                    </Button>
                </header>

                {repositories.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Start with an area of life</CardTitle>
                            <CardDescription>
                                Create a private repository for something you
                                want to document. Releases can follow when you
                                are ready.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href={create()}>
                                    Create your first repository
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <section
                        className="space-y-4"
                        aria-labelledby="active-repositories"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <h2
                                id="active-repositories"
                                className="text-lg font-semibold"
                            >
                                Active repositories
                            </h2>
                            <Link
                                href={index()}
                                className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                            >
                                View all
                            </Link>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {repositories.map((repository) => (
                                <Card
                                    key={repository.public_id}
                                    className="gap-4"
                                >
                                    <CardHeader className="gap-3">
                                        <CardTitle className="text-base">
                                            <Link
                                                href={show(
                                                    repository.public_id,
                                                )}
                                                className="rounded-sm hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                {repository.name}
                                            </Link>
                                        </CardTitle>
                                        <RepositoryBadges
                                            status={repository.status}
                                            visibility={repository.visibility}
                                        />
                                    </CardHeader>
                                    <CardContent>
                                        <Link
                                            href={show(repository.public_id)}
                                            className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                        >
                                            Open repository{' '}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                )}

                {drafts.length > 0 && (
                    <section className="space-y-4" aria-labelledby="drafts">
                        <h2 id="drafts" className="text-lg font-semibold">
                            Drafts needing continuation
                        </h2>
                        <div className="divide-y rounded-lg border border-border">
                            {drafts.map((release) => (
                                <div
                                    key={release.public_id}
                                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {release.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {release.repository.name} · updated{' '}
                                            {release.updated_at}
                                        </p>
                                    </div>
                                    <Badge variant="outline">Draft</Badge>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                {recentPublishedReleases.length > 0 && (
                    <section
                        className="space-y-4"
                        aria-labelledby="recent-releases"
                    >
                        <h2
                            id="recent-releases"
                            className="text-lg font-semibold"
                        >
                            Recent published releases
                        </h2>
                        <div className="divide-y rounded-lg border border-border">
                            {recentPublishedReleases.map((release) => (
                                <div
                                    key={release.public_id}
                                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
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
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
