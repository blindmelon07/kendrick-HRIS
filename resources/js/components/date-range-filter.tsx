import { router } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { Button, Input } from '@/components/ui';

/**
 * Cut-off picker: reloads the current page with `from`/`to` (plus any extra filters).
 */
export default function DateRangeFilter({
    url,
    from,
    to,
    extra = {},
    children,
}: {
    url: string;
    from: string;
    to: string;
    extra?: Record<string, string | number | null | undefined>;
    children?: ReactNode;
}) {
    const [range, setRange] = useState({ from, to });

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(url, { ...extra, ...range }, { preserveState: true });
    }

    return (
        <form
            onSubmit={submit}
            className="no-print flex flex-wrap items-end gap-2"
        >
            <label className="flex flex-col gap-1 text-xs text-ink-muted">
                From
                <Input
                    type="date"
                    value={range.from}
                    onChange={(event) =>
                        setRange({ ...range, from: event.target.value })
                    }
                    className="w-40"
                />
            </label>
            <label className="flex flex-col gap-1 text-xs text-ink-muted">
                To
                <Input
                    type="date"
                    value={range.to}
                    onChange={(event) =>
                        setRange({ ...range, to: event.target.value })
                    }
                    className="w-40"
                />
            </label>
            {children}
            <Button type="submit" variant="secondary">
                Apply
            </Button>
        </form>
    );
}
