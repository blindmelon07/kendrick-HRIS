import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DateRangeFilter from '@/components/date-range-filter';
import {
    Badge,
    Button,
    Card,
    PageHeader,
    Select,
    Table,
    Td,
    Th,
} from '@/components/ui';
import { minutes } from '@/lib/format';
import workHours from '@/routes/timekeeping/work-hours';
import type { NamedRecord, WorkSummary } from '@/types';

type Row = WorkSummary & {
    employee: { id: number; employee_number: string; full_name: string };
    department: string;
    incomplete_days: number;
};

type Props = {
    rows: Row[];
    filters: { from: string; to: string; department_id: number | null };
    departments: NamedRecord[];
};

function toCsv(rows: Row[]): string {
    const header = [
        'Employee No.',
        'Name',
        'Department',
        'Days Worked',
        'Paid Leave',
        'Unpaid Leave',
        'Absent',
        'Late (min)',
        'Undertime (min)',
        'OT (hrs)',
    ];
    const lines = rows.map((row) =>
        [
            row.employee.employee_number,
            `"${row.employee.full_name}"`,
            `"${row.department}"`,
            row.days_worked,
            row.paid_leave_days,
            row.unpaid_leave_days,
            row.days_absent,
            row.late_minutes,
            row.undertime_minutes,
            row.overtime_hours,
        ].join(','),
    );

    return [header.join(','), ...lines].join('\n');
}

export default function WorkHoursIndex({ rows, filters, departments }: Props) {
    const [departmentId, setDepartmentId] = useState(
        filters.department_id ? String(filters.department_id) : '',
    );

    function exportCsv() {
        const blob = new Blob([toCsv(rows)], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `WHS_${filters.from}_${filters.to}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    return (
        <>
            <Head title="Work Hours Summary" />
            <PageHeader
                title="Work Hours Summary"
                description="Processed from time logs plus approved filings. Export per department and send to Dept. Heads for confirmation; no reply within the time frame means confirmed."
                actions={
                    <>
                        <Button variant="secondary" onClick={exportCsv}>
                            Export CSV
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => window.print()}
                        >
                            Print
                        </Button>
                    </>
                }
            />
            <div className="flex flex-col gap-6">
                <DateRangeFilter
                    url={workHours.index.url()}
                    from={filters.from}
                    to={filters.to}
                    extra={{ department_id: departmentId || undefined }}
                >
                    <label className="flex flex-col gap-1 text-xs text-ink-muted">
                        Department
                        <Select
                            options={departments.map((department) => ({
                                value: String(department.id),
                                label: department.name,
                            }))}
                            placeholder="All departments"
                            value={departmentId}
                            onChange={(event) =>
                                setDepartmentId(event.target.value)
                            }
                            className="w-52"
                        />
                    </label>
                </DateRangeFilter>

                <Card>
                    <Table
                        isEmpty={rows.length === 0}
                        head={
                            <tr>
                                <Th>Employee</Th>
                                <Th>Department</Th>
                                <Th className="text-right">Worked</Th>
                                <Th className="text-right">Paid leave</Th>
                                <Th className="text-right">Absent / unpaid</Th>
                                <Th className="text-right">Late</Th>
                                <Th className="text-right">Undertime</Th>
                                <Th className="text-right">OT</Th>
                                <Th>Flags</Th>
                            </tr>
                        }
                    >
                        {rows.map((row) => (
                            <tr
                                key={row.employee.id}
                                className="hover:bg-surface-muted"
                            >
                                <Td>
                                    <Link
                                        href={workHours.show.url(
                                            row.employee.id,
                                            {
                                                query: {
                                                    from: filters.from,
                                                    to: filters.to,
                                                },
                                            },
                                        )}
                                        className="font-medium text-brand hover:underline"
                                    >
                                        {row.employee.full_name}
                                    </Link>
                                    <div className="font-mono text-xs text-ink-muted">
                                        {row.employee.employee_number}
                                    </div>
                                </Td>
                                <Td>{row.department}</Td>
                                <Td className="text-right tabular-nums">
                                    {row.days_worked}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {row.paid_leave_days || '—'}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {row.days_absent + row.unpaid_leave_days ||
                                        '—'}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {minutes(row.late_minutes)}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {minutes(row.undertime_minutes)}
                                </Td>
                                <Td className="text-right tabular-nums">
                                    {row.overtime_hours
                                        ? `${row.overtime_hours}h`
                                        : '—'}
                                </Td>
                                <Td>
                                    {row.incomplete_days > 0 && (
                                        <Badge tone="amber">
                                            {row.incomplete_days} missing log(s)
                                        </Badge>
                                    )}
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
            </div>
        </>
    );
}
