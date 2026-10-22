import { Head, Link } from '@inertiajs/react';
import { Megaphone, Paperclip, Pin, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create as createNotice } from '@/actions/App/Http/Controllers/NoticeController';

type NoticeRow = {
    id: number;
    title: string;
    category: string;
    status: string;
    is_pinned: boolean;
    published_at: string | null;
    created_at: string;
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

export default function AdminNoticesIndex({
    notices,
    can_create: canCreate,
}: {
    notices: Paginated<NoticeRow>;
    can_create: boolean;
}) {
    return (
        <>
            <Head title="Notices" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Notices"
                        description="Create hall announcements and review their publication state."
                    />

                    {canCreate && (
                        <Button asChild>
                            <Link href={createNotice()}>
                                <Plus />
                                Create notice
                            </Link>
                        </Button>
                    )}
                </div>

                {notices.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <Megaphone className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No notices yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {canCreate
                                ? 'Create the first notice to publish it on the public board.'
                                : 'No notices have been created for your account.'}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-[56rem] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Notice
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Category
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Published
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Created
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {notices.data.map((notice) => (
                                    <tr
                                        key={notice.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 align-top">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {notice.title}
                                                </p>
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
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {notice.creator.name}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {formatValue(notice.category)}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <Badge
                                                variant={
                                                    notice.status ===
                                                    'published'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {formatValue(notice.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {notice.published_at
                                                ? formatDateTime(
                                                      notice.published_at,
                                                  )
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {formatDateTime(notice.created_at)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
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

function formatDateTime(value: string) {
    return new Date(value).toLocaleString();
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
