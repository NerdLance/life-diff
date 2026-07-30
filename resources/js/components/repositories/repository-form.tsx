import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    profileStatusPresentation,
    profileStatuses,
    repositoryVisibilities,
    repositoryVisibilityPresentation,
} from '@/types';
import type { ProfileStatus, RepositoryVisibility } from '@/types';

type RepositoryFormValues = {
    name: string;
    slug: string;
    description: string | null;
    status: ProfileStatus;
    visibility: RepositoryVisibility;
};

export function RepositoryForm({
    form,
    repository,
    isEditing = false,
}: {
    form: { action: string; method: 'post' };
    repository: RepositoryFormValues;
    isEditing?: boolean;
}) {
    const [status, setStatus] = useState<ProfileStatus>(repository.status);
    const [visibility, setVisibility] = useState<RepositoryVisibility>(
        repository.visibility,
    );

    return (
        <Form {...form} className="space-y-6">
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={repository.name}
                            required
                            maxLength={80}
                            autoComplete="off"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            name="slug"
                            defaultValue={repository.slug}
                            maxLength={100}
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            aria-describedby="slug-help"
                            autoComplete="off"
                        />
                        <p
                            id="slug-help"
                            className="text-sm text-muted-foreground"
                        >
                            Leave this blank to create one from the name. Use
                            lowercase letters, numbers, and single hyphens.
                        </p>
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            defaultValue={repository.description ?? ''}
                            maxLength={1000}
                            className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            value={status}
                            onChange={(event) =>
                                setStatus(event.target.value as ProfileStatus)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            aria-describedby="status-description"
                        >
                            {profileStatuses.map((value) => (
                                <option key={value} value={value}>
                                    {profileStatusPresentation[value].label}
                                </option>
                            ))}
                        </select>
                        <p
                            id="status-description"
                            className="text-sm text-muted-foreground"
                        >
                            {profileStatusPresentation[status].description}
                        </p>
                        <InputError message={errors.status} />
                    </div>

                    <fieldset className="grid gap-3">
                        <legend className="text-sm font-medium">
                            Visibility
                        </legend>
                        {repositoryVisibilities.map((value) => (
                            <label
                                key={value}
                                className="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 has-[:checked]:border-primary has-[:checked]:bg-muted/50"
                            >
                                <input
                                    type="radio"
                                    name="visibility"
                                    value={value}
                                    checked={visibility === value}
                                    onChange={() => setVisibility(value)}
                                    className="mt-1 size-4 accent-primary"
                                />
                                <span className="grid gap-1">
                                    <span className="font-medium">
                                        {
                                            repositoryVisibilityPresentation[
                                                value
                                            ].label
                                        }
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {
                                            repositoryVisibilityPresentation[
                                                value
                                            ].description
                                        }
                                    </span>
                                </span>
                            </label>
                        ))}
                        <InputError message={errors.visibility} />
                    </fieldset>

                    {isEditing && (
                        <p className="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-foreground">
                            Reducing visibility also reduces any broader
                            child-release visibility. Raising it later does not
                            broaden releases automatically.
                        </p>
                    )}

                    <div className="flex flex-wrap items-center gap-3">
                        <Button disabled={processing}>
                            {isEditing
                                ? 'Save repository'
                                : 'Create repository'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
