import { Head, Link } from '@inertiajs/react';
import DailyRecordsTable, {
    WorkSummaryStats,
} from '@/components/daily-records-table';
import DateRangeFilter from '@/components/date-range-filter';
import { Card, PageHeader, buttonClasses } from '@/components/ui';
import { attendance } from '@/routes/my';
import myFilings from '@/routes/my/filings';
import type { DailyRecord, WorkSummary } from '@/types';

type Props = {
    records: DailyRecord[];
    summary: WorkSummary;
    filters: { from: string; to: string };
};

export default function MyAttendance({ records, summary, filters }: Props) {
    const missing = records.filter(
        (record) =>
            record.status === 'incomplete' || record.status === 'absent',
    ).length;

    return (
        <>
            <Head title="My Attendance" />
            <PageHeader
                title="My Attendance"
                description="Review your logs for the cut-off. File any missing logs (field work, WFH, leave, OT) before the 3rd and 18th, 12:00 NN."
                actions={
                    <Link
                        href={myFilings.index.url()}
                        className={buttonClasses()}
                    >
                        File a request
                    </Link>
                }
            />
            <div className="flex flex-col gap-6">
                <DateRangeFilter
                    url={attendance.url()}
                    from={filters.from}
                    to={filters.to}
                />
                <WorkSummaryStats summary={summary} />
                {missing > 0 && (
                    <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-ink">
                        You have {missing} day(s) with missing logs or no
                        attendance. If you were at work, file an Unaccounted or
                        Leave request so it reflects in your Work Hours Summary.
                    </div>
                )}
                <Card>
                    <DailyRecordsTable records={records} />
                </Card>
            </div>
        </>
    );
}
