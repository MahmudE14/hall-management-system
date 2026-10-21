import { Head, Link, useForm } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/users';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    student_profile: {
        registration_number: string;
        department: string;
        session: string;
        phone: string;
        registration_status: string;
        residency_status: string;
    } | null;
};

export default function EditUser({
    user,
    roles,
}: {
    user: ManagedUser;
    roles: string[];
}) {
    const form = useForm<{ roles: string[] }>({ roles: user.roles });

    function toggleRole(role: string, checked: boolean) {
        form.setData(
            'roles',
            checked
                ? [...form.data.roles, role]
                : form.data.roles.filter((item) => item !== role),
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.put(UserController.updateRoles.url(user), { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Manage ${user.name}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title={user.name}
                    description="Update documented role assignments for this identity."
                />

                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <p className="font-medium">{user.email}</p>
                    {user.student_profile && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {user.student_profile.registration_number} ·{' '}
                            {user.student_profile.department} ·{' '}
                            {user.student_profile.session} ·{' '}
                            {user.student_profile.registration_status}
                        </p>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {roles.map((role) => (
                            <div
                                key={role}
                                className="flex items-center gap-2 rounded-md border p-3"
                            >
                                <Checkbox
                                    id={`role-${role}`}
                                    checked={form.data.roles.includes(role)}
                                    onCheckedChange={(checked) =>
                                        toggleRole(role, checked === true)
                                    }
                                />
                                <Label htmlFor={`role-${role}`}>
                                    {formatRole(role)}
                                </Label>
                            </div>
                        ))}
                    </div>

                    <InputError message={form.errors.roles} />
                    <InputError message={form.errors['roles.0']} />

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Save roles
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={index()}>Back to users</Link>
                        </Button>
                    </div>
                </form>

                <div className="flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                        <Badge key={role} variant="secondary">
                            {formatRole(role)}
                        </Badge>
                    ))}
                </div>
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
