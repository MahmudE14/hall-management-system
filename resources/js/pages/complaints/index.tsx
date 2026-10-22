import { Head, Link, router } from '@inertiajs/react';
import { MessageSquare, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    create as createComplaint,
    index as complaintsIndex,
    show as showComplaint,
} from '@/routes/complaints';

type Assignee = {
    id: number;
    name: string;
};

type Complaint = {
    id: number;
    tracking_id: string;
    category: string;
    title: string;
    status: string;
    location: string | null;
    room_no: string | null;
    block: string | null;
    student_name: string | null;
    registration_number: string | null;
    days_open: number;
    is_overdue: boolean;
    created_at: string;
    assignee: Assignee | null;
    image_url: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Filters = {
    status?: string | null;
    category?: string | null;
};

export default function ComplaintsIndex({
    complaints,
    filters,
    statuses,
    categories,
    can_create: canCreate,
}: {
    complaints: Paginated<Complaint>;
    filters: Filters;
    statuses: string[];
    categories: string[];
    can_create: boolean;
}) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            complaintsIndex.url(),
            {
                status: status || undefined,
                category: category || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Complaints" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Complaints"
                        description="Track hall complaints, their progress, and their assigned handlers."
                    />

                    {canCreate && (
                        <Button asChild>
                            <Link href={createComplaint()}>
                                <Plus />
                                Submit a complaint
                            </Link>
                        </Button>
                    )}
                </div>

                <form
                    onSubmit={applyFilters}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-3 dark:border-sidebar-border"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All statuses</option>
                            {statuses.map((value) => (
                                <option key={value} value={value}>
                                    {formatValue(value)}
                                </option>
                            ))}
                        </select>
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

                    <div className="flex items-end gap-2">
                        <Button type="submit">Apply filters</Button>
                        {(filters.status || filters.category) && (
                            <Button asChild variant="outline">
                                <Link href={complaintsIndex()}>Clear</Link>
                            </Button>
                        )}
                    </div>
                </form>

                {complaints.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <MessageSquare className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-2 font-medium">No complaints found</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {filters.status || filters.category
                                ? 'No complaints match the selected filters.'
                                : 'No complaints have been submitted yet.'}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-[64rem] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Tracking ID
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Complaint
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Location
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Assignee
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Days open
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {complaints.data.map((complaint) => (
                                    <tr
                                        key={complaint.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 align-top">
                                            <p className="font-mono text-xs font-medium">
                                                {complaint.tracking_id}
                                            </p>
                                            <p className="mt-1 text-muted-foreground">
                                                {formatDate(
                                                    complaint.created_at,
                                                )}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <p className="font-medium">
                                                {complaint.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {formatValue(
                                                    complaint.category,
                                                )}
                                            </p>
                                            {complaint.student_name && (
                                                <p className="text-xs text-muted-foreground">
                                                    {complaint.student_name}
                                                    {complaint.registration_number
                                                        ? ` · ${complaint.registration_number}`
                                                        : ''}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <div className="flex flex-wrap gap-1">
                                                <Badge variant="secondary">
                                                    {formatValue(
                                                        complaint.status,
                                                    )}
                                                </Badge>
                                                {complaint.is_overdue && (
                                                    <Badge variant="destructive">
                                                        Overdue
                                                    </Badge>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {complaint.room_no ? (
                                                <div>
                                                    <p className="font-medium">
                                                        Room {complaint.room_no}
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {complaint.block ??
                                                            'Block unassigned'}
                                                    </p>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    {complaint.location ??
                                                        'Location not recorded'}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {complaint.assignee ? (
                                                complaint.assignee.name
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Unassigned
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {complaint.days_open}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={showComplaint(
                                                        complaint.id,
                                                    )}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={complaints.links} />
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
