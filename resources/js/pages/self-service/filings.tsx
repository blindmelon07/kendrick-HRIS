import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FilingDetails, FilingStatusBadge } from '@/components/filing-summary';
import {
    Button,
    Card,
    Checkbox,
    Field,
    Input,
    PageHeader,
    Pagination,
    Select,
    Table,
    Td,
    Th,
    Textarea,
} from '@/components/ui';
import { formatTime } from '@/lib/format';
import myFilings from '@/routes/my/filings';
import type { Filing, Option, Paginated, Shift } from '@/types';

type Props = {
    filings: Paginated<Filing>;
    types: Option[];
    shifts: Shift[];
};

const guidance: Record<string, string> = {
    unaccounted:
        'Missing time in or out (field work, approved WFH, forgot to tap). Attach proof to your Dept. Head when required.',
    leave: 'Leave request. Half-day leave credits 0.5 day worked + 0.5 day leave; extra hours beyond the half day are not paid.',
    overtime:
        'Overtime rendered beyond your shift. Per Biyahe drivers cannot claim OT on a trip day.',
    early_out:
        'Leaving before the end of your shift. Undertime is still deducted.',
    shift_change: 'Work a different schedule for the day.',
};

export default function MyFilings({ filings, types, shifts }: Props) {
    const form = useForm({
        type: 'unaccounted',
        date_from: '',
        date_to: '',
        time_from: '',
        time_to: '',
        is_half_day: false,
        with_pay: true,
        shift_id: '',
        reason: '',
    });
    const { data, setData, errors } = form;

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(myFilings.store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function withdraw(filing: Filing) {
        if (window.confirm('Withdraw this filing?')) {
            router.delete(myFilings.destroy.url(filing.id), {
                preserveScroll: true,
            });
        }
    }

    const isLeave = data.type === 'leave';
    const multiDay = isLeave && !data.is_half_day;

    return (
        <>
            <Head title="My Filings" />
            <PageHeader
                title="My Filings"
                description="Requests go to your Dept. Head first, then to the HR Time Keeper. Submit before the 3rd and 18th, 12:00 NN, to make the payroll cut-off."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card title="New filing" className="lg:order-2">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field
                            label="Type"
                            error={errors.type}
                            hint={guidance[data.type]}
                        >
                            <Select
                                options={types}
                                value={data.type}
                                onChange={(event) =>
                                    setData('type', event.target.value)
                                }
                            />
                        </Field>

                        <div
                            className={multiDay ? 'grid grid-cols-2 gap-3' : ''}
                        >
                            <Field
                                label={multiDay ? 'From' : 'Date'}
                                error={errors.date_from}
                            >
                                <Input
                                    type="date"
                                    value={data.date_from}
                                    onChange={(event) =>
                                        setData('date_from', event.target.value)
                                    }
                                />
                            </Field>
                            {multiDay && (
                                <Field label="To" error={errors.date_to}>
                                    <Input
                                        type="date"
                                        value={data.date_to}
                                        onChange={(event) =>
                                            setData(
                                                'date_to',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                            )}
                        </div>

                        {isLeave && (
                            <div className="flex flex-col gap-2">
                                <Checkbox
                                    label="Half day"
                                    checked={data.is_half_day}
                                    onChange={(event) =>
                                        setData(
                                            'is_half_day',
                                            event.target.checked,
                                        )
                                    }
                                />
                                <Checkbox
                                    label="With pay (subject to HR approval)"
                                    checked={data.with_pay}
                                    onChange={(event) =>
                                        setData(
                                            'with_pay',
                                            event.target.checked,
                                        )
                                    }
                                />
                            </div>
                        )}

                        {(data.type === 'unaccounted' ||
                            data.type === 'overtime') && (
                            <div className="grid grid-cols-2 gap-3">
                                <Field
                                    label={
                                        data.type === 'unaccounted'
                                            ? 'Time in'
                                            : 'Start'
                                    }
                                    error={errors.time_from}
                                >
                                    <Input
                                        type="time"
                                        value={data.time_from}
                                        onChange={(event) =>
                                            setData(
                                                'time_from',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label={
                                        data.type === 'unaccounted'
                                            ? 'Time out'
                                            : 'End'
                                    }
                                    error={errors.time_to}
                                >
                                    <Input
                                        type="time"
                                        value={data.time_to}
                                        onChange={(event) =>
                                            setData(
                                                'time_to',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                        )}

                        {data.type === 'early_out' && (
                            <Field label="Time out" error={errors.time_from}>
                                <Input
                                    type="time"
                                    value={data.time_from}
                                    onChange={(event) =>
                                        setData('time_from', event.target.value)
                                    }
                                />
                            </Field>
                        )}

                        {data.type === 'shift_change' && (
                            <Field label="Shift" error={errors.shift_id}>
                                <Select
                                    options={shifts.map((shift) => ({
                                        value: String(shift.id),
                                        label: `${shift.name} (${formatTime(shift.start_time)}–${formatTime(shift.end_time)})`,
                                    }))}
                                    placeholder="Select…"
                                    value={data.shift_id}
                                    onChange={(event) =>
                                        setData('shift_id', event.target.value)
                                    }
                                />
                            </Field>
                        )}

                        <Field label="Reason" error={errors.reason}>
                            <Textarea
                                value={data.reason}
                                onChange={(event) =>
                                    setData('reason', event.target.value)
                                }
                            />
                        </Field>

                        <Button type="submit" disabled={form.processing}>
                            Submit filing
                        </Button>
                    </form>
                </Card>

                <Card title="My requests" className="lg:col-span-2">
                    <Table
                        isEmpty={filings.data.length === 0}
                        empty="You have not filed anything yet."
                        head={
                            <tr>
                                <Th>Request</Th>
                                <Th>Reason</Th>
                                <Th>Status</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {filings.data.map((filing) => (
                            <tr key={filing.id}>
                                <Td>
                                    <FilingDetails filing={filing} />
                                </Td>
                                <Td className="max-w-56 text-xs text-ink-muted">
                                    {filing.reason}
                                    {filing.remarks && (
                                        <div className="mt-1 text-ink">
                                            Remarks: {filing.remarks}
                                        </div>
                                    )}
                                </Td>
                                <Td>
                                    <FilingStatusBadge status={filing.status} />
                                </Td>
                                <Td className="text-right">
                                    {filing.status === 'pending' && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => withdraw(filing)}
                                        >
                                            Withdraw
                                        </Button>
                                    )}
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
            </div>
            <Pagination page={filings} />
        </>
    );
}
