import { Link } from '@inertiajs/react';
import type {
    ButtonHTMLAttributes,
    InputHTMLAttributes,
    ReactNode,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';
import { cn } from '@/lib/utils';
import type { Option, Paginated } from '@/types';

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';

const buttonVariants: Record<ButtonVariant, string> = {
    primary: 'bg-brand text-brand-ink hover:opacity-90',
    secondary: 'border border-line bg-surface text-ink hover:bg-surface-muted',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    ghost: 'text-ink-muted hover:bg-surface-muted hover:text-ink',
};

export function buttonClasses(
    variant: ButtonVariant = 'primary',
    size: 'sm' | 'md' = 'md',
): string {
    return cn(
        'inline-flex items-center justify-center gap-2 rounded-md font-medium transition focus-visible:ring-2 focus-visible:ring-brand focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
        size === 'sm' ? 'h-8 px-3 text-xs' : 'h-9 px-4 text-sm',
        buttonVariants[variant],
    );
}

export function Button({
    variant = 'primary',
    size = 'md',
    className,
    type = 'button',
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
    size?: 'sm' | 'md';
}) {
    return (
        <button
            type={type}
            className={cn(buttonClasses(variant, size), className)}
            {...props}
        />
    );
}

const controlClasses =
    'block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink shadow-xs placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none disabled:opacity-60';

export function Input({
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return <input className={cn(controlClasses, className)} {...props} />;
}

export function Textarea({
    className,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            rows={3}
            className={cn(controlClasses, className)}
            {...props}
        />
    );
}

export function Select({
    options,
    placeholder,
    className,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & {
    options: Option[];
    placeholder?: string;
}) {
    return (
        <select className={cn(controlClasses, 'pr-8', className)} {...props}>
            {placeholder !== undefined && (
                <option value="">{placeholder}</option>
            )}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export function Checkbox({
    label,
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement> & { label: ReactNode }) {
    return (
        <label
            className={cn(
                'inline-flex items-center gap-2 text-sm text-ink',
                className,
            )}
        >
            <input
                type="checkbox"
                className="size-4 rounded border-line accent-brand"
                {...props}
            />
            {label}
        </label>
    );
}

export function Field({
    label,
    error,
    hint,
    className,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <label className={cn('flex flex-col gap-1.5', className)}>
            <span className="text-sm font-medium text-ink">{label}</span>
            {children}
            {hint && !error && (
                <span className="text-xs text-ink-muted">{hint}</span>
            )}
            {error && <span className="text-xs text-red-600">{error}</span>}
        </label>
    );
}

export function Card({
    title,
    description,
    actions,
    className,
    children,
}: {
    title?: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'rounded-lg border border-line bg-surface shadow-xs',
                className,
            )}
        >
            {(title || actions) && (
                <header className="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        {title && (
                            <h2 className="text-sm font-semibold text-ink">
                                {title}
                            </h2>
                        )}
                        {description && (
                            <p className="mt-0.5 text-xs text-ink-muted">
                                {description}
                            </p>
                        )}
                    </div>
                    {actions && (
                        <div className="flex items-center gap-2">{actions}</div>
                    )}
                </header>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}

export function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 className="text-xl font-semibold tracking-tight text-ink">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-ink-muted">{description}</p>
                )}
            </div>
            {actions && (
                <div className="no-print flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </div>
    );
}

const badgeTones = {
    gray: 'bg-surface-muted text-ink-muted',
    blue: 'bg-brand-soft text-brand',
    green: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    amber: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
    red: 'bg-red-500/15 text-red-700 dark:text-red-400',
};

export type BadgeTone = keyof typeof badgeTones;

export function Badge({
    tone = 'gray',
    children,
}: {
    tone?: BadgeTone;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                badgeTones[tone],
            )}
        >
            {children}
        </span>
    );
}

export const statusTone: Record<string, BadgeTone> = {
    pending: 'amber',
    endorsed: 'blue',
    approved: 'green',
    denied: 'red',
    draft: 'gray',
    processed: 'blue',
    finalized: 'green',
    active: 'green',
    resigned: 'gray',
    terminated: 'red',
    present: 'green',
    absent: 'red',
    incomplete: 'amber',
    leave: 'blue',
    holiday: 'blue',
    rest_day: 'gray',
    not_employed: 'gray',
};

export function Table({
    head,
    children,
    empty,
    isEmpty,
}: {
    head: ReactNode;
    children: ReactNode;
    empty?: string;
    isEmpty?: boolean;
}) {
    return (
        <div className="-mx-5 -my-5 overflow-x-auto">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-line bg-surface-muted text-xs tracking-wide text-ink-muted uppercase">
                    {head}
                </thead>
                <tbody className="divide-y divide-line">
                    {isEmpty ? (
                        <tr>
                            <td
                                colSpan={99}
                                className="px-5 py-10 text-center text-sm text-ink-muted"
                            >
                                {empty ?? 'Nothing to show yet.'}
                            </td>
                        </tr>
                    ) : (
                        children
                    )}
                </tbody>
            </table>
        </div>
    );
}

export function Th({
    className,
    children,
}: {
    className?: string;
    children?: ReactNode;
}) {
    return (
        <th
            className={cn(
                'px-5 py-2.5 font-medium whitespace-nowrap',
                className,
            )}
        >
            {children}
        </th>
    );
}

export function Td({
    className,
    children,
    colSpan,
}: {
    className?: string;
    children?: ReactNode;
    colSpan?: number;
}) {
    return (
        <td
            colSpan={colSpan}
            className={cn('px-5 py-3 align-middle text-ink', className)}
        >
            {children}
        </td>
    );
}

export function Pagination({ page }: { page: Paginated<unknown> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav className="no-print mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted">
            <span>
                Showing {page.from}–{page.to} of {page.total}
            </span>
            <div className="flex flex-wrap gap-1">
                {page.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'rounded-md px-3 py-1.5',
                                link.active
                                    ? 'bg-brand text-brand-ink'
                                    : 'hover:bg-surface-muted',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="px-3 py-1.5 opacity-40"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </nav>
    );
}

/**
 * Employee photo, or initials on a brand-tinted circle when there is none.
 */
export function Avatar({
    name,
    photoUrl,
    size = 'md',
}: {
    name: string;
    photoUrl?: string | null;
    size?: 'sm' | 'md' | 'lg';
}) {
    const sizes = {
        sm: 'size-8 text-xs',
        md: 'size-12 text-sm',
        lg: 'size-24 text-2xl',
    };
    // "Dela Cruz, Juan K." → "DJ": surname and first-name initials, so
    // multi-word surnames like Dela Cruz count as one.
    const initials = (
        name.includes(',')
            ? name.split(',').map((part) => part.trim())
            : name.split(/\s+/)
    )
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');

    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={name}
                className={cn(
                    'shrink-0 rounded-full border border-line bg-surface-muted object-cover',
                    sizes[size],
                )}
            />
        );
    }

    return (
        <span
            aria-hidden
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-full bg-brand-soft font-semibold text-brand',
                sizes[size],
            )}
        >
            {initials}
        </span>
    );
}

export function Stat({
    label,
    value,
    hint,
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
}) {
    return (
        <div className="rounded-lg border border-line bg-surface p-4 shadow-xs">
            <div className="text-xs font-medium text-ink-muted">{label}</div>
            <div className="mt-1 text-2xl font-semibold tracking-tight text-ink tabular-nums">
                {value}
            </div>
            {hint && <div className="mt-1 text-xs text-ink-muted">{hint}</div>}
        </div>
    );
}
