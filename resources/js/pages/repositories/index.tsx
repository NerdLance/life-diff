import { Head, Link } from '@inertiajs/react';
import { Archive, ArrowUpRight, FolderPlus, Settings2 } from 'lucide-react';
import {
    create,
    edit,
    show,
} from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryBadges } from '@/components/repositories/repository-badges';
import { Button } from '@/components/ui/button';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

type Repository = {
    public_id: string;
    name: string;
    slug: string;
    status: ProfileStatus;
    visibility: RepositoryVisibility;
    archived_at: string | null;
    release_count: number;
    last_release_at: string | null;
    latest_version: string | null;
};

function RepositoryList({
    repositories,
    emptyCopy,
}: {
    repositories: Repository[];
    emptyCopy: string;
}) {
    if (repositories.length === 0) {
        return (
            <p className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                {emptyCopy}
            </p>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border border-border">
            <div className="divide-y">
                {repositories.map((repository) => (
                    <article
                        key={repository.public_id}
                        className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div className="min-w-0 space-y-2">
                            <h2 className="truncate font-semibold">
                                <Link
                                    href={show(repository.public_id)}
                                    className="rounded-sm hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    {repository.name}
                                </Link>
                            </h2>
                            <RepositoryBadges
                                status={repository.status}
                                visibility={repository.visibility}
                            />
                            <p className="text-sm text-muted-foreground">
                                {repository.release_count} releases ·{' '}
                                {repository.latest_version
                                    ? `latest v${repository.latest_version}`
                                    : 'no published version'}
                                {repository.last_release_at
                                    ? ` · ${repository.last_release_at}`
                                    : ''}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button size="sm" variant="outline" asChild>
                                <Link href={show(repository.public_id)}>
                                    <ArrowUpRight /> Open
                                </Link>
                            </Button>
                            <Button size="sm" variant="outline" asChild>
                                <Link href={edit(repository.public_id)}>
                                    <Settings2 /> Settings
                                </Link>
                            </Button>
                        </div>
                    </article>
                ))}
            </div>
        </div>
    );
}

export default function RepositoryIndex({
    activeRepositories,
    archivedRepositories,
}: {
    activeRepositories: Repository[];
    archivedRepositories: Repository[];
}) {
    return (
        <>
            <Head title="Repositories" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Repositories
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Areas of life, organized for clear release-note
                            journaling.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <FolderPlus /> Create repository
                        </Link>
                    </Button>
                </header>
                <section
                    className="space-y-4"
                    aria-labelledby="active-repositories"
                >
                    <h2
                        id="active-repositories"
                        className="text-lg font-semibold"
                    >
                        Active
                    </h2>
                    <RepositoryList
                        repositories={activeRepositories}
                        emptyCopy="No active repositories yet. Start with one area you want to document."
                    />
                </section>
                <section
                    className="space-y-4"
                    aria-labelledby="archived-repositories"
                >
                    <h2
                        id="archived-repositories"
                        className="flex items-center gap-2 text-lg font-semibold"
                    >
                        <Archive className="size-5" /> Archived
                    </h2>
                    <RepositoryList
                        repositories={archivedRepositories}
                        emptyCopy="Archived repositories will appear here. They remain readable until you restore them."
                    />
                </section>
            </div>
        </>
    );
}
