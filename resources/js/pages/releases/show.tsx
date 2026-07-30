import { Form, Head, Link } from '@inertiajs/react';
import {
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/ReleaseController';
import { show as repositoryShow } from '@/actions/App/Http/Controllers/RepositoryController';
import { CopyPublicLink } from '@/components/releases/copy-public-link';
import type { ComposerRepository } from '@/components/releases/release-composer';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { changeTypePresentation, releaseTypePresentation } from '@/types';
import type {
    ChangeType,
    ReleaseState,
    ReleaseType,
    RepositoryVisibility,
} from '@/types';

type ReleaseDetail = {
    public_id: string;
    version: string;
    release_type: ReleaseType;
    state: ReleaseState;
    title: string;
    body: string | null;
    visibility: RepositoryVisibility;
    published_at: string | null;
    edited_at: string | null;
    change_entries: Array<{ change_type: ChangeType; content: string }>;
};

export default function ShowRelease({
    repository,
    profile,
    release,
    actions,
    copyLink,
}: {
    repository: ComposerRepository;
    profile: { display_name: string; handle: string };
    release: ReleaseDetail;
    actions: { canUpdate: boolean; canDelete: boolean; isOwner: boolean };
    copyLink: string | null;
}) {
    return (
        <>
            <Head title={`${release.title} · ${repository.name}`} />
            <main className="mx-auto w-full max-w-3xl space-y-8 p-4 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-2">
                        <Link
                            href={repositoryShow.url(repository.public_id)}
                            className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                        >
                            {repository.name}
                        </Link>
                        <p className="text-sm text-muted-foreground">
                            @{profile.handle} · {profile.display_name}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight break-words sm:text-3xl">
                            {release.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {release.version} ·{' '}
                            {
                                releaseTypePresentation[release.release_type]
                                    .label
                            }
                            {release.published_at
                                ? ` · Published ${new Date(release.published_at).toLocaleDateString()}`
                                : ' · Draft'}
                            {release.edited_at ? ' · Edited' : ''}
                        </p>
                    </div>
                    {actions.canUpdate ? (
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href={edit.url(release.public_id)}>
                                    Edit
                                </Link>
                            </Button>
                            {actions.canDelete ? (
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button variant="destructive">
                                            Delete
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            Delete this release?
                                        </DialogTitle>
                                        <DialogDescription>
                                            This immediately removes the release
                                            from all routes. Type its title to
                                            confirm.
                                        </DialogDescription>
                                        <Form
                                            {...destroy.form(release.public_id)}
                                            className="space-y-4"
                                        >
                                            {({ errors, processing }) => (
                                                <>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="confirmation">
                                                            Release title
                                                        </Label>
                                                        <Input
                                                            id="confirmation"
                                                            name="confirmation"
                                                            autoFocus
                                                            aria-invalid={Boolean(
                                                                errors.confirmation,
                                                            )}
                                                        />
                                                        {errors.confirmation ? (
                                                            <p className="text-sm text-destructive">
                                                                {
                                                                    errors.confirmation
                                                                }
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                            >
                                                                Cancel
                                                            </Button>
                                                        </DialogClose>
                                                        <Button
                                                            type="submit"
                                                            variant="destructive"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Delete release
                                                        </Button>
                                                    </DialogFooter>
                                                </>
                                            )}
                                        </Form>
                                    </DialogContent>
                                </Dialog>
                            ) : null}
                        </div>
                    ) : null}
                    {copyLink ? <CopyPublicLink href={copyLink} /> : null}
                </div>

                {release.body ? (
                    <section className="text-sm leading-6 break-words whitespace-pre-wrap">
                        {release.body}
                    </section>
                ) : null}

                <section
                    className="space-y-3"
                    aria-labelledby="change-entries-heading"
                >
                    <h2
                        id="change-entries-heading"
                        className="text-lg font-semibold"
                    >
                        Changes
                    </h2>
                    {release.change_entries.length > 0 ? (
                        <ol className="space-y-3">
                            {release.change_entries.map((entry, index) => (
                                <li
                                    key={`${entry.change_type}-${index}`}
                                    className="rounded-lg border border-border p-4"
                                >
                                    <p className="text-sm font-medium">
                                        {
                                            changeTypePresentation[
                                                entry.change_type
                                            ].label
                                        }
                                    </p>
                                    <p className="mt-1 text-sm break-words whitespace-pre-wrap text-muted-foreground">
                                        {entry.content}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No change entries have been recorded.
                        </p>
                    )}
                </section>
            </main>
        </>
    );
}
