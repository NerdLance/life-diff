import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border">
                <div className="mx-auto flex min-h-16 max-w-5xl items-center px-4 sm:px-6">
                    <Link
                        href="/"
                        className="rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <AppLogo />
                    </Link>
                </div>
            </header>
            <main className="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
                {children}
            </main>
        </div>
    );
}
