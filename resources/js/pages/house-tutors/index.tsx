import { Head, Link, router } from '@inertiajs/react';
import { GraduationCap, Plus } from 'lucide-react';
import TutorController from '@/actions/App/Http/Controllers/TutorController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    create as createTutor,
    edit as editTutor,
} from '@/routes/house-tutors';

type Block = {
    id: number;
    name: string;
    code: string;
};

type Tutor = {
    id: number;
    name: string;
    email: string;
    designation: string;
    department: string;
    phone: string;
    is_active: boolean;
    blocks: Block[];
};

export default function HouseTutorsIndex({ tutors }: { tutors: Tutor[] }) {
    return (
        <>
            <Head title="House tutors" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="House tutors"
                        description="Review tutor records and manage their block assignments."
                    />

                    <Button asChild>
                        <Link href={createTutor()}>
                            <Plus />
                            New tutor
                        </Link>
                    </Button>
                </div>

                {tutors.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <GraduationCap className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No tutors yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create the first tutor record and assign the blocks
                            they oversee.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={createTutor()}>
                                <Plus />
                                New tutor
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {tutors.map((tutor) => (
                            <div
                                key={tutor.id}
                                className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {tutor.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {tutor.email}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            tutor.is_active
                                                ? 'outline'
                                                : 'destructive'
                                        }
                                    >
                                        {tutor.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </Badge>
                                </div>

                                <div className="mt-4 space-y-1 text-sm">
                                    <p>{tutor.designation}</p>
                                    <p className="text-muted-foreground">
                                        {tutor.department} · {tutor.phone}
                                    </p>
                                </div>

                                <div className="mt-4">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">
                                        Assigned blocks
                                    </p>
                                    {tutor.blocks.length === 0 ? (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            No blocks assigned
                                        </p>
                                    ) : (
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {tutor.blocks.map((block) => (
                                                <Badge
                                                    key={block.id}
                                                    variant="secondary"
                                                >
                                                    {block.name} ({block.code})
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={editTutor(tutor)}>
                                            Edit tutor
                                        </Link>
                                    </Button>
                                    {tutor.is_active && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.patch(
                                                    TutorController.deactivate.url(
                                                        tutor,
                                                    ),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Deactivate
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
