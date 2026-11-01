import { Head, router } from '@inertiajs/react';
import { FilingDetails, FilingStatusBadge } from '@/components/filing-summary';
import {
    Badge,
    Button,
    Card,
    PageHeader,
    Pagination,
    Select,
    Table,
    Td,
    Th,
} from '@/components/ui';
import filingApprovals from '@/routes/timekeeping/filings';
import type { Filing, Option, Paginated } from '@/types';

type Props = {
    filings: Paginated<Filing>;
    filters: { status: string; type: string };
    statuses: Option[];
    types: Option[];
};

export default function FilingApprovals({
    filings,
    filters,
    statuses,
    types,
}: Props) {
    function filter(changes: Partial<Props['filters']>) {
        router.get(
            filingApprovals.index.url(),
            { ...filters, ...changes },
            { preserveState: true },
        );
    }

    function decide(filing: Filing, decision: 'approve' | 'deny') {
        let withPay = filing.with_pay;

        if (decision === 'approve' && filing.type === 'leave') {
            withPay = window.confirm(
                'Approve this leave WITH PAY?\n\nOK = with pay, Cancel = without pay',
            );
        }

        const remarks = window.prompt(
            `Remarks (include the approval date for HR records):`,
            `${decision === 'approve' ? 'Approved' : 'Denied'} ${new Date().toLocaleDateString('en-PH')}`,
        );

        if (remarks === null) {
            return;
        }

        router.put(
            filingApprovals.update.url(filing.id),
            { decision, remarks, with_pay: withPay },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Filings Approval" />
            <PageHeader
                title="Filings Approval"
                description="Validate filings endorsed by Dept. Heads (shift change, offsetting, no time in/out without valid attachment). After approving, the Work Hours Summary reflects the changes automatically."
            />
            <Card>
                <div className="mb-5 flex flex-wrap gap-2">
                    <Select
                        options={[
                            { value: 'for_review', label: 'Awaiting review' },
                            { value: 'all', label: 'All statuses' },
                            ...statuses,
                        ]}
                        value={filters.status}
                        onChange={(event) =>
                            filter({ status: event.target.value })
                        }
                        className="w-52"
                    />
                    <Select
                        options={types}
                        placeholder="All types"
                        value={filters.type}
                        onChange={(event) =>
                            filter({ type: event.target.value })
                        }
                        className="w-44"
                    />
                </div>
                <Table
                    isEmpty={filings.data.length === 0}
                    empty="No filings match these filters."
                    head={
                        <tr>
                            <Th>Employee</Th>
                            <Th>Request</Th>
                            <Th>Reason</Th>
                            <Th>Status</Th>
                            <Th />
                        </tr>
                    }
                >
                    {filings.data.map((filing) => (
                        <tr key={filing.id}>
                            <Td>
                                <div className="font-medium">
                                    {filing.employee?.full_name}
                                </div>
                                <div className="text-xs text-ink-muted">
                                    {filing.employee?.employee_number} ·{' '}
                                    {filing.employee?.department?.name}
                                </div>
                            </Td>
                            <Td>
                                <FilingDetails filing={filing} />
                            </Td>
                            <Td className="max-w-64 text-xs text-ink-muted">
                                {filing.reason}
                                {filing.remarks && (
                                    <div className="mt-1 text-ink">
                                        Remarks: {filing.remarks}
                                    </div>
                                )}
                            </Td>
                            <Td>
                                <div className="flex flex-col items-start gap-1">
                                    <FilingStatusBadge status={filing.status} />
                                    {filing.endorser && (
                                        <span className="text-xs text-ink-muted">
                                            by {filing.endorser.name}
                                        </span>
                                    )}
                                </div>
                            </Td>
                            <Td className="text-right whitespace-nowrap">
                                {filing.ready_for_review ? (
                                    <>
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                decide(filing, 'approve')
                                            }
                                        >
                                            Approve
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                decide(filing, 'deny')
                                            }
                                        >
                                            Deny
                                        </Button>
                                    </>
                                ) : (
                                    filing.status === 'pending' && (
                                        <Badge tone="amber">
                                            Awaiting Dept. Head
                                        </Badge>
                                    )
                                )}
                            </Td>
                        </tr>
                    ))}
                </Table>
            </Card>
            <Pagination page={filings} />
        </>
    );
}
