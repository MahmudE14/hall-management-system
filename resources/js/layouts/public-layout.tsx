import { Link, usePage } from '@inertiajs/react';
import { dashboard, home, login } from '@/routes';
import { index as noticesIndex } from '@/routes/notices';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth } = usePage<{
        auth: { user: { name: string } | null };
    }>().props;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="border-b border-sidebar-border/70 dark:border-sidebar-border">
                <div className="mx-auto flex w-full max-w-5xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <Link href={home()} className="font-semibold">
                        Kabi Jasimuddin Hall
                    </Link>

                    <nav className="flex flex-wrap items-center gap-4 text-sm">
                        <Link
                            href={noticesIndex()}
                            className="font-medium underline-offset-4 hover:underline"
                        >
                            Notice board
                        </Link>

                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="text-muted-foreground underline-offset-4 hover:underline"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="text-muted-foreground underline-offset-4 hover:underline"
                            >
                                Log in
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
                {children}
            </main>

            <footer className="border-t border-sidebar-border/70 px-4 py-4 text-center text-xs text-muted-foreground dark:border-sidebar-border">
                Kabi Jasimuddin Hall Management System
            </footer>
        </div>
    );
}
