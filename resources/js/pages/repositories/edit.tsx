import { Form, Head, Link } from '@inertiajs/react';
import RepositoryController from '@/actions/App/Http/Controllers/RepositoryController';
import {
    destroy,
    show,
} from '@/actions/App/Http/Controllers/RepositoryController';
import { RepositoryForm } from '@/components/repositories/repository-form';
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
                <section className="mt-10 space-y-4 rounded-lg border border-destructive/40 bg-destructive/5 p-4">
                    <div className="space-y-1">
                        <h2 className="font-semibold">Delete repository</h2>
                        <p className="text-sm text-muted-foreground">
                            This removes the repository and its releases from
                            all routes immediately.
                        </p>
                    </div>
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">
                                Delete repository
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Delete this repository?</DialogTitle>
                            <DialogDescription>
                                Type the repository name to confirm. This is a
                                soft deletion, but it is not shown as a
                                restoration workflow.
                            </DialogDescription>
                            <Form
                                {...destroy.form(repository.public_id)}
                                className="space-y-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="confirmation">
                                                Repository name
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
                                                    {errors.confirmation}
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
                                                disabled={processing}
                                            >
                                                Delete repository
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </section>
            </div>
        </>
    );
}
