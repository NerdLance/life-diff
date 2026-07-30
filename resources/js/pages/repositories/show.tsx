import { Form, Head, Link } from '@inertiajs/react';
import { ArchiveRestore, Plus, Settings2 } from 'lucide-react';
import ReleaseController from '@/actions/App/Http/Controllers/ReleaseController';
import RepositoryController from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryBadges } from '@/components/repositories/repository-badges';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

type Repository = {
    public_id: string;
    name: string;
    slug: string;
    description: string | null;
    status: ProfileStatus;
    visibility: RepositoryVisibility;
    archived_at: string | null;
};
type Release = {
    public_id: string;
    version: string;
    title: string;
    release_type: string;
    visibility: RepositoryVisibility;
    published_at: string | null;
    updated_at: string;
};

export default function RepositoryShow({
    repository,
    drafts,
    publishedReleases,
    actions,
}: {
    repository: Repository;
    drafts: Release[];
    publishedReleases: Release[];
    actions: {
        canUpdate: boolean;
        canArchive: boolean;
        canRestore: boolean;
        canDelete: boolean;
        canCreateRelease: boolean;
    };
}) {
    return (
        <>
            <Head title={repository.name} />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6">
                <header className="flex flex-col justify-between gap-4 border-b border-border pb-6 sm:flex-row sm:items-start">
                    <div className="min-w-0 space-y-3">
                        <p className="font-mono text-sm text-muted-foreground">
                            {repository.slug}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {repository.name}
                        </h1>
                        {repository.description && (
                            <p className="max-w-2xl whitespace-pre-line text-muted-foreground">
                                {repository.description}
                            </p>
                        )}
                        <RepositoryBadges
                            status={repository.status}
                            visibility={repository.visibility}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {actions.canCreateRelease && (
                            <Button asChild>
                                <Link
                                    href={ReleaseController.create(
                                        repository.public_id,
                                    )}
                                >
                                    <Plus /> New draft
                                </Link>
                            </Button>
                        )}
                        {actions.canUpdate && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={RepositoryController.edit(
                                        repository.public_id,
                                    )}
                                >
                                    <Settings2 /> Settings
                                </Link>
                            </Button>
                        )}
                        {actions.canArchive && (
                            <Form
                                {...RepositoryController.archive.form(
                                    repository.public_id,
                                )}
                            >
                                <Button variant="outline">
                                    <ArchiveRestore /> Archive
                                </Button>
                            </Form>
                        )}
                        {actions.canRestore && (
                            <Form
                                {...RepositoryController.restore.form(
                                    repository.public_id,
                                )}
                            >
                                <Button>
                                    <ArchiveRestore /> Restore
                                </Button>
                            </Form>
                        )}
                    </div>
                </header>
                {repository.archived_at && (
                    <p className="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm">
                        Archived repositories are read-only until restored.
                    </p>
                )}
                <section className="space-y-4" aria-labelledby="draft-releases">
                    <div>
                        <h2
                            id="draft-releases"
                            className="text-lg font-semibold"
                        >
                            Drafts
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Only you can see these releases.
                        </p>
                    </div>
                    {drafts.length === 0 ? (
                        <Card>
                            <CardContent className="pt-6 text-sm text-muted-foreground">
                                No drafts need continuation.
                            </CardContent>
                        </Card>
                    ) : (
                        <ReleaseList releases={drafts} draft />
                    )}
                </section>
                <section
                    className="space-y-4"
                    aria-labelledby="published-releases"
                >
                    <div>
                        <h2
                            id="published-releases"
                            className="text-lg font-semibold"
                        >
                            Published releases
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Most recent first.
                        </p>
                    </div>
                    {publishedReleases.length === 0 ? (
                        <Card>
                            <CardContent className="pt-6 text-sm text-muted-foreground">
                                No published releases yet.
                            </CardContent>
                        </Card>
                    ) : (
                        <ReleaseList releases={publishedReleases} />
                    )}
                </section>
            </div>
        </>
    );
}

function ReleaseList({
    releases,
    draft = false,
}: {
    releases: Release[];
    draft?: boolean;
}) {
    return (
        <div className="divide-y rounded-lg border border-border">
            {releases.map((release) => (
                <article
                    key={release.public_id}
                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                >
                    <div className="min-w-0">
                        <h3 className="truncate font-medium">
                            {release.title}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {release.release_type} ·{' '}
                            {draft
                                ? `updated ${release.updated_at}`
                                : release.published_at}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Badge variant="secondary">v{release.version}</Badge>
                        <Badge variant="outline">
                            {draft ? 'Draft' : release.visibility}
                        </Badge>
                        {draft && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={ReleaseController.edit(
                                        release.public_id,
                                    )}
                                >
                                    Continue
                                </Link>
                            </Button>
                        )}
                    </div>
                </article>
            ))}
        </div>
    );
}
