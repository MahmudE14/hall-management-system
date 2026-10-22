import { Head, Link, useForm } from '@inertiajs/react';
import TutorController from '@/actions/App/Http/Controllers/TutorController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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

export default function CreateHouseTutor({ blocks }: { blocks: Block[] }) {
    const form = useForm({
        name: '',
        email: '',
        designation: '',
        department: '',
        phone: '',
        block_ids: [] as number[],
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

        form.post(TutorController.store.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="New tutor" />

            <div className="space-y-6 p-4">
                <Heading
                    title="New tutor"
                    description="Create a tutor record and assign the blocks they oversee."
                />

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
                            {form.processing ? 'Creating...' : 'Create tutor'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={houseTutors()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
