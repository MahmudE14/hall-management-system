import { Head, Link, useForm } from '@inertiajs/react';
import {
    index as noticesIndex,
    store as storeNotice,
} from '@/actions/App/Http/Controllers/NoticeController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function CreateNotice({
    categories,
    statuses,
}: {
    categories: string[];
    statuses: string[];
}) {
    const form = useForm({
        title: '',
        body: '',
        category: categories[0] ?? '',
        status: 'draft',
        is_pinned: false,
        attachment: null as File | null,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(storeNotice.url(), {
            preserveScroll: true,
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Create notice" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Create notice"
                    description="Publish an announcement or save it as a draft for later publication."
                />

                <form
                    onSubmit={submit}
                    className="max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
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
                        <Label htmlFor="body">Body</Label>
                        <textarea
                            id="body"
                            rows={8}
                            value={form.data.body}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.body)}
                            className="flex min-h-40 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
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
                                        <SelectItem
                                            key={category}
                                            value={category}
                                        >
                                            {formatValue(category)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.category} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={form.data.status}
                                onValueChange={(value) =>
                                    form.setData('status', value)
                                }
                            >
                                <SelectTrigger
                                    id="status"
                                    aria-invalid={Boolean(form.errors.status)}
                                >
                                    <SelectValue placeholder="Select a status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {formatValue(status)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.status} />
                        </div>
                    </div>

                    <div className="flex items-start gap-3 rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                        <Checkbox
                            id="is_pinned"
                            checked={form.data.is_pinned}
                            onCheckedChange={(checked) =>
                                form.setData('is_pinned', checked === true)
                            }
                        />
                        <div>
                            <Label htmlFor="is_pinned">Pin this notice</Label>
                            <p className="text-sm text-muted-foreground">
                                Pinned notices appear before unpinned notices on
                                the public board.
                            </p>
                        </div>
                        <InputError message={form.errors.is_pinned} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="attachment">
                            PDF attachment (optional)
                        </Label>
                        <Input
                            id="attachment"
                            type="file"
                            accept="application/pdf,.pdf"
                            onChange={(event) =>
                                form.setData(
                                    'attachment',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                            aria-invalid={Boolean(form.errors.attachment)}
                        />
                        <p className="text-sm text-muted-foreground">
                            A valid PDF up to 5 MB.
                        </p>
                        <InputError message={form.errors.attachment} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Creating...' : 'Create notice'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={noticesIndex()}>Cancel</Link>
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
