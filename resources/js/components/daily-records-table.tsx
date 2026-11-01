import { Badge, Stat, Table, Td, Th, statusTone } from '@/components/ui';
import { formatDate, minutes } from '@/lib/format';
import type { DailyRecord, WorkSummary } from '@/types';

const statusLabels: Record<DailyRecord['status'], string> = {
    present: 'Present',
    absent: 'Absent',
    incomplete: 'Missing log',
    leave: 'On leave',
    holiday: 'Holiday',
    rest_day: 'Rest day',
    not_employed: '—',
};

export function WorkSummaryStats({ summary }: { summary: WorkSummary }) {
    return (
        <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <Stat label="Days worked" value={summary.days_worked} />
            <Stat label="Paid leave" value={summary.paid_leave_days} />
            <Stat
                label="Absent / unpaid"
                value={summary.days_absent + summary.unpaid_leave_days}
            />
            <Stat label="Late" value={minutes(summary.late_minutes)} />
            <Stat
                label="Undertime"
                value={minutes(summary.undertime_minutes)}
            />
            <Stat label="Overtime" value={`${summary.overtime_hours}h`} />
        </div>
    );
}

export default function DailyRecordsTable({
    records,
}: {
    records: DailyRecord[];
}) {
    return (
        <Table
            head={
                <tr>
                    <Th>Date</Th>
                    <Th>Shift</Th>
                    <Th>In</Th>
                    <Th>Out</Th>
                    <Th>Late</Th>
                    <Th>Undertime</Th>
                    <Th>OT</Th>
                    <Th>Status</Th>
                </tr>
            }
        >
            {records.map((record) => (
                <tr
                    key={record.date}
                    className={
                        record.is_work_day
                            ? ''
                            : 'bg-surface-muted/60 text-ink-muted'
                    }
                >
                    <Td className="whitespace-nowrap">
                        <span className="inline-block w-9 text-ink-muted">
                            {record.day}
                        </span>
                        {formatDate(record.date)}
                    </Td>
                    <Td className="text-xs text-ink-muted">{record.shift}</Td>
                    <Td className="tabular-nums">{record.time_in ?? '—'}</Td>
                    <Td className="tabular-nums">{record.time_out ?? '—'}</Td>
                    <Td
                        className={
                            record.late_minutes
                                ? 'text-red-600 tabular-nums'
                                : 'tabular-nums'
                        }
                    >
                        {minutes(record.late_minutes)}
                    </Td>
                    <Td
                        className={
                            record.undertime_minutes
                                ? 'text-red-600 tabular-nums'
                                : 'tabular-nums'
                        }
                    >
                        {minutes(record.undertime_minutes)}
                    </Td>
                    <Td className="tabular-nums">
                        {record.overtime_hours
                            ? `${record.overtime_hours}h`
                            : '—'}
                    </Td>
                    <Td>
                        <div className="flex flex-wrap items-center gap-1">
                            <Badge tone={statusTone[record.status]}>
                                {record.status === 'leave'
                                    ? record.paid_leave_days
                                        ? 'Leave (paid)'
                                        : 'Leave (unpaid)'
                                    : statusLabels[record.status]}
                            </Badge>
                            {record.days_worked === 0.5 && (
                                <Badge tone="blue">Half-day leave</Badge>
                            )}
                            {record.holiday && (
                                <Badge tone="blue">{record.holiday}</Badge>
                            )}
                        </div>
                    </Td>
                </tr>
            ))}
        </Table>
    );
}
