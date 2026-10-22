import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as studentsIndex, show as studentShow } from '@/routes/students';

type Student = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    registration_number: string;
    department: string;
    session: string;
    phone: string;
    registration_status: string;
    residency_status: string;
    block: { id: number; name: string; code: string } | null;
    current_room: { id: number; room_no: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Block = { id: number; name: string; code: string };

type Filters = {
    search?: string | null;
    block?: number | null;
    department?: string | null;
    session?: string | null;
    status?: string | null;
};

export default function StudentsIndex({
    students,
    filters,
    blocks,
    departments,
    sessions,
    statuses,
}: {
    students: Paginated<Student>;
    filters: Filters;
    blocks: Block[];
    departments: string[];
    sessions: string[];
    statuses: string[];
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [block, setBlock] = useState(
        filters.block ? String(filters.block) : '',
    );
    const [department, setDepartment] = useState(filters.department ?? '');
    const [session, setSession] = useState(filters.session ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            studentsIndex.url(),
            {
                search: search || undefined,
                block: block || undefined,
                department: department || undefined,
                session: session || undefined,
                status: status || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Students" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Students"
                    description="Search student records and filter by academic details and registration status."
                />

                <form
                    onSubmit={applyFilters}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-2 xl:grid-cols-6 dark:border-sidebar-border"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Name or registration number"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="block">Block</Label>
                        <select
                            id="block"
                            value={block}
                            onChange={(event) =>
                                setBlock(event.target.value)
                            }
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All blocks</option>
                            {blocks.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name} ({option.code})
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="department">Department</Label>
                        <select
                            id="department"
                            value={department}
                            onChange={(event) =>
                                setDepartment(event.target.value)
                            }
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All departments</option>
                            {departments.map((name) => (
                                <option key={name} value={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="session">Session</Label>
                        <select
                            id="session"
                            value={session}
                            onChange={(event) => setSession(event.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All sessions</option>
                            {sessions.map((name) => (
                                <option key={name} value={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </div>

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

                    <div className="flex items-end">
                        <Button type="submit" className="w-full xl:w-auto">
                            Apply filters
                        </Button>
                    </div>
                </form>

                {students.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No students match the selected filters.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-[52rem] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Student
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Registration
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Department
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Session
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Room
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {students.data.map((student) => (
                                    <tr
                                        key={student.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 align-top">
                                            <p className="font-medium">
                                                {student.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {student.email}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {student.registration_number}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {student.department}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {student.session}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <div className="flex flex-wrap gap-1">
                                                <Badge variant="secondary">
                                                    {formatValue(
                                                        student.registration_status,
                                                    )}
                                                </Badge>
                                                <Badge variant="outline">
                                                    {formatValue(
                                                        student.residency_status,
                                                    )}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {student.current_room ? (
                                                <div>
                                                    <p className="font-medium">
                                                        Room{' '}
                                                        {
                                                            student
                                                                .current_room
                                                                .room_no
                                                        }
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {student.block
                                                            ? `${student.block.name} (${student.block.code})`
                                                            : 'Block unassigned'}
                                                    </p>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not allocated
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={studentShow.url(
                                                        student.id,
                                                    )}
                                                >
                                                    View profile
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={students.links} />
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
