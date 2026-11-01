import { Head, Link, router } from '@inertiajs/react';
import { FilingDetails, FilingStatusBadge } from '@/components/filing-summary';
import {
    Button,
    Card,
    PageHeader,
    Pagination,
    Table,
    Td,
    Th,
    buttonClasses,
} from '@/components/ui';
import endorsements from '@/routes/endorsements';
import type { Filing, Paginated } from '@/types';

type Props = { filings: Paginated<Filing>; showAll: boolean };

export default function Endorsements({ filings, showAll }: Props) {
    function decide(filing: Filing, decision: 'endorse' | 'deny') {
        const remarks =
            decision === 'deny'
                ? window.prompt('Reason for denying (optional):')
                : null;

        if (decision === 'deny' && remarks === null) {
            return;
        }

        router.put(
            endorsements.update.url(filing.id),
            { decision, remarks },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Endorsements" />
            <PageHeader
                title="Filings for Endorsement"
                description="Check your team's filed missing logs, leaves, overtime and early outs. Endorsed filings go to the HR Time Keeper for final approval."
                actions={
                    <Link
                        href={endorsements.index.url({
                            query: showAll ? {} : { all: 1 },
                        })}
                        className={buttonClasses('secondary')}
                    >
                        {showAll ? 'Show pending only' : 'Show all'}
                    </Link>
                }
            />
            <Card>
                <Table
                    isEmpty={filings.data.length === 0}
                    empty="Nothing waiting for your endorsement."
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
                                <div className="font-mono text-xs text-ink-muted">
                                    {filing.employee?.employee_number}
                                </div>
                            </Td>
                            <Td>
                                <FilingDetails filing={filing} />
                            </Td>
                            <Td className="max-w-64 text-xs text-ink-muted">
                                {filing.reason}
                            </Td>
                            <Td>
                                <FilingStatusBadge status={filing.status} />
                            </Td>
                            <Td className="text-right whitespace-nowrap">
                                {filing.status === 'pending' && (
                                    <>
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                decide(filing, 'endorse')
                                            }
                                        >
                                            Endorse
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
