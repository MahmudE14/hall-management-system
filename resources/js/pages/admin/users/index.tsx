import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type StudentProfile = {
    registration_number: string;
    department: string;
    session: string;
    phone: string;
    registration_status: string;
    residency_status: string;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_active: boolean;
    roles: string[];
    student_profile: StudentProfile | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Filters = {
    search?: string;
    role?: string;
    registration_status?: string;
};

export default function UsersIndex({
    users,
    filters,
    roles,
    registrationStatuses,
}: {
    users: Paginated<ManagedUser>;
    filters: Filters;
    roles: string[];
    registrationStatuses: string[];
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role ?? '');
    const [registrationStatus, setRegistrationStatus] = useState(
        filters.registration_status ?? '',
    );

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            UserController.index.url(),
            {
                search: search || undefined,
                role: role || undefined,
                registration_status: registrationStatus || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Users" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Users and roles"
                    description="Search identities, review assigned roles, and manage access."
                />

                <form
                    onSubmit={applyFilters}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-4 dark:border-sidebar-border"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Name, email, or registration number"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="role">Role</Label>
                        <select
                            id="role"
                            value={role}
                            onChange={(event) => setRole(event.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All roles</option>
                            {roles.map((roleName) => (
                                <option key={roleName} value={roleName}>
                                    {formatRole(roleName)}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="registration_status">
                            Registration status
                        </Label>
                        <select
                            id="registration_status"
                            value={registrationStatus}
                            onChange={(event) =>
                                setRegistrationStatus(event.target.value)
                            }
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All statuses</option>
                            {registrationStatuses.map((status) => (
                                <option key={status} value={status}>
                                    {formatRole(status)}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end">
                        <Button type="submit">Apply filters</Button>
                    </div>
                </form>

                {users.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No users match the selected filters.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-[48rem] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Identity
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Roles
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Registration
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {user.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {user.email}
                                            </p>
                                            <Badge
                                                variant={
                                                    user.is_active
                                                        ? 'outline'
                                                        : 'destructive'
                                                }
                                            >
                                                {user.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((roleName) => (
                                                    <Badge
                                                        key={roleName}
                                                        variant="secondary"
                                                    >
                                                        {formatRole(roleName)}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {user.student_profile ? (
                                                <>
                                                    <p>
                                                        {
                                                            user.student_profile
                                                                .registration_number
                                                        }
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {formatRole(
                                                            user.student_profile
                                                                .registration_status,
                                                        )}
                                                    </p>
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Staff account
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={UserController.edit.url(
                                                        user,
                                                    )}
                                                >
                                                    Manage
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={users.links} />
            </div>
        </>
    );
}

function formatRole(value: string) {
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
