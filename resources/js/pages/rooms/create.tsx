import { Head, Link, useForm } from '@inertiajs/react';
import RoomController from '@/actions/App/Http/Controllers/RoomController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as createBlock } from '@/routes/blocks';
import { index as rooms } from '@/routes/rooms';

type Block = {
    id: number;
    name: string;
    code: string;
};

export default function CreateRoom({ blocks }: { blocks: Block[] }) {
    const form = useForm({
        block_id: blocks[0]?.id.toString() ?? '',
        room_no: '',
        floor: '',
        capacity: '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(RoomController.store.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="New room" />

            <div className="space-y-6 p-4">
                <Heading
                    title="New room"
                    description="Add a room to an existing block."
                />

                {blocks.length === 0 ? (
                    <div className="max-w-xl rounded-xl border border-dashed border-sidebar-border/70 p-6 dark:border-sidebar-border">
                        <p className="font-medium">No blocks available</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a block before adding rooms to the hall.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={createBlock()}>New block</Link>
                        </Button>
                    </div>
                ) : (
                    <form
                        onSubmit={submit}
                        className="max-w-xl space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="block_id">Block</Label>
                            <select
                                id="block_id"
                                value={form.data.block_id}
                                onChange={(event) =>
                                    form.setData('block_id', event.target.value)
                                }
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                aria-invalid={Boolean(form.errors.block_id)}
                            >
                                <option value="">Select a block</option>
                                {blocks.map((block) => (
                                    <option key={block.id} value={block.id}>
                                        {block.name} ({block.code})
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.block_id} />
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
                                {form.processing
                                    ? 'Creating...'
                                    : 'Create room'}
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={rooms()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}
