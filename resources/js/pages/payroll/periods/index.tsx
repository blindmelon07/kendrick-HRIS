import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    Badge,
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
    statusTone,
} from '@/components/ui';
import { formatDate, peso } from '@/lib/format';
import periods from '@/routes/payroll/periods';
import type { Option, Paginated, PayrollPeriod } from '@/types';

type Props = { periods: Paginated<PayrollPeriod>; halves: Option[] };

export default function PayrollPeriodsIndex({ periods: page, halves }: Props) {
    const form = useForm({
        start_date: '',
        end_date: '',
        pay_date: '',
        half: 'first',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(periods.store.url());
    }

    return (
        <>
            <Head title="Payroll Periods" />
            <PageHeader
                title="Payroll Periods"
                description="Semi-monthly cut-offs. Government contributions are deducted on the 1st half; SSS / Pag-IBIG loans on the 2nd half."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={page.data.length === 0}
                        empty="No payroll periods yet. Add the first cut-off."
                        head={
                            <tr>
                                <Th>Cut-off</Th>
                                <Th>Half</Th>
                                <Th>Pay date</Th>
                                <Th className="text-right">Employees</Th>
                                <Th className="text-right">Net pay</Th>
                                <Th>Status</Th>
                            </tr>
                        }
                    >
                        {page.data.map((period) => (
                            <tr
                                key={period.id}
                                className="hover:bg-surface-muted"
                            >
                                <Td>
                                    <Link
                                        href={periods.show.url(period.id)}
                                        className="font-medium text-brand hover:underline"
                                    >
                                        {period.label}
                                    </Link>
                                </Td>
                                <Td>
                                    {period.half === 'first' ? '1st' : '2nd'}
                                </Td>
                                <Td>{formatDate(period.pay_date)}</Td>
                                <Td className="text-right tabular-nums">
                                    {period.payslips_count}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {peso(period.payslips_sum_net_pay)}
                                </Td>
                                <Td>
                                    <Badge tone={statusTone[period.status]}>
                                        {period.status}
                                    </Badge>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title="New cut-off">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Start" error={form.errors.start_date}>
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
                            <Field label="End" error={form.errors.end_date}>
                                <Input
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'end_date',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field label="Pay date" error={form.errors.pay_date}>
                            <Input
                                type="date"
                                value={form.data.pay_date}
                                onChange={(event) =>
                                    form.setData('pay_date', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Payroll half" error={form.errors.half}>
                            <Select
                                options={halves}
                                value={form.data.half}
                                onChange={(event) =>
                                    form.setData('half', event.target.value)
                                }
                            />
                        </Field>
                        <Button type="submit" disabled={form.processing}>
                            Create period
                        </Button>
                    </form>
                </Card>
            </div>
            <Pagination page={page} />
        </>
    );
}
