import { Head, Link, usePage } from '@inertiajs/react';
import { Card, PageHeader, Stat, buttonClasses } from '@/components/ui';
import { peso } from '@/lib/format';
import { attendance } from '@/routes/my';
import myFilings from '@/routes/my/filings';
import payslips from '@/routes/payslips';
import periods from '@/routes/payroll/periods';
import filingApprovals from '@/routes/timekeeping/filings';
import type { PayrollPeriod } from '@/types';

type Props = {
    stats: {
        activeEmployees: number;
        presentToday: number;
        filingsForReview: number;
        latestPeriod: PayrollPeriod | null;
    } | null;
    me: {
        todayLogs: string[];
        pendingFilings: number;
        latestPayslip: {
            id: number;
            net_pay: string;
            payroll_period: PayrollPeriod;
        } | null;
    } | null;
};

export default function Dashboard({ stats, me }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <PageHeader
                title={`Good day, ${auth.user?.name.split(' ')[0]}`}
                description={new Date().toLocaleDateString('en-PH', {
                    weekday: 'long',
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                })}
            />

            <div className="flex flex-col gap-6">
                {stats && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Stat
                            label="Active employees"
                            value={stats.activeEmployees}
                        />
                        <Stat
                            label="With logs today"
                            value={stats.presentToday}
                            hint="From biometric and Bundy Clock punches"
                        />
                        <Stat
                            label="Filings awaiting review"
                            value={stats.filingsForReview}
                            hint={
                                auth.can.manageTimekeeping && (
                                    <Link
                                        href={filingApprovals.index.url()}
                                        className="text-brand hover:underline"
                                    >
                                        Review filings
                                    </Link>
                                )
                            }
                        />
                        <Stat
                            label="Latest payroll period"
                            value={
                                <span className="text-base">
                                    {stats.latestPeriod?.label ?? 'None yet'}
                                </span>
                            }
                            hint={
                                stats.latestPeriod &&
                                auth.can.managePayroll && (
                                    <Link
                                        href={periods.show.url(
                                            stats.latestPeriod.id,
                                        )}
                                        className="text-brand capitalize hover:underline"
                                    >
                                        {stats.latestPeriod.status} — open
                                        register
                                    </Link>
                                )
                            }
                        />
                    </div>
                )}

                {me && (
                    <div className="grid gap-4 lg:grid-cols-3">
                        <Card title="Today's logs">
                            {me.todayLogs.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {me.todayLogs.map((time, index) => (
                                        <span
                                            key={index}
                                            className="rounded-md bg-surface-muted px-2.5 py-1 text-sm font-medium text-ink tabular-nums"
                                        >
                                            {time}
                                        </span>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-ink-muted">
                                    No punches recorded yet today. Use your
                                    company ID at the biometric reader.
                                </p>
                            )}
                            <Link
                                href={attendance.url()}
                                className={`${buttonClasses('secondary', 'sm')} mt-4`}
                            >
                                View my attendance
                            </Link>
                        </Card>
                        <Card title="My filings">
                            <p className="text-sm text-ink-muted">
                                {me.pendingFilings
                                    ? `${me.pendingFilings} filing(s) awaiting approval.`
                                    : 'No filings awaiting approval.'}
                            </p>
                            <Link
                                href={myFilings.index.url()}
                                className={`${buttonClasses('secondary', 'sm')} mt-4`}
                            >
                                File unaccounted, leave or OT
                            </Link>
                        </Card>
                        <Card title="Latest payslip">
                            {me.latestPayslip ? (
                                <>
                                    <div className="text-2xl font-semibold text-ink tabular-nums">
                                        {peso(me.latestPayslip.net_pay)}
                                    </div>
                                    <p className="text-xs text-ink-muted">
                                        {me.latestPayslip.payroll_period.label}
                                    </p>
                                    <Link
                                        href={payslips.show.url(
                                            me.latestPayslip.id,
                                        )}
                                        className={`${buttonClasses('secondary', 'sm')} mt-4`}
                                    >
                                        View payslip
                                    </Link>
                                </>
                            ) : (
                                <p className="text-sm text-ink-muted">
                                    No released payslips yet.
                                </p>
                            )}
                        </Card>
                    </div>
                )}

                {!stats && !me && (
                    <Card>
                        <p className="text-sm text-ink-muted">
                            Your account is not linked to an employee record
                            yet. Ask HR to link it.
                        </p>
                    </Card>
                )}
            </div>
        </>
    );
}
