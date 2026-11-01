import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    Badge,
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Stat,
    Table,
    Td,
    Th,
    statusTone,
} from '@/components/ui';
import { formatDate, labelFor, peso } from '@/lib/format';
import payslips from '@/routes/payslips';
import periods from '@/routes/payroll/periods';
import adjustments from '@/routes/payroll/periods/adjustments';
import type {
    EmployeeSummary,
    Option,
    PayrollAdjustment,
    PayrollPeriod,
    Payslip,
} from '@/types';

type Props = {
    period: PayrollPeriod;
    payslips: Payslip[];
    totals: {
        gross: number;
        deductions: number;
        net: number;
        atm: number;
        cash: number;
    };
    adjustments: PayrollAdjustment[];
    categories: Option[];
    employees: EmployeeSummary[];
};

const num = (value: string | number) => Number(value);

function downloadCsv(filename: string, rows: (string | number)[][]) {
    const csv = rows
        .map((row) =>
            row
                .map((cell) =>
                    typeof cell === 'string' && /[",\n]/.test(cell)
                        ? `"${cell.replaceAll('"', '""')}"`
                        : cell,
                )
                .join(','),
        )
        .join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

export default function PayrollPeriodShow({
    period,
    payslips: rows,
    totals,
    adjustments: entries,
    categories,
    employees,
}: Props) {
    const locked = period.status === 'finalized';
    const manual = useForm({
        employee_id: '',
        category: 'per_biyahe',
        description: '',
        amount: '',
    });
    const upload = useForm<{ category: string; file: File | null }>({
        category: 'per_biyahe',
        file: null,
    });

    function process() {
        router.post(
            periods.process.url(period.id),
            {},
            { preserveScroll: true },
        );
    }

    function finalize() {
        if (
            window.confirm(
                'Finalize this payroll? Loan balances will be updated, payslips will be released to employees, and the period will be locked.',
            )
        ) {
            router.post(periods.finalize.url(period.id));
        }
    }

    function destroyPeriod() {
        if (
            window.confirm(
                'Delete this payroll period and all its payslips and adjustments?',
            )
        ) {
            router.delete(periods.destroy.url(period.id));
        }
    }

    function addAdjustment(event: FormEvent) {
        event.preventDefault();
        manual.post(adjustments.store.url(period.id), {
            preserveScroll: true,
            onSuccess: () =>
                manual.reset('employee_id', 'description', 'amount'),
        });
    }

    function uploadAdjustments(event: FormEvent) {
        event.preventDefault();
        upload.post(adjustments.import.url(period.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.setData('file', null),
        });
    }

    function exportRegister() {
        downloadCsv(
            `Payroll_Register_${period.start_date}_${period.end_date}.csv`,
            [
                [
                    'Employee No.',
                    'Name',
                    'Days Worked',
                    'Days Absent',
                    'Basic',
                    'Holiday',
                    'OT',
                    'Per Biyahe',
                    'Other Earnings',
                    'Absences',
                    'Late/UT',
                    'Gross',
                    'SSS',
                    'PhilHealth',
                    'Pag-IBIG',
                    'Loans',
                    'Other Deductions',
                    'Net',
                    'Payment',
                ],
                ...rows.map((payslip) => [
                    payslip.employee?.employee_number ?? '',
                    payslip.employee?.full_name ?? '',
                    num(payslip.days_worked),
                    num(payslip.days_absent),
                    num(payslip.basic_pay),
                    num(payslip.holiday_pay),
                    num(payslip.overtime_pay),
                    num(payslip.per_biyahe_pay),
                    num(payslip.other_earnings),
                    num(payslip.absence_deduction),
                    num(payslip.late_undertime_deduction),
                    num(payslip.gross_pay),
                    num(payslip.sss_contribution),
                    num(payslip.philhealth_contribution),
                    num(payslip.pagibig_contribution),
                    num(payslip.loan_deductions),
                    num(payslip.other_deductions),
                    num(payslip.net_pay),
                    payslip.payment_method.toUpperCase(),
                ]),
            ],
        );
    }

    function exportPaymentAdvice(method: 'atm' | 'cash') {
        const selected = rows.filter(
            (payslip) => payslip.payment_method === method,
        );

        downloadCsv(
            `${method === 'atm' ? 'BDO_ATM' : 'Cash'}_Payroll_${period.pay_date}.csv`,
            [
                method === 'atm'
                    ? ['Account Number', 'Employee No.', 'Name', 'Net Pay']
                    : ['Employee No.', 'Name', 'Net Pay'],
                ...selected.map((payslip) =>
                    method === 'atm'
                        ? [
                              payslip.employee?.bank_account_number ?? '',
                              payslip.employee?.employee_number ?? '',
                              payslip.employee?.full_name ?? '',
                              num(payslip.net_pay),
                          ]
                        : [
                              payslip.employee?.employee_number ?? '',
                              payslip.employee?.full_name ?? '',
                              num(payslip.net_pay),
                          ],
                ),
            ],
        );
    }

    return (
        <>
            <Head title={`Payroll ${period.label}`} />
            <PageHeader
                title={`Payroll Register · ${period.label}`}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        <Badge tone={statusTone[period.status]}>
                            {period.status}
                        </Badge>
                        {period.half === 'first'
                            ? '1st half · with government contributions'
                            : '2nd half · with loan deductions'}
                        <span>· Pay date {formatDate(period.pay_date)}</span>
                    </span>
                }
                actions={
                    <>
                        {!locked && (
                            <Button onClick={process}>
                                {period.status === 'draft'
                                    ? 'Process payroll'
                                    : 'Reprocess payroll'}
                            </Button>
                        )}
                        {period.status === 'processed' && (
                            <Button variant="secondary" onClick={finalize}>
                                Finalize & release
                            </Button>
                        )}
                        {rows.length > 0 && (
                            <Button
                                variant="secondary"
                                onClick={exportRegister}
                            >
                                Export register
                            </Button>
                        )}
                        {!locked && (
                            <Button variant="ghost" onClick={destroyPeriod}>
                                Delete
                            </Button>
                        )}
                    </>
                }
            />

            <div className="flex flex-col gap-6">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Stat label="Gross pay" value={peso(totals.gross)} />
                    <Stat label="Deductions" value={peso(totals.deductions)} />
                    <Stat label="Net pay" value={peso(totals.net)} />
                    <Stat
                        label="ATM (BDO)"
                        value={peso(totals.atm)}
                        hint={
                            rows.length > 0 && (
                                <button
                                    type="button"
                                    className="text-brand hover:underline"
                                    onClick={() => exportPaymentAdvice('atm')}
                                >
                                    Download payment advice
                                </button>
                            )
                        }
                    />
                    <Stat
                        label="Cash"
                        value={peso(totals.cash)}
                        hint={
                            rows.length > 0 && (
                                <button
                                    type="button"
                                    className="text-brand hover:underline"
                                    onClick={() => exportPaymentAdvice('cash')}
                                >
                                    Download cash list
                                </button>
                            )
                        }
                    />
                </div>

                <Card
                    title="Register"
                    description={
                        period.status === 'draft'
                            ? 'Upload Per Biyahe, other earnings and deductions below, then process the payroll.'
                            : 'Audit OT, holiday pay, leaves, Per Biyahe, other earnings and deductions. Fix the WHS or adjustments and reprocess if needed.'
                    }
                >
                    <Table
                        isEmpty={rows.length === 0}
                        empty="Not processed yet."
                        head={
                            <tr>
                                <Th>Employee</Th>
                                <Th className="text-right">Days</Th>
                                <Th className="text-right">Basic</Th>
                                <Th className="text-right">Holiday / OT</Th>
                                <Th className="text-right">
                                    Per Biyahe / Other
                                </Th>
                                <Th className="text-right">Abs. / Late</Th>
                                <Th className="text-right">Gross</Th>
                                <Th className="text-right">Gov't</Th>
                                <Th className="text-right">Loans / Other</Th>
                                <Th className="text-right">Net</Th>
                                <Th>Pay</Th>
                            </tr>
                        }
                    >
                        {rows.map((payslip) => {
                            const government =
                                num(payslip.sss_contribution) +
                                num(payslip.philhealth_contribution) +
                                num(payslip.pagibig_contribution);

                            return (
                                <tr
                                    key={payslip.id}
                                    className="tabular-nums hover:bg-surface-muted"
                                >
                                    <Td>
                                        <Link
                                            href={payslips.show.url(payslip.id)}
                                            className="font-medium text-brand hover:underline"
                                        >
                                            {payslip.employee?.full_name}
                                        </Link>
                                        <div className="font-mono text-xs text-ink-muted">
                                            {payslip.employee?.employee_number}
                                        </div>
                                    </Td>
                                    <Td className="text-right">
                                        {num(payslip.days_worked)}
                                        {num(payslip.days_absent) > 0 && (
                                            <div className="text-xs text-red-600">
                                                −{num(payslip.days_absent)} abs
                                            </div>
                                        )}
                                    </Td>
                                    <Td className="text-right">
                                        {peso(payslip.basic_pay)}
                                    </Td>
                                    <Td className="text-right">
                                        {peso(
                                            num(payslip.holiday_pay) +
                                                num(payslip.overtime_pay),
                                        )}
                                    </Td>
                                    <Td className="text-right">
                                        {peso(
                                            num(payslip.per_biyahe_pay) +
                                                num(payslip.other_earnings),
                                        )}
                                    </Td>
                                    <Td className="text-right text-red-600">
                                        {peso(
                                            num(payslip.absence_deduction) +
                                                num(
                                                    payslip.late_undertime_deduction,
                                                ),
                                        )}
                                    </Td>
                                    <Td className="text-right font-medium">
                                        {peso(payslip.gross_pay)}
                                    </Td>
                                    <Td className="text-right">
                                        {peso(government)}
                                    </Td>
                                    <Td className="text-right">
                                        {peso(
                                            num(payslip.loan_deductions) +
                                                num(payslip.other_deductions),
                                        )}
                                    </Td>
                                    <Td className="text-right font-semibold">
                                        {peso(payslip.net_pay)}
                                    </Td>
                                    <Td>
                                        <Badge
                                            tone={
                                                payslip.payment_method === 'atm'
                                                    ? 'blue'
                                                    : 'gray'
                                            }
                                        >
                                            {payslip.payment_method.toUpperCase()}
                                        </Badge>
                                    </Td>
                                </tr>
                            );
                        })}
                    </Table>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card
                        title="Earnings & deductions"
                        description="Per Biyahe trips, other earnings and other deductions for this cut-off."
                        className="lg:col-span-2"
                    >
                        <Table
                            isEmpty={entries.length === 0}
                            empty="No adjustments uploaded."
                            head={
                                <tr>
                                    <Th>Employee</Th>
                                    <Th>Category</Th>
                                    <Th>Description</Th>
                                    <Th className="text-right">Amount</Th>
                                    <Th />
                                </tr>
                            }
                        >
                            {entries.map((entry) => (
                                <tr key={entry.id}>
                                    <Td>{entry.employee?.full_name}</Td>
                                    <Td>
                                        <Badge
                                            tone={
                                                entry.category ===
                                                'other_deduction'
                                                    ? 'red'
                                                    : 'green'
                                            }
                                        >
                                            {labelFor(
                                                categories,
                                                entry.category,
                                            )}
                                        </Badge>
                                    </Td>
                                    <Td className="text-ink-muted">
                                        {entry.description}
                                    </Td>
                                    <Td className="text-right tabular-nums">
                                        {peso(entry.amount)}
                                    </Td>
                                    <Td className="text-right">
                                        {!locked && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    router.delete(
                                                        adjustments.destroy.url(
                                                            [
                                                                period.id,
                                                                entry.id,
                                                            ],
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Remove
                                            </Button>
                                        )}
                                    </Td>
                                </tr>
                            ))}
                        </Table>
                    </Card>

                    {!locked && (
                        <div className="flex flex-col gap-6">
                            <Card
                                title="Upload file"
                                description="CSV columns: employee_number, amount, description (optional). Use for the Per Biyahe uploader and Finance's deduction file."
                            >
                                <form
                                    onSubmit={uploadAdjustments}
                                    className="flex flex-col gap-3"
                                >
                                    <Field
                                        label="Category"
                                        error={upload.errors.category}
                                    >
                                        <Select
                                            options={categories}
                                            value={upload.data.category}
                                            onChange={(event) =>
                                                upload.setData(
                                                    'category',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label="CSV file"
                                        error={upload.errors.file}
                                    >
                                        <Input
                                            type="file"
                                            accept=".csv,text/csv"
                                            onChange={(event) =>
                                                upload.setData(
                                                    'file',
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={
                                            upload.processing ||
                                            !upload.data.file
                                        }
                                    >
                                        Upload
                                    </Button>
                                </form>
                            </Card>
                            <Card title="Add one entry">
                                <form
                                    onSubmit={addAdjustment}
                                    className="flex flex-col gap-3"
                                >
                                    <Field
                                        label="Employee"
                                        error={manual.errors.employee_id}
                                    >
                                        <Select
                                            options={employees.map(
                                                (employee) => ({
                                                    value: String(employee.id),
                                                    label: `${employee.employee_number} · ${employee.full_name}`,
                                                }),
                                            )}
                                            placeholder="Select…"
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
                                        label="Category"
                                        error={manual.errors.category}
                                    >
                                        <Select
                                            options={categories}
                                            value={manual.data.category}
                                            onChange={(event) =>
                                                manual.setData(
                                                    'category',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label="Description"
                                        error={manual.errors.description}
                                    >
                                        <Input
                                            value={manual.data.description}
                                            onChange={(event) =>
                                                manual.setData(
                                                    'description',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label="Amount"
                                        error={manual.errors.amount}
                                    >
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={manual.data.amount}
                                            onChange={(event) =>
                                                manual.setData(
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={manual.processing}
                                    >
                                        Add entry
                                    </Button>
                                </form>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
