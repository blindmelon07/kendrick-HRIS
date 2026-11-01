import { Head, Link } from '@inertiajs/react';
import { Card, PageHeader, Pagination, Table, Td, Th } from '@/components/ui';
import { formatDate, peso } from '@/lib/format';
import payslips from '@/routes/payslips';
import type { Paginated, Payslip } from '@/types';

export default function MyPayslips({
    payslips: page,
}: {
    payslips: Paginated<Payslip>;
}) {
    return (
        <>
            <Head title="My Payslips" />
            <PageHeader
                title="My Payslips"
                description="Payslips appear here once payroll for the cut-off is finalized."
            />
            <Card>
                <Table
                    isEmpty={page.data.length === 0}
                    empty="No payslips released yet."
                    head={
                        <tr>
                            <Th>Cut-off</Th>
                            <Th>Pay date</Th>
                            <Th className="text-right">Gross</Th>
                            <Th className="text-right">Deductions</Th>
                            <Th className="text-right">Net pay</Th>
                        </tr>
                    }
                >
                    {page.data.map((payslip) => (
                        <tr key={payslip.id} className="hover:bg-surface-muted">
                            <Td>
                                <Link
                                    href={payslips.show.url(payslip.id)}
                                    className="font-medium text-brand hover:underline"
                                >
                                    {payslip.payroll_period?.label}
                                </Link>
                            </Td>
                            <Td>
                                {formatDate(payslip.payroll_period?.pay_date)}
                            </Td>
                            <Td className="text-right tabular-nums">
                                {peso(payslip.gross_pay)}
                            </Td>
                            <Td className="text-right tabular-nums">
                                {peso(payslip.total_deductions)}
                            </Td>
                            <Td className="text-right font-semibold tabular-nums">
                                {peso(payslip.net_pay)}
                            </Td>
                        </tr>
                    ))}
                </Table>
            </Card>
            <Pagination page={page} />
        </>
    );
}
