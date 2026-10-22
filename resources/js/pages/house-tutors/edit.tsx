import { Head, Link, router, useForm } from '@inertiajs/react';
import TutorController from '@/actions/App/Http/Controllers/TutorController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as houseTutors } from '@/routes/house-tutors';

type Block = {
    id: number;
    name: string;
    code: string;
};

type Tutor = {
    id: number;
    name: string;
    email: string;
    designation: string;
    department: string;
    phone: string;
    is_active: boolean;
    block_ids: number[];
};

export default function EditHouseTutor({
    tutor,
    blocks,
}: {
    tutor: Tutor;
    blocks: Block[];
}) {
    const form = useForm({
        name: tutor.name,
        email: tutor.email,
        designation: tutor.designation,
        department: tutor.department,
        phone: tutor.phone,
        block_ids: tutor.block_ids,
    });

    function toggleBlock(blockId: number, checked: boolean) {
        form.setData(
            'block_ids',
            checked
                ? [...form.data.block_ids, blockId]
                : form.data.block_ids.filter((id) => id !== blockId),
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.put(TutorController.update.url(tutor), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={`Edit ${tutor.name}`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={tutor.name}
                        description="Update the tutor record and block assignments."
                    />
                    <Badge
                        variant={tutor.is_active ? 'outline' : 'destructive'}
                    >
                        {tutor.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>

                <form
                    onSubmit={submit}
                    className="max-w-2xl space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.name)}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.email)}
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="designation">Designation</Label>
                            <Input
                                id="designation"
                                value={form.data.designation}
                                onChange={(event) =>
                                    form.setData(
                                        'designation',
                                        event.target.value,
                                    )
                                }
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.designation)}
                            />
                            <InputError message={form.errors.designation} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="department">Department</Label>
                            <Input
                                id="department"
                                value={form.data.department}
                                onChange={(event) =>
                                    form.setData(
                                        'department',
                                        event.target.value,
                                    )
                                }
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.department)}
                            />
                            <InputError message={form.errors.department} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                                placeholder="01XXXXXXXXX"
                                autoComplete="off"
                                aria-invalid={Boolean(form.errors.phone)}
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Assigned blocks</Label>
                        {blocks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No blocks are available yet. Create a block in
                                the hall structure first.
                            </p>
                        ) : (
                            <div className="grid gap-2 sm:grid-cols-2">
                                {blocks.map((block) => (
                                    <div
                                        key={block.id}
                                        className="flex items-center gap-2 rounded-md border p-3"
                                    >
                                        <Checkbox
                                            id={`block-${block.id}`}
                                            checked={form.data.block_ids.includes(
                                                block.id,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleBlock(
                                                    block.id,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor={`block-${block.id}`}>
                                            {block.name} ({block.code})
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        )}
                        <InputError message={form.errors.block_ids} />
                        <InputError message={form.errors['block_ids.0']} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save changes'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={houseTutors()}>Cancel</Link>
                        </Button>
                        {tutor.is_active && (
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() =>
                                    router.patch(
                                        TutorController.deactivate.url(tutor),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Deactivate tutor
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}
