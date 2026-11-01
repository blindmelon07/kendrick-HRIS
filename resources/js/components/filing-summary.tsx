import { Badge, statusTone } from '@/components/ui';
import { formatDate, formatTime } from '@/lib/format';
import type { Filing } from '@/types';

export const filingTypeLabels: Record<string, string> = {
    unaccounted: 'Unaccounted',
    leave: 'Leave',
    overtime: 'Overtime',
    early_out: 'Early Out',
    shift_change: 'Shift Change',
};

export const filingStatusLabels: Record<string, string> = {
    pending: 'Pending',
    endorsed: 'Endorsed by Dept. Head',
    approved: 'Approved',
    denied: 'Denied',
};

export function FilingStatusBadge({ status }: { status: string }) {
    return (
        <Badge tone={statusTone[status]}>
            {filingStatusLabels[status] ?? status}
        </Badge>
    );
}

/**
 * One-line description of what a filing asks for.
 */
export function FilingDetails({ filing }: { filing: Filing }) {
    const dates =
        filing.date_to !== filing.date_from
            ? `${formatDate(filing.date_from)} – ${formatDate(filing.date_to)}`
            : formatDate(filing.date_from);

    let detail = '';

    switch (filing.type) {
        case 'unaccounted':
            detail = [
                filing.time_from && `In ${formatTime(filing.time_from)}`,
                filing.time_to && `Out ${formatTime(filing.time_to)}`,
            ]
                .filter(Boolean)
                .join(' · ');
            break;
        case 'overtime':
            detail = `${formatTime(filing.time_from)}–${formatTime(filing.time_to)}`;
            break;
        case 'early_out':
            detail = `Out at ${formatTime(filing.time_from)}`;
            break;
        case 'leave':
            detail = `${filing.is_half_day ? 'Half day' : 'Whole day'} · ${filing.with_pay ? 'With pay' : 'Without pay'}`;
            break;
        case 'shift_change':
            detail = filing.shift?.name ?? '';
            break;
    }

    return (
        <div>
            <div className="font-medium text-ink">
                {filingTypeLabels[filing.type] ?? filing.type}
                <span className="ml-2 font-normal text-ink-muted">{dates}</span>
            </div>
            {detail && <div className="text-xs text-ink-muted">{detail}</div>}
        </div>
    );
}
