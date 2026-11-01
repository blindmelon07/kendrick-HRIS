import { Head } from '@inertiajs/react';
import { Button, PageHeader } from '@/components/ui';
import { formatDate, minutes, peso } from '@/lib/format';
import type { PayrollAdjustment, Payslip } from '@/types';

type Props = {
    payslip: Payslip;
    adjustments: Pick<
        PayrollAdjustment,
        'id' | 'category' | 'description' | 'amount'
    >[];
    company: string;
};

function Line({
    label,
    amount,
    negative = false,
}: {
    label: string;
    amount: string | number;
    negative?: boolean;
}) {
    if (!Number(amount)) {
        return null;
    }

    return (
        <div className="flex justify-between gap-4 py-1 text-sm">
            <span className="text-ink-muted">{label}</span>
            <span className="text-ink tabular-nums">
                {negative && '−'}
                {peso(amount)}
            </span>
        </div>
    );
}

export default function PayslipShow({ payslip, adjustments, company }: Props) {
    const period = payslip.payroll_period!;
    const employee = payslip.employee!;
    const otherEarnings = adjustments.filter(
        (entry) => entry.category === 'other_earning',
    );
    const otherDeductions = adjustments.filter(
        (entry) => entry.category === 'other_deduction',
    );

    return (
        <>
            <Head title={`Payslip · ${employee.full_name}`} />
            <PageHeader
                title="Payslip"
                description={period.label}
                actions={
                    <Button variant="secondary" onClick={() => window.print()}>
                        Print / Save PDF
                    </Button>
                }
            />

            <article className="mx-auto max-w-3xl rounded-lg border border-line bg-surface p-6 shadow-xs sm:p-8 print:border-0 print:shadow-none">
                <header className="flex flex-wrap justify-between gap-4 border-b border-line pb-5">
                    <div>
                        <div className="text-lg font-semibold text-ink">
                            {company}
                        </div>
                        <div className="text-sm text-ink-muted">
                            Pay period {formatDate(period.start_date)} –{' '}
                            {formatDate(period.end_date)}
                        </div>
                        <div className="text-sm text-ink-muted">
                            Pay date {formatDate(period.pay_date)}
                        </div>
                    </div>
                    <div className="text-right text-sm">
                        <div className="font-semibold text-ink">
                            {employee.full_name}
                        </div>
                        <div className="text-ink-muted">
                            {employee.employee_number}
                        </div>
                        <div className="text-ink-muted">
                            {employee.job_title} · {employee.department?.name}
                        </div>
                        <div className="text-ink-muted">
                            {employee.branch?.name}
                        </div>
                    </div>
                </header>

                <section className="grid grid-cols-2 gap-x-6 gap-y-1 border-b border-line py-4 text-sm sm:grid-cols-4">
                    <div>
                        <div className="text-xs text-ink-muted">
                            Days worked
                        </div>
                        <div className="tabular-nums">
                            {Number(payslip.days_worked)}
                        </div>
                    </div>
                    <div>
                        <div className="text-xs text-ink-muted">
                            Absent / unpaid
                        </div>
                        <div className="tabular-nums">
                            {Number(payslip.days_absent)}
                        </div>
                    </div>
                    <div>
                        <div className="text-xs text-ink-muted">
                            Late / undertime
                        </div>
                        <div className="tabular-nums">
                            {minutes(
                                payslip.late_minutes +
                                    payslip.undertime_minutes,
                            )}
                        </div>
                    </div>
                    <div>
                        <div className="text-xs text-ink-muted">Overtime</div>
                        <div className="tabular-nums">
                            {Number(payslip.overtime_hours)}h
                        </div>
                    </div>
                </section>

                <section className="grid gap-8 py-5 sm:grid-cols-2">
                    <div>
                        <h3 className="mb-2 text-xs font-semibold tracking-wide text-ink-muted uppercase">
                            Earnings
                        </h3>
                        <Line label="Basic pay" amount={payslip.basic_pay} />
                        <Line
                            label="Holiday pay"
                            amount={payslip.holiday_pay}
                        />
                        <Line
                            label="Overtime pay"
                            amount={payslip.overtime_pay}
                        />
                        <Line
                            label="Per Biyahe"
                            amount={payslip.per_biyahe_pay}
                        />
                        {otherEarnings.map((entry) => (
                            <Line
                                key={entry.id}
                                label={entry.description}
                                amount={entry.amount}
                            />
                        ))}
                        <Line
                            label="Absences / unpaid leave"
                            amount={payslip.absence_deduction}
                            negative
                        />
                        <Line
                            label="Late / undertime"
                            amount={payslip.late_undertime_deduction}
                            negative
                        />
                        <div className="mt-2 flex justify-between border-t border-line pt-2 text-sm font-semibold">
                            <span>Gross pay</span>
                            <span className="tabular-nums">
                                {peso(payslip.gross_pay)}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 className="mb-2 text-xs font-semibold tracking-wide text-ink-muted uppercase">
                            Deductions
                        </h3>
                        <Line label="SSS" amount={payslip.sss_contribution} />
                        <Line
                            label="PhilHealth"
                            amount={payslip.philhealth_contribution}
                        />
                        <Line
                            label="Pag-IBIG"
                            amount={payslip.pagibig_contribution}
                        />
                        {(payslip.loan_breakdown ?? []).map((loan) => (
                            <Line
                                key={loan.loan_id}
                                label={loan.type}
                                amount={loan.amount}
                            />
                        ))}
                        {otherDeductions.map((entry) => (
                            <Line
                                key={entry.id}
                                label={entry.description}
                                amount={entry.amount}
                            />
                        ))}
                        <div className="mt-2 flex justify-between border-t border-line pt-2 text-sm font-semibold">
                            <span>Total deductions</span>
                            <span className="tabular-nums">
                                {peso(payslip.total_deductions)}
                            </span>
                        </div>
                    </div>
                </section>

                <footer className="flex items-center justify-between rounded-md bg-brand-soft px-5 py-4">
                    <div>
                        <div className="text-xs font-medium text-brand">
                            NET PAY
                        </div>
                        <div className="text-xs text-ink-muted">
                            {payslip.payment_method === 'atm'
                                ? 'Credited to BDO payroll account'
                                : 'Released in cash'}
                        </div>
                    </div>
                    <div className="text-2xl font-semibold text-ink tabular-nums">
                        {peso(payslip.net_pay)}
                    </div>
                </footer>
            </article>
        </>
    );
}
