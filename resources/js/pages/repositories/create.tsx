import { Head } from '@inertiajs/react';
import RepositoryController from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryForm } from '@/components/repositories/repository-form';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

export default function CreateRepository({
    repository,
}: {
    repository: {
        name: string;
        slug: string;
        description: string;
        status: ProfileStatus;
        visibility: RepositoryVisibility;
    };
}) {
    return (
        <>
            <Head title="Create repository" />
            <div className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-8 space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Create repository
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Begin privately. You can choose another visibility when
                        you are ready.
                    </p>
                </header>
                <RepositoryForm
                    form={RepositoryController.store.form()}
                    repository={repository}
                />
            </div>
        </>
    );
}
