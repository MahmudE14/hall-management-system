import { Head, Link, router } from '@inertiajs/react';
import { Megaphone, Paperclip, Pin } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as noticesIndex, show as showNotice } from '@/routes/notices';

type PublicNotice = {
    id: number;
    title: string;
    category: string;
    is_pinned: boolean;
    published_at: string | null;
    has_attachment: boolean;
    creator: {
        id: number;
        name: string;
    };
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Filters = {
    search?: string | null;
    category?: string | null;
};

export default function PublicNoticesIndex({
    notices,
    filters,
    categories,
}: {
    notices: Paginated<PublicNotice>;
    filters: Filters;
    categories: string[];
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            noticesIndex.url(),
            {
                search: search || undefined,
                category: category || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Notice board" />

            <div className="space-y-6">
                <Heading
                    title="Notice board"
                    description="Official announcements from the Provost Office, Hall Office, and clubs."
                />

                <form
                    onSubmit={applyFilters}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-3 dark:border-sidebar-border"
                >
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="search">Search notices</Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by title"
                            autoComplete="off"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <select
                            id="category"
                            value={category}
                            onChange={(event) =>
                                setCategory(event.target.value)
                            }
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All categories</option>
                            {categories.map((value) => (
                                <option key={value} value={value}>
                                    {formatValue(value)}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end gap-2 md:col-span-3">
                        <Button type="submit">Search notices</Button>
                        {(filters.search || filters.category) && (
                            <Button asChild variant="outline">
                                <Link href={noticesIndex()}>Clear</Link>
                            </Button>
                        )}
                    </div>
                </form>

                {notices.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <Megaphone className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No notices found</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {filters.search || filters.category
                                ? 'No published notices match the selected filters.'
                                : 'No notices have been published yet.'}
                        </p>
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {notices.data.map((notice) => (
                            <li
                                key={notice.id}
                                className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        {formatValue(notice.category)}
                                    </Badge>
                                    {notice.is_pinned && (
                                        <Badge variant="secondary">
                                            <Pin className="size-3" />
                                            Pinned
                                        </Badge>
                                    )}
                                    {notice.has_attachment && (
                                        <Badge variant="outline">
                                            <Paperclip className="size-3" />
                                            PDF
                                        </Badge>
                                    )}
                                </div>

                                <h2 className="mt-3 text-lg font-semibold">
                                    <Link
                                        href={showNotice(notice.id)}
                                        className="underline-offset-4 hover:underline"
                                    >
                                        {notice.title}
                                    </Link>
                                </h2>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    {notice.published_at
                                        ? `Published ${formatDate(
                                              notice.published_at,
                                          )}`
                                        : 'Published'}
                                    {' · '}
                                    {notice.creator.name}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={notices.links} />
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

function formatDate(value: string) {
    return new Date(value).toLocaleDateString();
}

function Pagination({ links }: { links: Paginated<unknown>['links'] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        className={`rounded-md border px-3 py-1 text-sm ${
                            link.active ? 'bg-muted font-medium' : ''
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : null,
            )}
        </div>
    );
}
