import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import InputError from '@/components/input-error';
import { ChangeEntryList } from '@/components/releases/change-entry-list';
import type { ComposerChangeEntry } from '@/components/releases/change-entry-list';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    releaseTypePresentation,
    releaseTypes,
    repositoryVisibilities,
    repositoryVisibilityPresentation,
} from '@/types';
import type { ReleaseType, RepositoryVisibility } from '@/types';
import type { ReleaseState } from '@/types';

export type ComposerRepository = {
    public_id: string;
    name: string;
    slug: string;
    status: string;
    visibility: RepositoryVisibility;
};

export type ComposerRelease = {
    public_id?: string;
    state?: ReleaseState;
    release_type: ReleaseType;
    version: string;
    title: string;
    body: string | null;
    visibility: RepositoryVisibility;
    change_entries: ComposerChangeEntry[];
};

type ComposerFormData = Omit<ComposerRelease, 'public_id'>;

export function ReleaseComposer({
    repository,
    release,
    suggestedVersion,
    submitUrl,
    method,
    cancelUrl,
    publishUrl,
}: {
    repository: ComposerRepository;
    release: ComposerRelease;
    suggestedVersion: string;
    submitUrl: string;
    method: 'post' | 'patch';
    cancelUrl: string;
    publishUrl?: string;
}) {
    const form = useForm<ComposerFormData>({
        release_type: release.release_type,
        version: release.version,
        title: release.title,
        body: release.body ?? '',
        visibility: release.visibility,
        change_entries: release.change_entries,
    });
    const versionWasEdited = useRef(false);
    const allowNavigation = useRef(false);
    const setFormData = form.setData;

    useEffect(() => {
        if (!versionWasEdited.current) {
            setFormData('version', suggestedVersion);
        }
    }, [setFormData, suggestedVersion]);

    useEffect(() => {
        const warnBeforeUnload = (event: BeforeUnloadEvent): void => {
            if (!form.isDirty) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', warnBeforeUnload);

        const removeBeforeVisitListener = router.on('before', () => {
            if (!form.isDirty || allowNavigation.current) {
                allowNavigation.current = false;

                return;
            }

            return window.confirm(
                'You have unsaved draft changes. Leave without saving?',
            );
        });

        return () => {
            window.removeEventListener('beforeunload', warnBeforeUnload);
            removeBeforeVisitListener();
        };
    }, [form.isDirty]);

    function changeReleaseType(value: ReleaseType): void {
        form.setData('release_type', value);

        if (!versionWasEdited.current) {
            router.reload({
                data: { release_type: value },
                only: ['suggestedVersion'],
            });
        }
    }

    function submit(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (method === 'post') {
            form.post(submitUrl);

            return;
        }

        form.patch(submitUrl);
    }

    function publish(): void {
        if (publishUrl) {
            form.post(publishUrl);
        }
    }

    function cancel(): void {
        if (
            form.isDirty &&
            !window.confirm(
                'You have unsaved draft changes. Leave without saving?',
            )
        ) {
            return;
        }

        allowNavigation.current = true;
        router.visit(cancelUrl);
    }

    return (
        <form className="space-y-8" onSubmit={submit} noValidate>
            <section className="space-y-2 rounded-lg border border-border bg-muted/30 p-4">
                <p className="text-sm font-medium">Repository</p>
                <p className="min-w-0 truncate text-lg font-semibold">
                    {repository.name}
                </p>
                <p className="font-mono text-sm text-muted-foreground">
                    {repository.slug}
                </p>
            </section>

            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="release-type">Release type</Label>
                    <select
                        id="release-type"
                        value={form.data.release_type}
                        onChange={(event) =>
                            changeReleaseType(event.target.value as ReleaseType)
                        }
                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {releaseTypes.map((type) => (
                            <option key={type} value={type}>
                                {releaseTypePresentation[type].label}
                            </option>
                        ))}
                    </select>
                    <p className="text-sm text-muted-foreground">
                        {
                            releaseTypePresentation[form.data.release_type]
                                .description
                        }
                    </p>
                    <InputError message={form.errors.release_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="version">Version</Label>
                    <Input
                        id="version"
                        value={form.data.version}
                        onChange={(event) => {
                            versionWasEdited.current = true;
                            form.setData('version', event.target.value);
                        }}
                        inputMode="numeric"
                        maxLength={14}
                        aria-describedby="version-help"
                        aria-invalid={Boolean(form.errors.version)}
                    />
                    <p
                        id="version-help"
                        className="text-sm text-muted-foreground"
                    >
                        Suggested by your latest published release. You can
                        override it with major.minor.patch.
                    </p>
                    <InputError message={form.errors.version} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    value={form.data.title}
                    onChange={(event) =>
                        form.setData('title', event.target.value)
                    }
                    maxLength={160}
                    required
                    aria-invalid={Boolean(form.errors.title)}
                />
                <InputError message={form.errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="body">Context (optional)</Label>
                <textarea
                    id="body"
                    value={form.data.body ?? ''}
                    onChange={(event) =>
                        form.setData('body', event.target.value)
                    }
                    maxLength={10000}
                    className="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    aria-invalid={Boolean(form.errors.body)}
                />
                <InputError message={form.errors.body} />
            </div>

            <fieldset className="grid gap-3">
                <legend className="text-sm font-medium">
                    Future publication visibility
                </legend>
                <p className="text-sm text-muted-foreground">
                    This is saved for a future publication. Drafts remain
                    private to you.
                </p>
                {repositoryVisibilities.map((visibility) => {
                    const permitted =
                        repositoryVisibilities.indexOf(visibility) <=
                        repositoryVisibilities.indexOf(repository.visibility);

                    return (
                        <label
                            key={visibility}
                            className={cn(
                                'flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 has-[:checked]:border-primary has-[:checked]:bg-muted/50',
                                !permitted && 'cursor-not-allowed opacity-60',
                            )}
                        >
                            <input
                                type="radio"
                                name="visibility"
                                value={visibility}
                                checked={form.data.visibility === visibility}
                                onChange={() =>
                                    form.setData('visibility', visibility)
                                }
                                disabled={!permitted}
                                className="mt-1 size-4 accent-primary"
                            />
                            <span className="grid gap-1">
                                <span className="font-medium">
                                    {
                                        repositoryVisibilityPresentation[
                                            visibility
                                        ].label
                                    }
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {
                                        repositoryVisibilityPresentation[
                                            visibility
                                        ].description
                                    }
                                </span>
                            </span>
                        </label>
                    );
                })}
                <InputError message={form.errors.visibility} />
            </fieldset>

            <ChangeEntryList
                entries={form.data.change_entries}
                errors={form.errors}
                onChange={(entries) => form.setData('change_entries', entries)}
            />

            <p className="text-sm text-muted-foreground">
                {publishUrl
                    ? 'Your draft changes are saved only when you choose Save draft or Publish now.'
                    : 'Draft changes are saved only when you choose Save draft.'}
            </p>

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button
                    type="button"
                    variant="outline"
                    onClick={cancel}
                    disabled={form.processing}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing
                        ? 'Saving…'
                        : release.state === 'published'
                          ? 'Save changes'
                          : 'Save draft'}
                </Button>
                {publishUrl ? (
                    <Button
                        type="button"
                        onClick={publish}
                        disabled={form.processing}
                    >
                        {form.processing ? 'Publishing…' : 'Publish now'}
                    </Button>
                ) : null}
            </div>
        </form>
    );
}
