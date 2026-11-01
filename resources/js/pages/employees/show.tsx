import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    Avatar,
    Badge,
    Card,
    PageHeader,
    Table,
    Td,
    Th,
    buttonClasses,
    statusTone,
} from '@/components/ui';
import { formatDate, formatTime, peso } from '@/lib/format';
import employees from '@/routes/employees';
import payslips from '@/routes/payslips';
import workHours from '@/routes/timekeeping/work-hours';
import type { Employee, Filing, Loan, Payslip } from '@/types';

type Props = {
    employee: Employee;
    filings: Filing[];
    loans: Loan[];
    payslips: Payslip[];
    contributions: {
        sss_contribution: number;
        philhealth_contribution: number;
        pagibig_contribution: number;
        monthly_salary: number;
        sss_salary_credit: number;
    };
};

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-ink-muted">{label}</dt>
            <dd className="mt-0.5 text-sm text-ink">{children || '—'}</dd>
        </div>
    );
}

const words = (value: string) => value.replaceAll('_', ' ');

export default function EmployeeShow({
    employee,
    filings,
    loans,
    payslips: recentPayslips,
    contributions,
}: Props) {
    return (
        <>
            <Head title={employee.full_name} />
            <div className="flex items-start gap-5">
                <Avatar
                    name={employee.full_name}
                    photoUrl={employee.photo_url}
                    size="lg"
                />
                <div className="min-w-0 flex-1 pt-3">
                    <PageHeader
                        title={employee.full_name}
                        description={`${employee.employee_number} · ${employee.job_title} · ${employee.department?.name}`}
                        actions={
                            <>
                                <Link
                                    href={workHours.show.url(employee.id)}
                                    className={buttonClasses('secondary')}
                                >
                                    Work hours
                                </Link>
                                <Link
                                    href={employees.edit.url(employee.id)}
                                    className={buttonClasses()}
                                >
                                    Edit record
                                </Link>
                            </>
                        }
                    />
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="flex flex-col gap-6 lg:col-span-2">
                    <Card title="Employment">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <Detail label="Status">
                                <Badge
                                    tone={statusTone[employee.employee_status]}
                                >
                                    {employee.employee_status}
                                </Badge>
                            </Detail>
                            <Detail label="Employment status">
                                <span className="capitalize">
                                    {words(employee.employment_status)}
                                </span>
                            </Detail>
                            <Detail label="Level">
                                <span className="capitalize">
                                    {words(employee.level)}
                                </span>
                            </Detail>
                            <Detail label="Branch">
                                {employee.branch?.name}
                            </Detail>
                            <Detail label="Shift">
                                {employee.shift &&
                                    `${employee.shift.name} (${formatTime(employee.shift.start_time)}–${formatTime(employee.shift.end_time)})`}
                            </Detail>
                            <Detail label="Date hired">
                                {formatDate(employee.date_hired)}
                            </Detail>
                            {employee.date_separated && (
                                <Detail label="Date separated">
                                    {formatDate(employee.date_separated)}
                                </Detail>
                            )}
                        </dl>
                    </Card>

                    <Card title="Payroll & Statutory">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <Detail label="Pay type">
                                <span className="capitalize">
                                    {words(employee.pay_type)}
                                </span>
                            </Detail>
                            <Detail
                                label={
                                    employee.pay_type === 'monthly'
                                        ? 'Monthly rate'
                                        : 'Daily rate'
                                }
                            >
                                {peso(employee.rate)}
                            </Detail>
                            <Detail label="Payment">
                                {employee.payment_method === 'atm'
                                    ? `ATM · ${employee.bank_account_number ?? ''}`
                                    : 'Cash'}
                            </Detail>
                            <Detail label="SSS No.">
                                {employee.sss_number}
                            </Detail>
                            <Detail label="PhilHealth No.">
                                {employee.philhealth_number}
                            </Detail>
                            <Detail label="Pag-IBIG No.">
                                {employee.pagibig_number}
                            </Detail>
                            <Detail label="TIN">{employee.tin}</Detail>
                            <Detail label="Statutory deductions">
                                {employee.has_statutory_deductions ? (
                                    <Badge tone="green">Applied monthly</Badge>
                                ) : (
                                    <Badge tone="amber">Not yet applied</Badge>
                                )}
                            </Detail>
                        </dl>

                        <div className="mt-5 border-t border-line pt-4">
                            <div className="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                                <h3 className="text-sm font-semibold text-ink">
                                    Monthly contributions (employee share)
                                </h3>
                                <span className="text-xs text-ink-muted">
                                    {employee.custom_contributions
                                        ? 'Custom amounts set by HR'
                                        : `From the 2026 tables · monthly salary ${peso(contributions.monthly_salary)} · SSS MSC ${peso(contributions.sss_salary_credit)}`}
                                </span>
                            </div>
                            <dl className="grid gap-4 sm:grid-cols-4">
                                <Detail label="SSS">
                                    {peso(contributions.sss_contribution)}
                                </Detail>
                                <Detail label="PhilHealth">
                                    {peso(
                                        contributions.philhealth_contribution,
                                    )}
                                </Detail>
                                <Detail label="Pag-IBIG">
                                    {peso(contributions.pagibig_contribution)}
                                </Detail>
                                <Detail label="Total per month">
                                    <span className="font-semibold">
                                        {peso(
                                            contributions.sss_contribution +
                                                contributions.philhealth_contribution +
                                                contributions.pagibig_contribution,
                                        )}
                                    </span>
                                </Detail>
                            </dl>
                        </div>
                    </Card>

                    <Card title="Recent filings">
                        <Table
                            isEmpty={filings.length === 0}
                            empty="No filings yet."
                            head={
                                <tr>
                                    <Th>Type</Th>
                                    <Th>Date</Th>
                                    <Th>Reason</Th>
                                    <Th>Status</Th>
                                </tr>
                            }
                        >
                            {filings.map((filing) => (
                                <tr key={filing.id}>
                                    <Td className="capitalize">
                                        {words(filing.type)}
                                    </Td>
                                    <Td>
                                        {formatDate(filing.date_from)}
                                        {filing.date_to !== filing.date_from &&
                                            ` – ${formatDate(filing.date_to)}`}
                                    </Td>
                                    <Td className="max-w-xs truncate">
                                        {filing.reason}
                                    </Td>
                                    <Td>
                                        <Badge tone={statusTone[filing.status]}>
                                            {filing.status}
                                        </Badge>
                                    </Td>
                                </tr>
                            ))}
                        </Table>
                    </Card>
                </div>

                <div className="flex flex-col gap-6">
                    <Card title="Contact">
                        <dl className="flex flex-col gap-3">
                            <Detail label="Mobile">
                                {employee.mobile_number}
                            </Detail>
                            <Detail label="Email">{employee.email}</Detail>
                            <Detail label="Address">{employee.address}</Detail>
                            <Detail label="Birth date">
                                {formatDate(employee.birth_date)}
                            </Detail>
                            <Detail label="Emergency contact">
                                {employee.emergency_contact_name &&
                                    `${employee.emergency_contact_name} (${employee.emergency_contact_relationship ?? '—'}) · ${employee.emergency_contact_number ?? ''}`}
                            </Detail>
                            <Detail label="Kiosk login">
                                {employee.user?.email ?? 'No account'}
                            </Detail>
                        </dl>
                    </Card>

                    <Card title="Loans">
                        {loans.length ? (
                            <ul className="flex flex-col gap-3 text-sm">
                                {loans.map((loan) => (
                                    <li
                                        key={loan.id}
                                        className="flex justify-between gap-2"
                                    >
                                        <span className="uppercase">
                                            {loan.type}
                                        </span>
                                        <span className="text-ink-muted tabular-nums">
                                            {peso(loan.balance)} left
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-ink-muted">
                                No loans on file.
                            </p>
                        )}
                    </Card>

                    <Card title="Recent payslips">
                        {recentPayslips.length ? (
                            <ul className="flex flex-col gap-3 text-sm">
                                {recentPayslips.map((payslip) => (
                                    <li
                                        key={payslip.id}
                                        className="flex justify-between gap-2"
                                    >
                                        <Link
                                            href={payslips.show.url(payslip.id)}
                                            className="text-brand hover:underline"
                                        >
                                            {payslip.payroll_period?.label}
                                        </Link>
                                        <span className="tabular-nums">
                                            {peso(payslip.net_pay)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-ink-muted">
                                No payslips yet.
                            </p>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}
