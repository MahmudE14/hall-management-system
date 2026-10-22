import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, History, LogOut, MapPin } from 'lucide-react';
import SeatAllocationController from '@/actions/App/Http/Controllers/SeatAllocationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as studentsIndex } from '@/routes/students';

type Allocation = {
    id: number;
    room_no: string;
    block: string;
    started_at: string;
    vacated_at: string | null;
    vacancy_reason: string | null;
};

type AvailableSeat = {
    id: number;
    label: string;
    room_no: string;
    block: string | null;
};

type ActiveAllocation = {
    id: number;
    allocated_at: string;
    seat: { id: number; label: string };
    room: { id: number; room_no: string };
    block: { id: number; name: string; code: string } | null;
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

export default function StudentShow({
    student,
    canAllocate,
    availableSeats,
    activeAllocation,
}: {
    student: Student;
    canAllocate: boolean;
    availableSeats: AvailableSeat[];
    activeAllocation: ActiveAllocation | null;
}) {
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
                        {activeAllocation ? (
                            <div className="mt-3 space-y-1 text-sm">
                                <p className="font-medium">
                                    Room {activeAllocation.room.room_no}, seat{' '}
                                    {activeAllocation.seat.label}
                                </p>
                                <p className="text-muted-foreground">
                                    {activeAllocation.block
                                        ? `${activeAllocation.block.name} (${activeAllocation.block.code})`
                                        : 'Block unassigned'}
                                </p>
                                <p className="text-muted-foreground">
                                    Allocated {activeAllocation.allocated_at}
                                </p>
                            </div>
                        ) : (
                            <p className="mt-3 text-sm text-muted-foreground">
                                No active allocation. This student is attached.
                            </p>
                        )}
                    </section>
                </div>

                {canAllocate && activeAllocation && (
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-2">
                            <LogOut className="size-4 text-muted-foreground" />
                            <h2 className="font-medium">Vacate current seat</h2>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Closing this allocation frees the seat and returns
                            the student to attached status.
                        </p>
                        <VacancyForm allocationId={activeAllocation.id} />
                    </section>
                )}

                {canAllocate &&
                    student.registration_status === 'approved' &&
                    !activeAllocation && (
                        <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                            <h2 className="font-medium">Allocate a seat</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Allocating an available seat makes this student
                                a resident and updates room occupancy.
                            </p>
                            <AllocationForm
                                studentId={student.id}
                                seats={availableSeats}
                            />
                        </section>
                    )}

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

function AllocationForm({
    studentId,
    seats,
}: {
    studentId: number;
    seats: AvailableSeat[];
}) {
    const form = useForm({ seat_id: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(SeatAllocationController.store.url(studentId), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    if (seats.length === 0) {
        return (
            <p className="mt-3 text-sm text-muted-foreground">
                No free seats are available. Vacate a seat or add seats to a
                room first.
            </p>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
        >
            <div className="grid gap-2">
                <Label htmlFor="seat_id">Available seat</Label>
                <Select
                    value={form.data.seat_id}
                    onValueChange={(value) => form.setData('seat_id', value)}
                >
                    <SelectTrigger
                        id="seat_id"
                        className="w-72"
                        aria-invalid={Boolean(form.errors.seat_id)}
                    >
                        <SelectValue placeholder="Select a seat" />
                    </SelectTrigger>
                    <SelectContent>
                        {seats.map((seat) => (
                            <SelectItem key={seat.id} value={String(seat.id)}>
                                {seat.block ? `${seat.block} - ` : ''}
                                Room {seat.room_no}, seat {seat.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={form.errors.seat_id} />
            </div>
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Allocating...' : 'Allocate seat'}
            </Button>
        </form>
    );
}

function VacancyForm({ allocationId }: { allocationId: number }) {
    const form = useForm({
        vacated_at: new Date().toISOString().slice(0, 10),
        vacancy_reason: '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.patch(SeatAllocationController.vacate.url(allocationId), {
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={submit} className="mt-4 grid gap-3 sm:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor="vacated_at">Vacancy date</Label>
                <Input
                    id="vacated_at"
                    type="date"
                    value={form.data.vacated_at}
                    onChange={(event) =>
                        form.setData('vacated_at', event.target.value)
                    }
                    aria-invalid={Boolean(form.errors.vacated_at)}
                />
                <InputError message={form.errors.vacated_at} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="vacancy_reason">Reason</Label>
                <Input
                    id="vacancy_reason"
                    value={form.data.vacancy_reason}
                    onChange={(event) =>
                        form.setData('vacancy_reason', event.target.value)
                    }
                    autoComplete="off"
                    aria-invalid={Boolean(form.errors.vacancy_reason)}
                />
                <InputError message={form.errors.vacancy_reason} />
            </div>
            <div className="sm:col-span-2">
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Vacating...' : 'Vacate seat'}
                </Button>
            </div>
        </form>
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
