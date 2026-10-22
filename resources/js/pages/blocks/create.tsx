import { Head, Link, useForm } from '@inertiajs/react';
import BlockController from '@/actions/App/Http/Controllers/BlockController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as blocks } from '@/routes/blocks';

export default function CreateBlock() {
    const form = useForm({
        name: '',
        code: '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(BlockController.store.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="New block" />

            <div className="space-y-6 p-4">
                <Heading
                    title="New block"
                    description="Add a block to the hall structure."
                />

                <form
                    onSubmit={submit}
                    className="max-w-xl space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Block name</Label>
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
                        <Label htmlFor="code">Block code</Label>
                        <Input
                            id="code"
                            value={form.data.code}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                            placeholder="e.g. NORTH"
                            autoComplete="off"
                            aria-invalid={Boolean(form.errors.code)}
                        />
                        <InputError message={form.errors.code} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Creating...' : 'Create block'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={blocks()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
