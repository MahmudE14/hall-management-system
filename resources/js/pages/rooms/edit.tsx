import { Head, Link, useForm } from '@inertiajs/react';
import { Armchair, Plus } from 'lucide-react';
import RoomController from '@/actions/App/Http/Controllers/RoomController';
import SeatController from '@/actions/App/Http/Controllers/SeatController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as rooms } from '@/routes/rooms';

type Seat = {
    id: number;
    label: string;
};

type Room = {
    id: number;
    room_no: string;
    floor: number;
    capacity: number;
    seats_count: number;
    occupancy_available: boolean;
    occupied_seats: number | null;
    free_seats: number | null;
    block: {
        id: number;
        name: string;
        code: string;
    } | null;
    seats: Seat[];
};

export default function EditRoom({ room }: { room: Room }) {
    const form = useForm({
        room_no: room.room_no,
        floor: room.floor.toString(),
        capacity: room.capacity.toString(),
    });
    const atCapacity = room.seats_count >= room.capacity;

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.put(RoomController.update.url(room), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={`Edit room ${room.room_no}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title={`Room ${room.room_no}`}
                    description="Update the room number, floor, capacity, or seats."
                />

                <div className="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div className="grid gap-2">
                            <Label>Block</Label>
                            <p className="text-sm">
                                {room.block
                                    ? `${room.block.name} (${room.block.code})`
                                    : 'Unassigned'}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="room_no">Room number</Label>
                            <Input
                                id="room_no"
                                value={form.data.room_no}
                                onChange={(event) =>
                                    form.setData('room_no', event.target.value)
                                }
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.room_no)}
                            />
                            <InputError message={form.errors.room_no} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="floor">Floor</Label>
                                <Input
                                    id="floor"
                                    type="number"
                                    min="0"
                                    max="255"
                                    value={form.data.floor}
                                    onChange={(event) =>
                                        form.setData(
                                            'floor',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={Boolean(form.errors.floor)}
                                />
                                <InputError message={form.errors.floor} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    min="1"
                                    max="255"
                                    value={form.data.capacity}
                                    onChange={(event) =>
                                        form.setData(
                                            'capacity',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={Boolean(form.errors.capacity)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Capacity cannot be lower than the{' '}
                                    {room.seats_count}{' '}
                                    {room.seats_count === 1
                                        ? 'seat already defined'
                                        : 'seats already defined'}
                                    .
                                </p>
                                <InputError message={form.errors.capacity} />
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving...' : 'Save changes'}
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={rooms()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>

                    <aside className="space-y-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="font-medium">Occupancy</p>
                        {room.occupancy_available ? (
                            <div className="flex flex-wrap gap-1">
                                <Badge variant="secondary">
                                    {room.occupied_seats} occupied
                                </Badge>
                                <Badge variant="outline">
                                    {room.free_seats} free
                                </Badge>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Occupied and free seat counts are unavailable
                                until seat allocation history is implemented.
                                This page does not treat missing allocation data
                                as zero occupancy.
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            Capacity {room.capacity}. {room.seats_count}{' '}
                            {room.seats_count === 1 ? 'seat' : 'seats'} defined.
                        </p>
                    </aside>
                </div>

                <section className="max-w-4xl space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 className="font-medium">Seats</h2>
                            <p className="text-sm text-muted-foreground">
                                Seat labels identify individual seats within
                                this room. Use one character such as A, B, C, or
                                D.
                            </p>
                        </div>
                        <Badge variant="outline">
                            {room.seats_count} of {room.capacity} defined
                        </Badge>
                    </div>

                    {room.seats.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-sidebar-border/70 p-6 text-center dark:border-sidebar-border">
                            <Armchair className="mx-auto size-8 text-muted-foreground" />
                            <p className="mt-2 font-medium">No seats yet</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Add the first seat below to make this room ready
                                for future allocation.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            {room.seats.map((seat) => (
                                <SeatRow key={seat.id} seat={seat} />
                            ))}
                        </div>
                    )}

                    {atCapacity ? (
                        <p className="rounded-lg border border-dashed border-sidebar-border/70 p-3 text-sm text-muted-foreground dark:border-sidebar-border">
                            This room has reached its capacity of{' '}
                            {room.capacity} seats. Increase the room capacity
                            before adding another seat.
                        </p>
                    ) : (
                        <AddSeatForm room={room} />
                    )}
                </section>
            </div>
        </>
    );
}

function AddSeatForm({ room }: { room: Room }) {
    const form = useForm({ label: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(SeatController.store.url(room), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-3 rounded-lg border border-dashed border-sidebar-border/70 p-3 sm:flex-row sm:items-end dark:border-sidebar-border"
        >
            <div className="grid gap-2">
                <Label htmlFor="new-seat-label">New seat label</Label>
                <Input
                    id="new-seat-label"
                    value={form.data.label}
                    onChange={(event) =>
                        form.setData('label', event.target.value.toUpperCase())
                    }
                    maxLength={1}
                    autoComplete="off"
                    className="w-24 uppercase"
                    aria-invalid={Boolean(form.errors.label)}
                />
                <InputError message={form.errors.label} />
            </div>
            <Button type="submit" disabled={form.processing}>
                <Plus />
                {form.processing ? 'Adding...' : 'Add seat'}
            </Button>
        </form>
    );
}

function SeatRow({ seat }: { seat: Seat }) {
    const form = useForm({ label: seat.label });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.put(SeatController.update.url(seat), {
            preserveScroll: true,
        });
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 p-3 sm:flex-row sm:items-end dark:border-sidebar-border"
        >
            <div className="grid gap-2">
                <Label htmlFor={`seat-${seat.id}`}>Seat label</Label>
                <Input
                    id={`seat-${seat.id}`}
                    value={form.data.label}
                    onChange={(event) =>
                        form.setData('label', event.target.value.toUpperCase())
                    }
                    maxLength={1}
                    autoComplete="off"
                    className="w-24 uppercase"
                    aria-invalid={Boolean(form.errors.label)}
                />
                <InputError message={form.errors.label} />
            </div>
            <Button
                type="submit"
                size="sm"
                variant="outline"
                disabled={form.processing}
            >
                {form.processing ? 'Saving...' : 'Save seat'}
            </Button>
        </form>
    );
}
