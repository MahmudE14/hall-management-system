import { Head, Link, useForm } from '@inertiajs/react';
import RoomController from '@/actions/App/Http/Controllers/RoomController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as rooms } from '@/routes/rooms';

type Room = {
    id: number;
    room_no: string;
    floor: number;
    capacity: number;
    seats_count: number;
    occupied_seats: number;
    free_seats: number;
    block: {
        id: number;
        name: string;
        code: string;
    } | null;
    seats: {
        id: number;
        label: string;
    }[];
};

export default function EditRoom({ room }: { room: Room }) {
    const form = useForm({
        room_no: room.room_no,
        floor: room.floor.toString(),
        capacity: room.capacity.toString(),
    });

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
                    description="Update the room number, floor, or capacity."
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
                        <div className="flex flex-wrap gap-1">
                            <Badge variant="secondary">
                                {room.occupied_seats} occupied
                            </Badge>
                            <Badge variant="outline">
                                {room.free_seats} free
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Capacity {room.capacity}. {room.seats_count}{' '}
                            {room.seats_count === 1 ? 'seat' : 'seats'} defined.
                        </p>
                        {room.seats.length > 0 && (
                            <div className="flex flex-wrap gap-1">
                                {room.seats.map((seat) => (
                                    <Badge key={seat.id} variant="outline">
                                        {seat.label}
                                    </Badge>
                                ))}
                            </div>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
