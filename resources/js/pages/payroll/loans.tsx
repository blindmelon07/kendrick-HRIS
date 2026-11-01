import { Head, Link } from '@inertiajs/react';
import {
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Pagination,
    Select,
    Table,
    Td,
    Th,
    buttonClasses,
} from '@/components/ui';
import { useRecordForm } from '@/hooks/use-record-form';
import { formatDate, labelFor, peso } from '@/lib/format';
import loans from '@/routes/payroll/loans';
import type { EmployeeSummary, Loan, Option, Paginated } from '@/types';

type Props = {
    loans: Paginated<Loan>;
    showPaid: boolean;
    types: Option[];
    employees: EmployeeSummary[];
};

export default function Loans({
    loans: page,
    showPaid,
    types,
    employees,
}: Props) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        {
            employee_id: '',
            type: 'sss',
            reference_number: '',
            principal: '',
            amortization: '',
            balance: '',
            start_date: '',
        },
        {
            store: () => loans.store.url(),
            update: (id) => loans.update.url(id),
            destroy: (id) => loans.destroy.url(id),
        },
    );

    return (
        <>
            <Head title="Loans" />
            <PageHeader
                title="Government Loans Monitoring"
                description="Active SSS, Pag-IBIG and company loans. Amortizations are deducted on the 2nd-half payroll; balances update when the payroll is finalized."
                actions={
                    <Link
                        href={loans.index.url({
                            query: showPaid ? {} : { paid: 1 },
                        })}
                        className={buttonClasses('secondary')}
                    >
                        {showPaid ? 'Outstanding only' : 'Include fully paid'}
                    </Link>
                }
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={page.data.length === 0}
                        empty="No loans on file."
                        head={
                            <tr>
                                <Th>Employee</Th>
                                <Th>Loan</Th>
                                <Th className="text-right">Amortization</Th>
                                <Th className="text-right">Balance</Th>
                                <Th>Start</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {page.data.map((loan) => (
                            <tr key={loan.id}>
                                <Td>
                                    <div className="font-medium">
                                        {loan.employee?.full_name}
                                    </div>
                                    <div className="font-mono text-xs text-ink-muted">
                                        {loan.employee?.employee_number}
                                    </div>
                                </Td>
                                <Td>
                                    {labelFor(types, loan.type)}
                                    {loan.reference_number && (
                                        <div className="text-xs text-ink-muted">
                                            {loan.reference_number}
                                        </div>
                                    )}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {peso(loan.amortization)}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {peso(loan.balance)}
                                    <div className="text-xs text-ink-muted">
                                        of {peso(loan.principal)}
                                    </div>
                                </Td>
                                <Td>{formatDate(loan.start_date)}</Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(loan.id, {
                                                employee_id: String(
                                                    loan.employee_id,
                                                ),
                                                type: loan.type,
                                                reference_number:
                                                    loan.reference_number ?? '',
                                                principal: loan.principal,
                                                amortization: loan.amortization,
                                                balance: loan.balance,
                                                start_date: loan.start_date,
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
                                                loan.id,
                                                'Remove this loan?',
                                            )
                                        }
                                    >
                                        Remove
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit loan' : 'New loan'}>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field label="Employee" error={form.errors.employee_id}>
                            <Select
                                options={employees.map((employee) => ({
                                    value: String(employee.id),
                                    label: `${employee.employee_number} · ${employee.full_name}`,
                                }))}
                                placeholder="Select…"
                                value={form.data.employee_id}
                                onChange={(event) =>
                                    form.setData(
                                        'employee_id',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Type" error={form.errors.type}>
                                <Select
                                    options={types}
                                    value={form.data.type}
                                    onChange={(event) =>
                                        form.setData('type', event.target.value)
                                    }
                                />
                            </Field>
                            <Field
                                label="Reference no."
                                error={form.errors.reference_number}
                            >
                                <Input
                                    value={form.data.reference_number}
                                    onChange={(event) =>
                                        form.setData(
                                            'reference_number',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Principal"
                                error={form.errors.principal}
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={form.data.principal}
                                    onChange={(event) =>
                                        form.setData(
                                            'principal',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Amortization"
                                error={form.errors.amortization}
                                hint="Per 2nd-half payroll"
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={form.data.amortization}
                                    onChange={(event) =>
                                        form.setData(
                                            'amortization',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Balance"
                                error={form.errors.balance}
                                hint={
                                    editingId
                                        ? undefined
                                        : 'Defaults to principal'
                                }
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={form.data.balance}
                                    onChange={(event) =>
                                        form.setData(
                                            'balance',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="First deduction"
                                error={form.errors.start_date}
                            >
                                <Input
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'start_date',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Add loan'}
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
            <Pagination page={page} />
        </>
    );
}
