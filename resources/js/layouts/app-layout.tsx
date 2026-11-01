import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import {
    attendance as myAttendance,
    payslips as myPayslips,
} from '@/routes/my';
import branches from '@/routes/branches';
import departments from '@/routes/departments';
import employees from '@/routes/employees';
import endorsements from '@/routes/endorsements';
import myFilings from '@/routes/my/filings';
import loans from '@/routes/payroll/loans';
import periods from '@/routes/payroll/periods';
import shifts from '@/routes/shifts';
import filingApprovals from '@/routes/timekeeping/filings';
import holidays from '@/routes/timekeeping/holidays';
import timeLogs from '@/routes/timekeeping/time-logs';
import workHours from '@/routes/timekeeping/work-hours';
import users from '@/routes/users';

type NavItem = { label: string; href: string };
type NavSection = { title: string; items: NavItem[] };

function useNavigation(): NavSection[] {
    const { auth } = usePage().props;
    const sections: NavSection[] = [
        {
            title: 'Overview',
            items: [{ label: 'Dashboard', href: dashboard.url() }],
        },
    ];

    if (auth.employee) {
        sections.push({
            title: 'My Kiosk',
            items: [
                { label: 'My Attendance', href: myAttendance.url() },
                { label: 'My Filings', href: myFilings.index.url() },
                { label: 'My Payslips', href: myPayslips.url() },
            ],
        });
    }

    if (auth.can.endorseFilings) {
        sections.push({
            title: 'Dept. Head',
            items: [{ label: 'Endorsements', href: endorsements.index.url() }],
        });
    }

    if (auth.can.manageEmployees) {
        sections.push({
            title: 'Employees',
            items: [
                { label: 'Employee Records', href: employees.index.url() },
                { label: 'Departments', href: departments.index.url() },
                { label: 'Branches', href: branches.index.url() },
                { label: 'Shifts', href: shifts.index.url() },
            ],
        });
    }

    if (auth.can.manageTimekeeping) {
        sections.push({
            title: 'Timekeeping',
            items: [
                { label: 'Time Logs', href: timeLogs.index.url() },
                {
                    label: 'Filings Approval',
                    href: filingApprovals.index.url(),
                },
                { label: 'Work Hours Summary', href: workHours.index.url() },
                { label: 'Holidays', href: holidays.index.url() },
            ],
        });
    }

    if (auth.can.managePayroll) {
        sections.push({
            title: 'Payroll',
            items: [
                { label: 'Payroll Periods', href: periods.index.url() },
                { label: 'Loans', href: loans.index.url() },
            ],
        });
    }

    if (auth.can.manageUsers) {
        sections.push({
            title: 'Administration',
            items: [{ label: 'User Accounts', href: users.index.url() }],
        });
    }

    return sections;
}

function isActive(currentUrl: string, href: string): boolean {
    const path = currentUrl.split('?')[0];

    return href === '/'
        ? path === '/'
        : path === href || path.startsWith(`${href}/`);
}

function FlashMessages() {
    const { flash } = usePage();
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        setVisible(true);
        const timer = setTimeout(() => setVisible(false), 6000);

        return () => clearTimeout(timer);
    }, [flash]);

    if (!visible || (!flash.success && !flash.error)) {
        return null;
    }

    return (
        <div className="no-print fixed right-4 bottom-4 z-50 flex max-w-sm flex-col gap-2">
            {flash.success && (
                <div
                    role="status"
                    className="rounded-lg border border-emerald-500/30 bg-surface px-4 py-3 text-sm text-ink shadow-lg"
                >
                    <span className="mr-2 font-semibold text-emerald-600">
                        ✓
                    </span>
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div
                    role="alert"
                    className="rounded-lg border border-red-500/30 bg-surface px-4 py-3 text-sm text-ink shadow-lg"
                >
                    <span className="mr-2 font-semibold text-red-600">!</span>
                    {flash.error}
                </div>
            )}
        </div>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth, name } = usePage().props;
    const { url } = usePage();
    const sections = useNavigation();
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => setMobileOpen(false), [url]);

    return (
        <div className="min-h-screen lg:flex">
            <aside
                className={cn(
                    'no-print fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-surface transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex h-14 items-center gap-2 border-b border-line px-5">
                    <img
                        src="/images/tristellar-wordmark.png"
                        alt={name}
                        className="h-9 w-auto"
                    />
                    <span className="rounded bg-brand-soft px-1.5 py-0.5 text-[11px] font-semibold tracking-wider text-brand">
                        HRIS
                    </span>
                </div>
                <nav className="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    {sections.map((section) => (
                        <div key={section.title}>
                            <div className="px-2 pb-1 text-[11px] font-semibold tracking-wider text-ink-muted uppercase">
                                {section.title}
                            </div>
                            <ul className="space-y-0.5">
                                {section.items.map((item) => (
                                    <li key={item.href}>
                                        <Link
                                            href={item.href}
                                            prefetch
                                            className={cn(
                                                'block rounded-md px-2 py-1.5 text-sm',
                                                isActive(url, item.href)
                                                    ? 'bg-brand-soft font-medium text-brand'
                                                    : 'text-ink-muted hover:bg-surface-muted hover:text-ink',
                                            )}
                                        >
                                            {item.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
                <div className="border-t border-line p-4">
                    <div className="truncate text-sm font-medium text-ink">
                        {auth.user?.name}
                    </div>
                    <div className="truncate text-xs text-ink-muted">
                        {auth.user?.email}
                    </div>
                    <Link
                        href={logout.url()}
                        method="post"
                        as="button"
                        className="mt-3 text-xs font-medium text-ink-muted hover:text-ink"
                    >
                        Log out
                    </Link>
                </div>
            </aside>

            {mobileOpen && (
                <button
                    type="button"
                    aria-label="Close menu"
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            <div className="min-w-0 flex-1">
                <header className="no-print sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur lg:hidden">
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="rounded-md border border-line px-2.5 py-1 text-sm text-ink"
                    >
                        Menu
                    </button>
                    <img
                        src="/images/tristellar-wordmark.png"
                        alt={name}
                        className="h-7 w-auto"
                    />
                </header>
                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {children}
                </main>
            </div>

            <FlashMessages />
        </div>
    );
}
