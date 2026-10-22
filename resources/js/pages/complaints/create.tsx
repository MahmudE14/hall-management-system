import { Head, Link, useForm } from '@inertiajs/react';
import ComplaintController from '@/actions/App/Http/Controllers/ComplaintController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { dashboard } from '@/routes';

type CurrentRoom = {
    room_no: string;
    block: string | null;
};

export default function CreateComplaint({
    currentRoom,
    requiresLocation,
    categories,
}: {
    currentRoom: CurrentRoom | null;
    requiresLocation: boolean;
    categories: string[];
}) {
    const form = useForm({
        category: categories[0] ?? '',
        title: '',
        description: '',
        location: '',
        image: null as File | null,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(ComplaintController.store.url(), {
            preserveScroll: true,
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Submit a complaint" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Submit a complaint"
                    description="Report a hall issue and receive a tracking ID for follow-up."
                />

                <form
                    onSubmit={submit}
                    className="max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    {currentRoom ? (
                        <div className="rounded-md border border-sidebar-border/70 bg-muted/40 p-3 text-sm dark:border-sidebar-border">
                            <p className="font-medium">
                                Room {currentRoom.room_no}
                            </p>
                            {currentRoom.block && (
                                <p className="text-muted-foreground">
                                    {currentRoom.block}
                                </p>
                            )}
                            <p className="mt-1 text-muted-foreground">
                                Your current room will be attached to this
                                complaint automatically.
                            </p>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            You are attached to the hall, so please provide the
                            location of the issue.
                        </p>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <Select
                            value={form.data.category}
                            onValueChange={(value) =>
                                form.setData('category', value)
                            }
                        >
                            <SelectTrigger
                                id="category"
                                aria-invalid={Boolean(form.errors.category)}
                            >
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem key={category} value={category}>
                                        {formatValue(category)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.category} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            autoComplete="off"
                            aria-invalid={Boolean(form.errors.title)}
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            rows={5}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.description)}
                            className="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    {requiresLocation && (
                        <div className="grid gap-2">
                            <Label htmlFor="location">Location</Label>
                            <Input
                                id="location"
                                value={form.data.location}
                                onChange={(event) =>
                                    form.setData('location', event.target.value)
                                }
                                placeholder="Block, floor, or area"
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.location)}
                            />
                            <InputError message={form.errors.location} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="image">Photo (optional)</Label>
                        <Input
                            id="image"
                            type="file"
                            accept="image/jpeg,image/png"
                            onChange={(event) =>
                                form.setData(
                                    'image',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                            aria-invalid={Boolean(form.errors.image)}
                        />
                        <p className="text-sm text-muted-foreground">
                            JPG or PNG, up to 2 MB.
                        </p>
                        <InputError message={form.errors.image} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? 'Submitting...'
                                : 'Submit complaint'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={dashboard()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

function formatValue(value: string) {
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}
