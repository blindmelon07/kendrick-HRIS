import { Head } from '@inertiajs/react';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Table,
    Td,
    Th,
} from '@/components/ui';
import { useRecordForm } from '@/hooks/use-record-form';
import departments from '@/routes/departments';
import type { EmployeeSummary } from '@/types';

type Department = {
    id: number;
    name: string;
    code: string;
    email: string | null;
    head_employee_id: number | null;
    head: EmployeeSummary | null;
    employees_count: number;
};

export default function DepartmentsIndex({
    departments: rows,
    employees,
}: {
    departments: Department[];
    employees: EmployeeSummary[];
}) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        { name: '', code: '', email: '', head_employee_id: '' },
        {
            store: () => departments.store.url(),
            update: (id) => departments.update.url(id),
            destroy: (id) => departments.destroy.url(id),
        },
    );

    return (
        <>
            <Head title="Departments" />
            <PageHeader
                title="Departments"
                description="The Dept. Head endorses filings from their department before HR's final approval. The email receives the Work Hours Summary for confirmation."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={rows.length === 0}
                        head={
                            <tr>
                                <Th>Code</Th>
                                <Th>Name</Th>
                                <Th>Dept. Head</Th>
                                <Th className="text-right">Employees</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {rows.map((department) => (
                            <tr key={department.id}>
                                <Td className="font-mono text-xs">
                                    {department.code}
                                </Td>
                                <Td>
                                    <div className="font-medium">
                                        {department.name}
                                    </div>
                                    {department.email && (
                                        <div className="text-xs text-ink-muted">
                                            {department.email}
                                        </div>
                                    )}
                                </Td>
                                <Td>
                                    {department.head ? (
                                        `${department.head.first_name} ${department.head.last_name}`
                                    ) : (
                                        <span className="text-ink-muted">
                                            Not set
                                        </span>
                                    )}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {department.employees_count}
                                </Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(department.id, {
                                                name: department.name,
                                                code: department.code,
                                                email: department.email ?? '',
                                                head_employee_id:
                                                    department.head_employee_id
                                                        ? String(
                                                              department.head_employee_id,
                                                          )
                                                        : '',
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => destroy(department.id)}
                                    >
                                        Delete
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit department' : 'New department'}>
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
                        <Field label="Email" error={form.errors.email}>
                            <Input
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Dept. Head"
                            error={form.errors.head_employee_id}
                        >
                            <Select
                                options={employees.map((employee) => ({
                                    value: String(employee.id),
                                    label: employee.full_name,
                                }))}
                                placeholder="None"
                                value={form.data.head_employee_id}
                                onChange={(event) =>
                                    form.setData(
                                        'head_employee_id',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Add department'}
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
