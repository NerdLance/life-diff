import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { profileStatusPresentation, profileStatuses } from '@/types';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;
    const [selectedStatus, setSelectedStatus] = useState(auth.user.status);
    const statusPresentation = profileStatusPresentation[selectedStatus];

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your LifeDiff identity and account details"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="handle">Handle</Label>

                                <Input
                                    id="handle"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.handle ?? ''}
                                    name="handle"
                                    required
                                    autoComplete="username"
                                    placeholder="your-handle"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.handle}
                                />

                                <p className="text-sm text-muted-foreground">
                                    Changing your handle can break existing
                                    public links.
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="display_name">
                                    Display name
                                </Label>

                                <Input
                                    id="display_name"
                                    className="mt-1 block w-full"
                                    defaultValue={
                                        auth.user.display_name ?? auth.user.name
                                    }
                                    name="display_name"
                                    required
                                    autoComplete="name"
                                    placeholder="Your name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.display_name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="bio">Bio</Label>

                                <textarea
                                    id="bio"
                                    className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                                    defaultValue={auth.user.bio ?? ''}
                                    name="bio"
                                    maxLength={500}
                                    placeholder="A little context about you, if you want it."
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.bio}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Profile status</Label>

                                <select
                                    id="status"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                                    value={selectedStatus}
                                    name="status"
                                    aria-describedby="profile-status-description"
                                    onChange={(event) => {
                                        const profileStatus =
                                            profileStatuses.find(
                                                (value) =>
                                                    value ===
                                                    event.target.value,
                                            );

                                        if (profileStatus) {
                                            setSelectedStatus(profileStatus);
                                        }
                                    }}
                                >
                                    {profileStatuses.map((profileStatus) => (
                                        <option
                                            key={profileStatus}
                                            value={profileStatus}
                                        >
                                            {
                                                profileStatusPresentation[
                                                    profileStatus
                                                ].label
                                            }
                                        </option>
                                    ))}
                                </select>

                                <p
                                    id="profile-status-description"
                                    className="text-sm text-muted-foreground"
                                >
                                    {statusPresentation.description}
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.status}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timezone">Timezone</Label>

                                <Input
                                    id="timezone"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.timezone}
                                    name="timezone"
                                    required
                                    placeholder="America/New_York"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.timezone}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
