import { Head, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import DateRangeFilter from '@/components/date-range-filter';
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
} from '@/components/ui';
import { formatDate, formatTime } from '@/lib/format';
import timeLogs from '@/routes/timekeeping/time-logs';
import type { EmployeeSummary, Paginated } from '@/types';

type TimeLog = {
    id: number;
    logged_at: string;
    source: string;
    employee: EmployeeSummary;
};

type Props = {
    logs: Paginated<TimeLog>;
    filters: { from: string; to: string; search: string };
    employees: EmployeeSummary[];
};

export default function TimeLogs({ logs, filters, employees }: Props) {
    const { flash } = usePage();
    const [search, setSearch] = useState(filters.search);

    const upload = useForm<{ file: File | null }>({ file: null });
    const manual = useForm({ employee_id: '', logged_at: '' });

    function submitUpload(event: FormEvent) {
        event.preventDefault();
        upload.post(timeLogs.import.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.reset(),
        });
    }

    function submitManual(event: FormEvent) {
        event.preventDefault();
        manual.post(timeLogs.store.url(), {
            preserveScroll: true,
            onSuccess: () => manual.reset('logged_at'),
        });
    }

    function remove(log: TimeLog) {
        if (window.confirm('Remove this punch?')) {
            router.delete(timeLogs.destroy.url(log.id), {
                preserveScroll: true,
            });
        }
    }

    const importResult = flash.importResult;

    return (
        <>
            <Head title="Time Logs" />
            <PageHeader
                title="Time Logs"
                description="Raw punches from the biometric ID system. Upload the BIS export every week, and encode Bundy Clock entries manually when the system is down."
            />

            <div className="grid gap-6 lg:grid-cols-2">
                <Card
                    title="Upload BIS time logs"
                    description="CSV with columns employee_number, date, time (or employee_number, logged_at). Duplicate punches are skipped."
                >
                    <form
                        onSubmit={submitUpload}
                        className="flex flex-col gap-3"
                    >
                        <Field label="CSV file" error={upload.errors.file}>
                            <Input
                                type="file"
                                accept=".csv,text/csv"
                                onChange={(event) =>
                                    upload.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </Field>
                        <div>
                            <Button
                                type="submit"
                                disabled={
                                    upload.processing || !upload.data.file
                                }
                            >
                                {upload.processing
                                    ? 'Uploading…'
                                    : 'Upload logs'}
                            </Button>
                        </div>
                        {importResult &&
                            (importResult.unknown_employees.length > 0 ||
                                importResult.invalid_rows.length > 0) && (
                                <div className="rounded-md border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-ink">
                                    {importResult.unknown_employees.length >
                                        0 && (
                                        <p>
                                            Unknown employee numbers:{' '}
                                            {importResult.unknown_employees.join(
                                                ', ',
                                            )}
                                        </p>
                                    )}
                                    {importResult.invalid_rows.length > 0 && (
                                        <p>
                                            Unreadable lines:{' '}
                                            {importResult.invalid_rows.join(
                                                ', ',
                                            )}
                                        </p>
                                    )}
                                </div>
                            )}
                    </form>
                </Card>

                <Card
                    title="Bundy Clock entry"
                    description="Manual encoding of punches recorded on the Bundy Clock. No Unaccounted form needed."
                >
                    <form
                        onSubmit={submitManual}
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        <Field
                            label="Employee"
                            error={manual.errors.employee_id}
                            className="sm:col-span-2"
                        >
                            <Select
                                options={employees.map((employee) => ({
                                    value: String(employee.id),
                                    label: `${employee.employee_number} · ${employee.full_name}`,
                                }))}
                                placeholder="Select employee…"
                                value={manual.data.employee_id}
                                onChange={(event) =>
                                    manual.setData(
                                        'employee_id',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Date and time"
                            error={manual.errors.logged_at}
                        >
                            <Input
                                type="datetime-local"
                                value={manual.data.logged_at}
                                onChange={(event) =>
                                    manual.setData(
                                        'logged_at',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <div className="flex items-end">
                            <Button type="submit" disabled={manual.processing}>
                                Record punch
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <Card className="mt-6" title="Punches">
                <div className="mb-5">
                    <DateRangeFilter
                        url={timeLogs.index.url()}
                        from={filters.from}
                        to={filters.to}
                        extra={{ search }}
                    >
                        <label className="flex flex-col gap-1 text-xs text-ink-muted">
                            Employee
                            <Input
                                placeholder="Name or employee no."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="w-56"
                            />
                        </label>
                    </DateRangeFilter>
                </div>
                <Table
                    isEmpty={logs.data.length === 0}
                    empty="No punches in this range."
                    head={
                        <tr>
                            <Th>Employee</Th>
                            <Th>Date</Th>
                            <Th>Time</Th>
                            <Th>Source</Th>
                            <Th />
                        </tr>
                    }
                >
                    {logs.data.map((log) => {
                        const [date, time] = log.logged_at.split(' ');

                        return (
                            <tr key={log.id}>
                                <Td>
                                    <div className="font-medium">
                                        {log.employee.full_name}
                                    </div>
                                    <div className="font-mono text-xs text-ink-muted">
                                        {log.employee.employee_number}
                                    </div>
                                </Td>
                                <Td>{formatDate(date)}</Td>
                                <Td className="tabular-nums">
                                    {formatTime(time)}
                                </Td>
                                <Td>
                                    <Badge
                                        tone={
                                            log.source === 'biometric'
                                                ? 'gray'
                                                : 'amber'
                                        }
                                    >
                                        {log.source === 'biometric'
                                            ? 'Biometric'
                                            : 'Bundy Clock'}
                                    </Badge>
                                </Td>
                                <Td className="text-right">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => remove(log)}
                                    >
                                        Remove
                                    </Button>
                                </Td>
                            </tr>
                        );
                    })}
                </Table>
            </Card>
            <Pagination page={logs} />
        </>
    );
}
