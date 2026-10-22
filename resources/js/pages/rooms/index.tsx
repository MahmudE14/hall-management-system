import { Head, Link } from '@inertiajs/react';
import { DoorOpen, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as blocks } from '@/routes/blocks';
import { create as createRoom, edit as editRoom } from '@/routes/rooms';

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
    seats: {
        id: number;
        label: string;
    }[];
};

export default function RoomsIndex({
    rooms,
    canManage,
}: {
    rooms: Room[];
    canManage: boolean;
}) {
    return (
        <>
            <Head title="Rooms" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Rooms"
                        description="Review room capacity, defined seats, and seat management."
                    />

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={blocks()}>View blocks</Link>
                        </Button>
                        {canManage && (
                            <Button asChild>
                                <Link href={createRoom()}>
                                    <Plus />
                                    New room
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {rooms.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <DoorOpen className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No rooms yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a room within a block to start defining
                            seats.
                        </p>
                        {canManage && (
                            <Button asChild className="mt-4">
                                <Link href={createRoom()}>
                                    <Plus />
                                    New room
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-[52rem] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Room
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Block
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Floor
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Capacity
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Occupancy
                                    </th>
                                    {canManage && (
                                        <th className="px-4 py-3 font-medium">
                                            Actions
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {rooms.map((room) => (
                                    <tr
                                        key={room.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 align-top">
                                            <p className="font-medium">
                                                {room.room_no}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {room.seats_count}{' '}
                                                {room.seats_count === 1
                                                    ? 'seat defined'
                                                    : 'seats defined'}
                                            </p>
                                            {room.seats.length > 0 && (
                                                <div className="mt-2 flex flex-wrap gap-1">
                                                    {room.seats.map((seat) => (
                                                        <Badge
                                                            key={seat.id}
                                                            variant="outline"
                                                        >
                                                            {seat.label}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {room.block ? (
                                                <>
                                                    <p>{room.block.name}</p>
                                                    <p className="text-muted-foreground">
                                                        {room.block.code}
                                                    </p>
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Unassigned
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {room.floor}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {room.capacity}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {room.occupancy_available ? (
                                                <div className="flex flex-wrap gap-1">
                                                    <Badge variant="secondary">
                                                        {room.occupied_seats}{' '}
                                                        occupied
                                                    </Badge>
                                                    <Badge variant="outline">
                                                        {room.free_seats} free
                                                    </Badge>
                                                </div>
                                            ) : (
                                                <div className="space-y-1">
                                                    <p className="text-muted-foreground">
                                                        Not yet available
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Allocation history is
                                                        required.
                                                    </p>
                                                </div>
                                            )}
                                        </td>
                                        {canManage && (
                                            <td className="px-4 py-3 align-top">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link href={editRoom(room)}>
                                                        Edit room
                                                    </Link>
                                                </Button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
