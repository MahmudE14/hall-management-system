import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import RegistrationController from '@/actions/App/Http/Controllers/RegistrationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/registrations';

type StudentProfile = {
    id: number;
    registration_number: string;
    department: string;
    session: string;
    phone: string;
    registration_status: string;
    created_at: string;
    user: {
        name: string;
        email: string;
    };
    registration_decisions: {
        id: number;
        status: string;
        reason: string | null;
        created_at: string;
        actor: {
            name: string;
        };
    }[];
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function RegistrationsIndex({
    registrations,
    status,
}: {
    registrations: Paginated<StudentProfile>;
    status: string;
}) {
    return (
        <>
            <Head title="Registrations" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Student registrations"
                    description="Review pending registrations or reconsider rejected students."
                />

                <div className="flex gap-2">
                    <Button asChild variant={status === 'pending' ? 'default' : 'outline'}>
                        <Link href={index({ query: { status: 'pending' } })}>
                            Pending
                        </Link>
                    </Button>
                    <Button asChild variant={status === 'rejected' ? 'default' : 'outline'}>
                        <Link href={index({ query: { status: 'rejected' } })}>
                            Rejected
                        </Link>
                    </Button>
                </div>

                {registrations.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No {status} registrations.
                    </p>
                ) : (
                    <div className="space-y-4">
                        {registrations.data.map((profile) => (
                            <RegistrationCard
                                key={profile.id}
                                profile={profile}
                                status={status}
                            />
                        ))}
                    </div>
                )}

                <Pagination links={registrations.links} />
            </div>
        </>
    );
}

function RegistrationCard({
    profile,
    status,
}: {
    profile: StudentProfile;
    status: string;
}) {
    const [showReject, setShowReject] = useState(false);
    const rejectForm = useForm({ reason: '' });

    function approve() {
        router.post(RegistrationController.approve.url(profile), {
            preserveScroll: true,
        });
    }

    function reject() {
        rejectForm.post(RegistrationController.reject.url(profile), {
            preserveScroll: true,
            onSuccess: () => {
                rejectForm.reset();
                setShowReject(false);
            },
        });
    }

    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="font-medium">{profile.user.name}</p>
                    <p className="text-sm text-muted-foreground">
                        {profile.user.email}
                    </p>
                </div>
                <p className="text-sm text-muted-foreground">
                    {profile.registration_number} · {profile.department} ·{' '}
                    {profile.session}
                </p>
            </div>

            <p className="mt-2 text-sm text-muted-foreground">
                Phone: {profile.phone}
            </p>

            {profile.registration_decisions.length > 0 && (
                <div className="mt-4 space-y-2 text-sm">
                    <p className="font-medium">Decision history</p>
                    {profile.registration_decisions.map((decision) => (
                        <p key={decision.id} className="text-muted-foreground">
                            {decision.status} by {decision.actor.name} on{' '}
                            {new Date(decision.created_at).toLocaleString()}
                            {decision.reason ? ` — ${decision.reason}` : ''}
                        </p>
                    ))}
                </div>
            )}

            <div className="mt-4 flex flex-wrap gap-2">
                {status === 'pending' && (
                    <>
                        <Button onClick={approve}>Approve</Button>
                        <Button
                            variant="outline"
                            onClick={() => setShowReject((visible) => !visible)}
                        >
                            Reject
                        </Button>
                    </>
                )}

                {status === 'rejected' && (
                    <Button
                        onClick={() =>
                            router.post(
                                RegistrationController.reconsider.url(profile),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Return to pending
                    </Button>
                )}
            </div>

            {showReject && status === 'pending' && (
                <div className="mt-4 space-y-2">
                    <Label htmlFor={`reason-${profile.id}`}>
                        Rejection reason
                    </Label>
                    <Input
                        id={`reason-${profile.id}`}
                        value={rejectForm.data.reason}
                        onChange={(event) =>
                            rejectForm.setData('reason', event.target.value)
                        }
                        placeholder="Explain why the registration is rejected"
                    />
                    <InputError message={rejectForm.errors.reason} />
                    <Button
                        variant="destructive"
                        disabled={rejectForm.processing}
                        onClick={reject}
                    >
                        Confirm rejection
                    </Button>
                </div>
            )}
        </div>
    );
}

function Pagination({ links }: { links: Paginated<unknown>['links'] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        className={`rounded-md border px-3 py-1 text-sm ${
                            link.active ? 'bg-muted font-medium' : ''
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : null,
            )}
        </div>
    );
}
