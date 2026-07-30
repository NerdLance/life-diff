import { Head } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/ReleaseController';
import { show as repositoryShow } from '@/actions/App/Http/Controllers/RepositoryController';
import { ReleaseComposer } from '@/components/releases/release-composer';
import type {
    ComposerRelease,
    ComposerRepository,
} from '@/components/releases/release-composer';

export default function EditRelease({
    repository,
    release,
    suggestedVersion,
}: {
    repository: ComposerRepository;
    release: ComposerRelease;
    suggestedVersion: string;
}) {
    return (
        <>
            <Head title={`Edit draft · ${repository.name}`} />
            <main className="mx-auto w-full max-w-3xl p-4 sm:p-6">
                <div className="mb-8 space-y-2">
                    <p className="text-sm text-muted-foreground">
                        Editing release draft
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        {release.title || 'Untitled draft'}
                    </h1>
                </div>
                <ReleaseComposer
                    repository={repository}
                    release={release}
                    suggestedVersion={suggestedVersion}
                    submitUrl={update.url(release.public_id ?? '')}
                    method="patch"
                    cancelUrl={repositoryShow.url(repository.public_id)}
                />
            </main>
        </>
    );
}
