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
import { formatTime, weekdays } from '@/lib/format';
import { cn } from '@/lib/utils';
import shifts from '@/routes/shifts';
import type { Shift } from '@/types';

export default function ShiftsIndex({ shifts: rows }: { shifts: Shift[] }) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        {
            name: '',
            start_time: '08:00',
            end_time: '17:00',
            break_minutes: 60,
            work_days: [1, 2, 3, 4, 5, 6] as number[],
        },
        {
            store: () => shifts.store.url(),
            update: (id) => shifts.update.url(id),
            destroy: (id) => shifts.destroy.url(id),
        },
    );

    function toggleDay(day: number) {
        const days = form.data.work_days;

        form.setData(
            'work_days',
            days.includes(day)
                ? days.filter((value) => value !== day)
                : [...days, day].sort((a, b) => a - b),
        );
    }

    return (
        <>
            <Head title="Shifts" />
            <PageHeader
                title="Shifts"
                description="Work schedules used to compute lates, undertime and half days. Half day = half of the paid hours."
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={rows.length === 0}
                        head={
                            <tr>
                                <Th>Name</Th>
                                <Th>Schedule</Th>
                                <Th>Break</Th>
                                <Th>Work days</Th>
                                <Th className="text-right">Employees</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {rows.map((shift) => (
                            <tr key={shift.id}>
                                <Td className="font-medium">{shift.name}</Td>
                                <Td className="tabular-nums">
                                    {formatTime(shift.start_time)}–
                                    {formatTime(shift.end_time)}
                                </Td>
                                <Td>{shift.break_minutes} min</Td>
                                <Td className="text-xs text-ink-muted">
                                    {weekdays
                                        .filter((day) =>
                                            shift.work_days.includes(day.value),
                                        )
                                        .map((day) => day.label)
                                        .join(' ')}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {shift.employees_count}
                                </Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(shift.id, {
                                                name: shift.name,
                                                start_time: formatTime(
                                                    shift.start_time,
                                                ),
                                                end_time: formatTime(
                                                    shift.end_time,
                                                ),
                                                break_minutes:
                                                    shift.break_minutes,
                                                work_days: shift.work_days,
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => destroy(shift.id)}
                                    >
                                        Delete
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit shift' : 'New shift'}>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field label="Name" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Start" error={form.errors.start_time}>
                                <Input
                                    type="time"
                                    value={form.data.start_time}
                                    onChange={(event) =>
                                        form.setData(
                                            'start_time',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field label="End" error={form.errors.end_time}>
                                <Input
                                    type="time"
                                    value={form.data.end_time}
                                    onChange={(event) =>
                                        form.setData(
                                            'end_time',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field
                            label="Unpaid break (minutes)"
                            error={form.errors.break_minutes}
                        >
                            <Input
                                type="number"
                                min="0"
                                value={form.data.break_minutes}
                                onChange={(event) =>
                                    form.setData(
                                        'break_minutes',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </Field>
                        <div className="flex flex-col gap-1.5">
                            <span className="text-sm font-medium text-ink">
                                Work days
                            </span>
                            <div className="flex flex-wrap gap-1">
                                {weekdays.map((day) => (
                                    <button
                                        key={day.value}
                                        type="button"
                                        onClick={() => toggleDay(day.value)}
                                        className={cn(
                                            'rounded-md border px-2.5 py-1 text-xs font-medium',
                                            form.data.work_days.includes(
                                                day.value,
                                            )
                                                ? 'border-brand bg-brand-soft text-brand'
                                                : 'border-line text-ink-muted',
                                        )}
                                    >
                                        {day.label}
                                    </button>
                                ))}
                            </div>
                            {form.errors.work_days && (
                                <span className="text-xs text-red-600">
                                    {form.errors.work_days}
                                </span>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Add shift'}
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
