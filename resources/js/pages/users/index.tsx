import { Head } from '@inertiajs/react';
import {
    Badge,
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
import { labelFor } from '@/lib/format';
import users from '@/routes/users';
import type { Option } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    employee: { id: number; employee_number: string } | null;
};

export default function UsersIndex({
    users: rows,
    roles,
}: {
    users: UserRow[];
    roles: Option[];
}) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        { name: '', email: '', role: 'hr_timekeeper', password: '' },
        {
            store: () => users.store.url(),
            update: (id) => users.update.url(id),
            destroy: (id) => users.destroy.url(id),
        },
    );

    return (
        <>
            <Head title="User Accounts" />
            <PageHeader
                title="User Accounts"
                description="HR, payroll and finance staff accounts. Employee kiosk logins are created from the employee record."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        head={
                            <tr>
                                <Th>Name</Th>
                                <Th>Email</Th>
                                <Th>Role</Th>
                                <Th>Employee</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {rows.map((user) => (
                            <tr key={user.id}>
                                <Td className="font-medium">{user.name}</Td>
                                <Td>{user.email}</Td>
                                <Td>
                                    <Badge
                                        tone={
                                            user.role === 'admin'
                                                ? 'blue'
                                                : 'gray'
                                        }
                                    >
                                        {labelFor(roles, user.role)}
                                    </Badge>
                                </Td>
                                <Td className="font-mono text-xs">
                                    {user.employee?.employee_number ?? '—'}
                                </Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(user.id, {
                                                name: user.name,
                                                email: user.email,
                                                role: user.role,
                                                password: '',
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            destroy(
                                                user.id,
                                                `Delete ${user.name}'s account?`,
                                            )
                                        }
                                    >
                                        Delete
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit account' : 'New account'}>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field label="Name" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
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
                        <Field label="Role" error={form.errors.role}>
                            <Select
                                options={roles}
                                value={form.data.role}
                                onChange={(event) =>
                                    form.setData('role', event.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label={editingId ? 'New password' : 'Password'}
                            error={form.errors.password}
                            hint={
                                editingId
                                    ? 'Leave blank to keep the current password.'
                                    : undefined
                            }
                        >
                            <Input
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                            />
                        </Field>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Create account'}
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
