import { Head, Link } from '@inertiajs/react';
import RepositoryController from '@/actions/App/Http/Controllers/RepositoryController';
import { show } from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryForm } from '@/components/repositories/repository-form';
import { Button } from '@/components/ui/button';
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

export default function EditRepository({
    repository,
}: {
    repository: Repository;
}) {
    return (
        <>
            <Head title={`Edit ${repository.name}`} />
            <div className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Repository settings
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update how {repository.name} is described and
                            shared.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={show(repository.public_id)}>
                            Back to repository
                        </Link>
                    </Button>
                </header>
                <RepositoryForm
                    form={RepositoryController.update.form(
                        repository.public_id,
                    )}
                    repository={repository}
                    isEditing
                />
            </div>
        </>
    );
}
