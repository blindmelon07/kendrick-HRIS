import type { Option } from '@/types';

const pesoFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

export function peso(value: string | number | null | undefined): string {
    return pesoFormatter.format(Number(value ?? 0));
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'en-PH',
        { month: 'short', day: 'numeric', year: 'numeric' },
    );
}

export function formatTime(value: string | null | undefined): string {
    return value ? value.slice(0, 5) : '—';
}

export function labelFor(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

/**
 * Display a minute count as "1h 05m" (or "—" for zero).
 */
export function minutes(value: number): string {
    if (!value) {
        return '—';
    }

    const hours = Math.floor(value / 60);
    const rest = value % 60;

    return hours ? `${hours}h ${String(rest).padStart(2, '0')}m` : `${rest}m`;
}

export const weekdays = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];
