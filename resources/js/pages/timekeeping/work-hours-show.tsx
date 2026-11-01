import { Head, Link } from '@inertiajs/react';
import DailyRecordsTable, {
    WorkSummaryStats,
} from '@/components/daily-records-table';
import DateRangeFilter from '@/components/date-range-filter';
import { Button, Card, PageHeader, buttonClasses } from '@/components/ui';
import workHours from '@/routes/timekeeping/work-hours';
import type { DailyRecord, WorkSummary } from '@/types';

type Props = {
    employee: { id: number; employee_number: string; full_name: string };
    records: DailyRecord[];
    summary: WorkSummary;
    filters: { from: string; to: string };
};

export default function WorkHoursShow({
    employee,
    records,
    summary,
    filters,
}: Props) {
    return (
        <>
            <Head title={`WHS · ${employee.full_name}`} />
            <PageHeader
                title={employee.full_name}
                description={`${employee.employee_number} · Work Hours Summary`}
                actions={
                    <>
                        <Link
                            href={workHours.index.url({ query: filters })}
                            className={buttonClasses('secondary')}
                        >
                            Back to summary
                        </Link>
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
                    url={workHours.show.url(employee.id)}
                    from={filters.from}
                    to={filters.to}
                />
                <WorkSummaryStats summary={summary} />
                <Card>
                    <DailyRecordsTable records={records} />
                </Card>
            </div>
        </>
    );
}
