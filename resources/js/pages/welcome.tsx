import { Head, Link, usePage } from '@inertiajs/react';
import { Armchair, Megaphone, MessageSquare, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login } from '@/routes';
import { index as noticesIndex } from '@/routes/notices';

const features = [
    {
        title: 'Seat Management',
        description:
            'Allocate and vacate hall seats, and keep room occupancy current.',
        icon: Armchair,
    },
    {
        title: 'Complaints',
        description:
            'Submit, assign, and track hall complaints through to resolution.',
        icon: MessageSquare,
    },
    {
        title: 'Notice Board',
        description:
            'Read published hall notices and download their PDF attachments.',
        icon: Megaphone,
    },
    {
        title: 'Student Records',
        description:
            'View student profiles, residency status, and allocation history.',
        icon: Users,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />

            <section className="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-gradient-to-br from-muted/70 via-background to-background dark:border-sidebar-border">
                <div className="grid gap-12 px-6 py-12 sm:px-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:px-14 lg:py-16">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.22em] text-muted-foreground uppercase">
                            University Hall Portal
                        </p>
                        <h1 className="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">
                            Kabi Jasimuddin Hall
                        </h1>
                        <p className="mt-2 text-lg font-medium text-muted-foreground">
                            Hall Management System
                        </p>
                        <p className="mt-5 max-w-xl text-base leading-7 text-muted-foreground">
                            A central portal for managing hall seats,
                            complaints, notices, and student records.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button
                                asChild
                                size="lg"
                                className="w-full sm:w-auto"
                            >
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    data-test="welcome-primary-action"
                                >
                                    {auth.user ? 'Open dashboard' : 'Log in'}
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="w-full sm:w-auto"
                            >
                                <Link
                                    href={noticesIndex()}
                                    data-test="welcome-notice-board"
                                >
                                    <Megaphone aria-hidden="true" />
                                    Public notice board
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div
                        aria-hidden="true"
                        className="relative hidden size-56 shrink-0 items-center justify-center rounded-full border border-foreground/15 lg:flex"
                    >
                        <div className="absolute inset-4 rounded-full border border-dashed border-foreground/15" />
                        <div className="text-center">
                            <span className="block font-serif text-5xl font-semibold tracking-tight">
                                KJH
                            </span>
                            <span className="mt-2 block text-[0.65rem] font-medium tracking-[0.28em] text-muted-foreground uppercase">
                                Hall Portal
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mt-10 sm:mt-12" aria-labelledby="hall-services">
                <h2
                    id="hall-services"
                    className="text-sm font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                >
                    Hall services
                </h2>

                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {features.map((feature) => (
                        <Card
                            key={feature.title}
                            className="gap-4 py-5 transition-colors hover:border-foreground/20"
                        >
                            <CardHeader className="gap-3">
                                <span className="flex size-9 items-center justify-center rounded-lg border bg-background">
                                    <feature.icon
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div className="space-y-1.5">
                                    <CardTitle className="text-base">
                                        {feature.title}
                                    </CardTitle>
                                    <CardDescription className="leading-6">
                                        {feature.description}
                                    </CardDescription>
                                </div>
                            </CardHeader>
                        </Card>
                    ))}
                </div>
            </section>
        </>
    );
}
