import { Head, Link } from '@inertiajs/react';
import { show as showPublicProfile } from '@/actions/App/Http/Controllers/PublicProfileController';
import { CopyPublicLink } from '@/components/releases/copy-public-link';
import { Badge } from '@/components/ui/badge';
import { changeTypePresentation, releaseTypePresentation } from '@/types';
import type { ChangeType, ReleaseType } from '@/types';

type ReleaseDetail = {
    public_id: string;
    version: string;
    release_type: ReleaseType;
    title: string;
    body: string | null;
    published_at: string;
    edited_at: string | null;
    change_entries: Array<{ change_type: ChangeType; content: string }>;
};

export default function PublicRelease({
    profile,
    repository,
    release,
    copyLink,
}: {
    profile: { display_name: string; handle: string };
    repository: { name: string; slug: string };
    release: ReleaseDetail;
    copyLink: string;
}) {
    return (
        <>
            <Head title={`${release.title} · ${repository.name}`} />
            <main className="mx-auto w-full max-w-3xl space-y-8 p-4 sm:p-6">
                <header className="space-y-3 border-b border-border pb-6">
                    <Link
                        href={showPublicProfile(profile.handle)}
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        @{profile.handle}
                    </Link>
                    <p className="text-sm text-muted-foreground">
                        {profile.display_name} · {repository.name}
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight break-words sm:text-3xl">
                        {release.title}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <Badge variant="secondary">v{release.version}</Badge>
                        <span>
                            {
                                releaseTypePresentation[release.release_type]
                                    .label
                            }
                        </span>
                        <span>
                            Published{' '}
                            {new Date(
                                release.published_at,
                            ).toLocaleDateString()}
                        </span>
                        {release.edited_at ? <span>Edited</span> : null}
                    </div>
                    <CopyPublicLink href={copyLink} />
                </header>

                {release.body ? (
                    <section className="text-sm leading-6 break-words whitespace-pre-wrap">
                        {release.body}
                    </section>
                ) : null}

                <section
                    className="space-y-3"
                    aria-labelledby="changes-heading"
                >
                    <h2 id="changes-heading" className="text-lg font-semibold">
                        Changes
                    </h2>
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
                </section>
            </main>
        </>
    );
}
