import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, LockKeyhole, Share2 } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login, register } from '@/routes';

const overviewItems = [
    {
        icon: BookOpen,
        title: 'Keep a structured history',
        description:
            'Create repositories for the areas of life you want to document, then record releases as things change.',
    },
    {
        icon: ArrowRight,
        title: 'Start imperfectly',
        description:
            'Drafts can stay incomplete. Add, reorder, revise, or remove change entries when you are ready.',
    },
    {
        icon: Share2,
        title: 'Share deliberately',
        description:
            'Keep releases private by default, use unlisted links when useful, or make selected history public.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="LifeDiff" />
            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <div className="flex items-center gap-2 font-semibold">
                            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            <span>LifeDiff</span>
                        </div>
                        <nav
                            className="flex items-center gap-2 text-sm"
                            aria-label="Account"
                        >
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    Open your journal
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-md px-3 py-2 font-medium hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Start privately
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="mx-auto grid w-full max-w-6xl gap-10 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                        <div className="space-y-6">
                            <p className="text-sm font-medium text-muted-foreground">
                                Personal history, clearly recorded
                            </p>
                            <h1 className="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">
                                A private journal for life as it changes.
                            </h1>
                            <p className="max-w-2xl text-lg leading-8 text-muted-foreground">
                                LifeDiff uses the familiar shape of release
                                notes to help you keep a humane, chronological
                                record of what is changing, what is working, and
                                what needs attention.
                            </p>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={auth.user ? dashboard() : register()}
                                    className="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    {auth.user
                                        ? 'Open your journal'
                                        : 'Create a private journal'}
                                    <ArrowRight className="size-4" />
                                </Link>
                                {!auth.user && (
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center justify-center rounded-md border border-input px-4 py-2.5 font-medium transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Log in
                                    </Link>
                                )}
                            </div>
                        </div>

                        <aside className="rounded-xl border border-border bg-muted/30 p-6 sm:p-8">
                            <div className="flex items-start gap-3">
                                <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-background text-foreground shadow-xs">
                                    <LockKeyhole className="size-4" />
                                </span>
                                <div className="space-y-2">
                                    <h2 className="font-semibold">
                                        Private first, always
                                    </h2>
                                    <p className="text-sm leading-6 text-muted-foreground">
                                        New repositories and releases begin
                                        private. Sharing is a choice you make
                                        for each part of your history.
                                    </p>
                                </div>
                            </div>
                        </aside>
                    </section>

                    <section className="border-y border-border bg-muted/20">
                        <div className="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 sm:py-16">
                            <div className="max-w-2xl space-y-3">
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    A simple rhythm for documenting change
                                </h2>
                                <p className="text-muted-foreground">
                                    Use release types and change entries as
                                    prompts, not as a scorecard. Rollbacks,
                                    hotfixes, experiments, and known issues all
                                    belong in the record.
                                </p>
                            </div>
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {overviewItems.map((item) => (
                                    <article
                                        key={item.title}
                                        className="rounded-lg border border-border bg-background p-5"
                                    >
                                        <item.icon className="size-5 text-muted-foreground" />
                                        <h3 className="mt-4 font-semibold">
                                            {item.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
