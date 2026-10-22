import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, History, MapPin } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as studentsIndex } from '@/routes/students';

type Allocation = {
    id: number;
    room_no: string;
    block: string;
    started_at: string;
    vacated_at: string | null;
    vacancy_reason: string | null;
};

type Student = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    registration_number: string;
    department: string;
    session: string;
    phone: string;
    registration_status: string;
    residency_status: string;
    block: { id: number; name: string; code: string } | null;
    current_room: { id: number; room_no: string } | null;
    allocation_history: Allocation[];
    allocation_history_available: boolean;
};

export default function StudentShow({ student }: { student: Student }) {
    return (
        <>
            <Head title={student.name} />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={student.name}
                        description="Student personal details, residency, and accommodation history."
                    />

                    <Button asChild variant="outline">
                        <Link href={studentsIndex()}>
                            <ArrowLeft />
                            Back to students
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">
                        {formatValue(student.registration_status)}
                    </Badge>
                    <Badge variant="outline">
                        {formatValue(student.residency_status)}
                    </Badge>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Personal details</h2>
                        <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <Detail label="Full name" value={student.name} />
                            <Detail label="Email" value={student.email} />
                            <Detail
                                label="Registration number"
                                value={student.registration_number}
                            />
                            <Detail
                                label="Department"
                                value={student.department}
                            />
                            <Detail label="Session" value={student.session} />
                            <Detail label="Phone" value={student.phone} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-2">
                            <MapPin className="size-4 text-muted-foreground" />
                            <h2 className="font-medium">Current room</h2>
                        </div>
                        {student.current_room ? (
                            <p className="mt-3 text-sm">
                                Room {student.current_room.room_no}
                                {student.block ? `, ${student.block.name}` : ''}
                            </p>
                        ) : (
                            <div className="mt-3 space-y-1 text-sm text-muted-foreground">
                                <p>Not yet available</p>
                                <p>
                                    Current room details require seat allocation
                                    history.
                                </p>
                            </div>
                        )}
                    </section>
                </div>

                <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex items-center gap-2">
                        <History className="size-4 text-muted-foreground" />
                        <h2 className="font-medium">Allocation history</h2>
                    </div>

                    {!student.allocation_history_available ? (
                        <div className="mt-3 space-y-1 text-sm text-muted-foreground">
                            <p>Not yet available</p>
                            <p>
                                Allocation history becomes available when seat
                                allocations are introduced.
                            </p>
                        </div>
                    ) : student.allocation_history.length === 0 ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            This student has no recorded allocations.
                        </p>
                    ) : (
                        <div className="mt-3 overflow-x-auto">
                            <table className="w-full min-w-[40rem] text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Room
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Block
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            From
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            To
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Reason
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {student.allocation_history.map(
                                        (allocation) => (
                                            <tr
                                                key={allocation.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3">
                                                    {allocation.room_no}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {allocation.block}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {allocation.started_at}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {allocation.vacated_at ??
                                                        'Active'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {allocation.vacancy_reason ??
                                                        '—'}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function formatValue(value: string) {
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}
