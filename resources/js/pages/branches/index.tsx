import { Head } from '@inertiajs/react';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Table,
    Td,
    Th,
} from '@/components/ui';
import { useRecordForm } from '@/hooks/use-record-form';
import branches from '@/routes/branches';

type Branch = {
    id: number;
    name: string;
    code: string;
    employees_count: number;
};

export default function BranchesIndex({
    branches: rows,
}: {
    branches: Branch[];
}) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        { name: '', code: '' },
        {
            store: () => branches.store.url(),
            update: (id) => branches.update.url(id),
            destroy: (id) => branches.destroy.url(id),
        },
    );

    return (
        <>
            <Head title="Branches" />
            <PageHeader
                title="Branches"
                description="Company sites such as Trece and Tanza."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={rows.length === 0}
                        head={
                            <tr>
                                <Th>Code</Th>
                                <Th>Name</Th>
                                <Th className="text-right">Employees</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {rows.map((branch) => (
                            <tr key={branch.id}>
                                <Td className="font-mono text-xs">
                                    {branch.code}
                                </Td>
                                <Td className="font-medium">{branch.name}</Td>
                                <Td className="text-right tabular-nums">
                                    {branch.employees_count}
                                </Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(branch.id, {
                                                name: branch.name,
                                                code: branch.code,
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => destroy(branch.id)}
                                    >
                                        Delete
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit branch' : 'New branch'}>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field label="Name" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Code" error={form.errors.code}>
                            <Input
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData('code', event.target.value)
                                }
                            />
                        </Field>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Add branch'}
                            </Button>
                            {editingId && (
                                <Button variant="secondary" onClick={cancel}>
                                    Cancel
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>
        </>
    );
}
