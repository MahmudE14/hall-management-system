import { Head, Link } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create as createBlock, edit as editBlock } from '@/routes/blocks';
import { index as rooms } from '@/routes/rooms';

type Block = {
    id: number;
    name: string;
    code: string;
    rooms_count: number;
};

export default function BlocksIndex({
    blocks,
    canManage,
}: {
    blocks: Block[];
    canManage: boolean;
}) {
    return (
        <>
            <Head title="Blocks" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Blocks"
                        description="Review hall blocks and the rooms assigned to each one."
                    />

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={rooms()}>View rooms</Link>
                        </Button>
                        {canManage && (
                            <Button asChild>
                                <Link href={createBlock()}>
                                    <Plus />
                                    New block
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {blocks.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <Building2 className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No blocks yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create the first block to start defining the hall
                            structure.
                        </p>
                        {canManage && (
                            <Button asChild className="mt-4">
                                <Link href={createBlock()}>
                                    <Plus />
                                    New block
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {blocks.map((block) => (
                            <div
                                key={block.id}
                                className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {block.name}
                                        </p>
                                        <Badge
                                            variant="secondary"
                                            className="mt-1"
                                        >
                                            {block.code}
                                        </Badge>
                                    </div>
                                    <Building2 className="size-5 text-muted-foreground" />
                                </div>

                                <p className="mt-4 text-sm text-muted-foreground">
                                    {block.rooms_count}{' '}
                                    {block.rooms_count === 1 ? 'room' : 'rooms'}
                                </p>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={rooms()}>View rooms</Link>
                                    </Button>
                                    {canManage && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link href={editBlock(block)}>
                                                Edit block
                                            </Link>
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
