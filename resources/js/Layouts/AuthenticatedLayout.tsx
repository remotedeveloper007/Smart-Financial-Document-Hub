/**
 * Authenticated layout
 * - Provides the application shell for authenticated pages.
 * - Keeps presentation only: no business logic or data fetching here.
 * - Navigation links (desktop + mobile), user dropdown, and a header slot.
 */
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

type Theme = 'light' | 'dark';

type NavigationIconName =
    | 'dashboard'
    | 'team'
    | 'documents'
    | 'transactions'
    | 'search'
    | 'invoices'
    | 'receipts'
    | 'contracts'
    | 'integrations'
    | 'billing'
    | 'ai'
    | 'audit'
    | 'system'
    | 'queue'
    | 'profile'
    | 'logout';

const navigationIconPaths: Record<NavigationIconName, string> = {
    dashboard: 'M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z',
    team: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
    documents: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M8 13h8 M8 17h8',
    transactions: 'M3 7h18 M3 12h18 M3 17h18 M7 3v18 M17 3v18',
    search: 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16 M21 21l-4.35-4.35',
    invoices: 'M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3 M16 3h5v5 M21 3l-9 9 M7 8h4 M7 12h3 M7 16h2',
    receipts: 'M4 2v20l4-2 4 2 4-2 4 2V2l-4 2-4-2-4 2z M8 9h8 M8 13h8',
    contracts: 'M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-5-5z M14 3v6h6 M8 13h8 M8 17h8',
    integrations: 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71 M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
    billing: 'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
    ai: 'M12 3v3 M18.36 5.64l-2.12 2.12 M21 12h-3 M18.36 18.36l-2.12-2.12 M12 21v-3 M5.64 18.36l2.12-2.12 M3 12h3 M5.64 5.64l2.12 2.12 M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
    audit: 'M9 11l3 3L22 4 M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
    system: 'M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11 M9 12l2 2 4-4',
    queue: 'M8 6h13 M8 12h13 M8 18h13 M3 6h.01 M3 12h.01 M3 18h.01',
    profile: 'M20 21a8 8 0 0 0-16 0 M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8',
    logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
};

function NavigationIcon({ name }: { name: NavigationIconName }) {
    return (
        <svg
            aria-hidden="true"
            className="h-5 w-5 shrink-0"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="1.8"
            viewBox="0 0 24 24"
        >
            <path d={navigationIconPaths[name]} />
        </svg>
    );
}

function SidebarLink({
    href,
    label,
    icon,
    active,
    collapsed,
}: {
    href: string;
    label: string;
    icon: NavigationIconName;
    active: boolean;
    collapsed: boolean;
}) {
    return (
        <Link
            href={href}
            aria-label={collapsed ? label : undefined}
            aria-current={active ? 'page' : undefined}
            title={collapsed ? label : undefined}
            className={`group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors ${
                collapsed ? 'justify-center px-0' : ''
            } ${
                active
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'
            }`}
        >
            {active && (
                <span
                    aria-hidden="true"
                    className="absolute inset-y-2 left-0 w-1 rounded-full bg-indigo-600 dark:bg-indigo-400"
                />
            )}
            <NavigationIcon name={icon} />
            <span className={collapsed ? 'sr-only' : undefined}>{label}</span>
        </Link>
    );
}

function AppearanceToggle({
    theme,
    onToggle,
}: {
    theme: Theme;
    onToggle: () => void;
}) {
    const nextTheme = theme === 'dark' ? 'light' : 'dark';

    return (
        <button
            type="button"
            onClick={onToggle}
            aria-label={`Switch to ${nextTheme} mode`}
            aria-pressed={theme === 'dark'}
            title={`Switch to ${nextTheme} mode`}
            className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 active:scale-95"
        >
            {theme === 'dark' ? (
                <svg aria-hidden="true" className="h-5 w-5" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2 M12 20v2 M4.93 4.93l1.42 1.42 M17.65 17.65l1.42 1.42 M2 12h2 M20 12h2 M4.93 19.07l1.42-1.42 M17.65 6.35l1.42-1.42" />
                </svg>
            ) : (
                <svg aria-hidden="true" className="h-5 w-5" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
                    <path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z" />
                </svg>
            )}
        </button>
    );
}

type TeamSummary = {
    id: number;
    name: string;
};

function TeamSwitcher({
    teams,
    activeTeam,
}: {
    teams: TeamSummary[];
    activeTeam: TeamSummary | null;
}) {
    if (teams.length < 2) {
        return null;
    }

    return (
        <select
            aria-label="Active team"
            value={activeTeam?.id ?? ''}
            onChange={(event) =>
                router.post(
                    route('team.active.update'),
                    { team_id: Number(event.currentTarget.value) },
                    { preserveScroll: true },
                )
            }
            className="max-w-40 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
            {teams.map((team) => (
                <option key={team.id} value={team.id}>
                    {team.name}
                </option>
            ))}
        </select>
    );
}

function NavigationSection({
    title,
    collapsed,
    children,
}: PropsWithChildren<{ title: string; collapsed: boolean }>) {
    return (
        <section>
            <h2
                className={`px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 ${
                    collapsed ? 'sr-only' : ''
                }`}
            >
                {title}
            </h2>
            <div className="mt-2 space-y-1">{children}</div>
        </section>
    );
}

type AuthenticatedPageProps = {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    can?: {
        isPlatformAdmin?: boolean;
        viewAdmin?: boolean;
        permissions?: string[];
    };
    tenant?: {
        activeTeam: TeamSummary | null;
        teams: TeamSummary[];
    };
};

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    // Inertia-provided root props: auth user, permissions, team context, etc.
    // const props = usePage().props;
    const props = usePage<AuthenticatedPageProps>().props;
    const user = props.auth?.user;
    const activeTeam = props.tenant?.activeTeam ?? null;
    const teams = props.tenant?.teams ?? [];
    const isPlatformAdmin = props.can?.isPlatformAdmin ?? false;
    const canViewAdmin = props.can?.viewAdmin ?? false;
    const permissions = new Set(props.can?.permissions ?? []);
    const can = (permission: string) => permissions.has(permission);
    const canManageUsers = can('manage_users');
    const canCreateDocuments = can('create_documents');
    const canViewTransactions = can('view_transactions');
    const canViewInvoices = can('view_invoices');
    const canViewReceipts = can('view_receipts');
    const canViewContracts = can('view_contracts');
    const canManageIntegrations = can('manage_integrations');
    const canManageBilling = can('manage_billing');
    const canUseAi = can('use_ai');
    const canViewSystemAuditLogs = canViewAdmin;

    // Keep these display preferences in the browser so they follow the user between page visits.
    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(
        () =>
            typeof window !== 'undefined' &&
            window.localStorage.getItem('financial-hub-sidebar-collapsed') ===
                'true',
    );
    const [theme, setTheme] = useState<Theme>(() =>
        typeof window !== 'undefined' &&
        window.localStorage.getItem('financial-hub-theme') === 'dark'
            ? 'dark'
            : 'light',
    );

    useEffect(() => {
        window.localStorage.setItem(
            'financial-hub-sidebar-collapsed',
            String(sidebarCollapsed),
        );
    }, [sidebarCollapsed]);

    useEffect(() => {
        document.documentElement.dataset.theme = theme;
        window.localStorage.setItem('financial-hub-theme', theme);
    }, [theme]);

    return (
        <div
            className={`min-h-screen bg-slate-50 transition-[padding] duration-200 dark:bg-slate-950 ${
                sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'
            }`}
        >
            <aside
                className={`fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-slate-200 bg-white transition-[width] duration-200 dark:border-slate-800 dark:bg-slate-900 lg:flex ${
                    sidebarCollapsed ? 'w-20' : 'w-72'
                }`}
            >
                <div
                    className={`flex h-16 shrink-0 items-center border-b border-slate-200 dark:border-slate-800 ${
                        sidebarCollapsed
                            ? 'justify-center px-2'
                            : 'justify-between gap-2 px-4'
                    }`}
                >
                    <Link
                        href={route('dashboard')}
                        aria-label="Financial Hub dashboard"
                        className={`flex min-w-0 items-center gap-3 ${
                            sidebarCollapsed ? 'justify-center' : ''
                        }`}
                    >
                        <ApplicationLogo className="block h-9 w-auto shrink-0 fill-current text-indigo-700 dark:text-indigo-400" />
                        {!sidebarCollapsed && (
                            <span className="truncate font-semibold text-slate-900 dark:text-slate-100">
                                Financial Hub
                            </span>
                        )}
                    </Link>
                    <button
                        type="button"
                        onClick={() =>
                            setSidebarCollapsed((collapsed) => !collapsed)
                        }
                        aria-label={
                            sidebarCollapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        }
                        aria-expanded={!sidebarCollapsed}
                        title={
                            sidebarCollapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        }
                        className={`inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white ${
                            sidebarCollapsed ? 'absolute -right-3 top-14 border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800' : ''
                        }`}
                    >
                        <svg
                            aria-hidden="true"
                            className="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d={
                                    sidebarCollapsed
                                        ? 'M9 18l6-6-6-6'
                                        : 'M15 18l-6-6 6-6'
                                }
                            />
                        </svg>
                    </button>
                </div>

                <nav
                    aria-label="Primary navigation"
                    className={`flex-1 space-y-6 overflow-y-auto py-6 ${
                        sidebarCollapsed ? 'px-3' : 'px-4'
                    }`}
                >
                    {!isPlatformAdmin && (
                        <>
                            <NavigationSection title="Workspace" collapsed={sidebarCollapsed}>
                                <SidebarLink href={route('dashboard')} label="Dashboard" icon="dashboard" active={route().current('dashboard')} collapsed={sidebarCollapsed} />
                                {canManageUsers && <SidebarLink href={route('team.members.page')} label="Team Members" icon="team" active={route().current('team.members.*')} collapsed={sidebarCollapsed} />}
                            </NavigationSection>

                            {(canCreateDocuments || canViewTransactions || canViewInvoices || canViewReceipts || canViewContracts) && (
                                <NavigationSection title="Financial data" collapsed={sidebarCollapsed}>
                                    {canCreateDocuments && <SidebarLink href={route('documents.upload')} label="Documents" icon="documents" active={route().current('documents.*')} collapsed={sidebarCollapsed} />}
                                    {canViewTransactions && <SidebarLink href={route('transactions.index')} label="Transactions" icon="transactions" active={route().current('transactions.*')} collapsed={sidebarCollapsed} />}
                                    {canViewTransactions && <SidebarLink href={route('search.page')} label="Search" icon="search" active={route().current('search.*')} collapsed={sidebarCollapsed} />}
                                    {canViewInvoices && <SidebarLink href={route('invoices.page')} label="Invoices" icon="invoices" active={route().current('invoices.*')} collapsed={sidebarCollapsed} />}
                                    {canViewReceipts && <SidebarLink href={route('receipts.page')} label="Receipts" icon="receipts" active={route().current('receipts.*')} collapsed={sidebarCollapsed} />}
                                    {canViewContracts && <SidebarLink href={route('contracts.page')} label="Contracts" icon="contracts" active={route().current('contracts.*')} collapsed={sidebarCollapsed} />}
                                </NavigationSection>
                            )}

                            {(canManageIntegrations || canManageBilling || canUseAi) && (
                                <NavigationSection title="Tools" collapsed={sidebarCollapsed}>
                                    {canManageIntegrations && <SidebarLink href={route('integrations.page')} label="Integrations" icon="integrations" active={route().current('integrations.*')} collapsed={sidebarCollapsed} />}
                                    {canManageBilling && <SidebarLink href={route('billing.page')} label="Billing" icon="billing" active={route().current('billing.*')} collapsed={sidebarCollapsed} />}
                                    {canUseAi && <SidebarLink href={route('ai.page')} label="AI Assistant" icon="ai" active={route().current('ai.*')} collapsed={sidebarCollapsed} />}
                                </NavigationSection>
                            )}
                        </>
                    )}

                    {canViewAdmin && (
                        <NavigationSection title="Administration" collapsed={sidebarCollapsed}>
                            <SidebarLink href={route('admin.system_health.index')} label="System Health" icon="system" active={route().current('admin.system_health.*')} collapsed={sidebarCollapsed} />
                            <SidebarLink href={route('queue.status.page')} label="Queue Monitor" icon="queue" active={route().current('queue.*')} collapsed={sidebarCollapsed} />
                            <SidebarLink href={route('admin.fault_logs.index')} label="Fault Logs" icon="system" active={route().current('admin.fault_logs.*')} collapsed={sidebarCollapsed} />
                            <SidebarLink href={route('admin.failed_jobs.index')} label="Failed Jobs" icon="queue" active={route().current('admin.failed_jobs.*')} collapsed={sidebarCollapsed} />
                            <SidebarLink href={route('audit.logs.page')} label="System Audit Logs" icon="audit" active={route().current('audit.logs.*')} collapsed={sidebarCollapsed} />
                        </NavigationSection>
                    )}
                </nav>

                <div
                    className={`border-t border-slate-200 py-4 dark:border-slate-800 ${
                        sidebarCollapsed ? 'px-3' : 'px-4'
                    }`}
                >
                    {!sidebarCollapsed && (
                        <>
                            <p className="truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                                {user.name}
                            </p>
                            <p className="truncate text-xs text-slate-500 dark:text-slate-400">
                                {user.email}
                            </p>
                        </>
                    )}
                    <div
                        className={`mt-3 flex ${
                            sidebarCollapsed
                                ? 'flex-col items-center gap-1'
                                : 'items-center justify-between'
                        }`}
                    >
                        <Link
                            href={route('profile.edit')}
                            aria-label={sidebarCollapsed ? 'Profile' : undefined}
                            title={sidebarCollapsed ? 'Profile' : undefined}
                            className={`flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/60 ${
                                sidebarCollapsed ? 'justify-center px-0' : ''
                            }`}
                        >
                            <NavigationIcon name="profile" />
                            <span className={sidebarCollapsed ? 'sr-only' : undefined}>Profile</span>
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            aria-label={sidebarCollapsed ? 'Log Out' : undefined}
                            title={sidebarCollapsed ? 'Log Out' : undefined}
                            className={`flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white ${
                                sidebarCollapsed ? 'justify-center px-0' : ''
                            }`}
                        >
                            <NavigationIcon name="logout" />
                            <span className={sidebarCollapsed ? 'sr-only' : undefined}>Log Out</span>
                        </Link>
                    </div>
                </div>
            </aside>

            <header className="sticky top-0 z-40 hidden h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:flex">
                <TeamSwitcher teams={teams} activeTeam={activeTeam} />
                <AppearanceToggle
                    theme={theme}
                    onToggle={() =>
                        setTheme((current) =>
                            current === 'dark' ? 'light' : 'dark',
                        )
                    }
                />
            </header>

            {/* Keep the existing responsive top navigation for small screens; desktop users use the sidebar above. */}
            <nav className="sticky top-0 z-40 border-b border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:hidden">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            {/* Desktop primary nav links (hidden on small screens) */}
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {isPlatformAdmin ? (
                                    <NavLink
                                        href={route('admin.system_health.index')}
                                        active={route().current('admin.system_health.index')}
                                    >
                                        System Health
                                    </NavLink>
                                ) : (
                                    <NavLink
                                        href={route('dashboard')}
                                        active={route().current('dashboard')}
                                    >
                                        Dashboard
                                    </NavLink>
                                )}
                                {!isPlatformAdmin && canManageUsers && <NavLink
                                    href={route('team.members.page')}
                                    active={route().current('team.members.page')}
                                >
                                    Team Members
                                </NavLink>}
                                {!isPlatformAdmin && canCreateDocuments && <NavLink
                                    href={route('documents.upload')}
                                    active={route().current('documents.upload')}
                                >
                                    Documents
                                </NavLink>}
                                {!isPlatformAdmin && canViewTransactions && <NavLink
                                    href={route('transactions.index')}
                                    active={route().current('transactions.index')}
                                >
                                    Transactions
                                </NavLink>}
                                {!isPlatformAdmin && canViewTransactions && <NavLink
                                    href={route('search.page')}
                                    active={route().current('search.page')}
                                >
                                    Search
                                </NavLink>}
                                {!isPlatformAdmin && canViewInvoices && <NavLink
                                    href={route('invoices.page')}
                                    active={route().current('invoices.page')}
                                >
                                    Invoices
                                </NavLink>}
                                {!isPlatformAdmin && canManageBilling && <NavLink
                                    href={route('billing.page')}
                                    active={route().current('billing.page')}
                                >
                                    Billing
                                </NavLink>}
                                {!isPlatformAdmin && canViewReceipts && <NavLink
                                    href={route('receipts.page')}
                                    active={route().current('receipts.page')}
                                >
                                    Receipts
                                </NavLink>}
                                {!isPlatformAdmin && canViewContracts && <NavLink
                                    href={route('contracts.page')}
                                    active={route().current('contracts.page')}
                                >
                                    Contracts
                                </NavLink>}
                                {!isPlatformAdmin && canManageIntegrations && <NavLink
                                    href={route('integrations.page')}
                                    active={route().current('integrations.page')}
                                >
                                    Integrations
                                </NavLink>}
                                {canViewAdmin && !isPlatformAdmin && (
                                    <NavLink
                                        href={route('queue.status.page')}
                                        active={route().current('queue.status.page')}
                                    >
                                        Queue Monitor
                                    </NavLink>
                                )}
                                {!isPlatformAdmin && canUseAi && <NavLink
                                    href={route('ai.page')}
                                    active={route().current('ai.page')}
                                >
                                    AI Assistant
                                </NavLink>}
                                {isPlatformAdmin && (
                                    <>
                                        <NavLink href={route('queue.status.page')} active={route().current('queue.status.page')}>Queue Monitor</NavLink>
                                        <NavLink href={route('admin.fault_logs.index')} active={route().current('admin.fault_logs.*')}>Fault Logs</NavLink>
                                        <NavLink href={route('admin.failed_jobs.index')} active={route().current('admin.failed_jobs.*')}>Failed Jobs</NavLink>
                                        <NavLink href={route('audit.logs.page')} active={route().current('audit.logs.*')}>System Audit Logs</NavLink>
                                    </>
                                )}
                                {!isPlatformAdmin && canViewSystemAuditLogs && <NavLink
                                    href={route('audit.logs.page')}
                                    active={route().current('audit.logs.page')}
                                >
                                    System Audit Logs
                                </NavLink>}
                            </div>
                        </div>

                        {/* Desktop user menu */}
                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center gap-2 sm:hidden">
                            <TeamSwitcher teams={teams} activeTeam={activeTeam} />
                            <AppearanceToggle
                                theme={theme}
                                onToggle={() =>
                                    setTheme((current) =>
                                        current === 'dark' ? 'light' : 'dark',
                                    )
                                }
                            />
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                aria-label={
                                    showingNavigationDropdown
                                        ? 'Close navigation menu'
                                        : 'Open navigation menu'
                                }
                                aria-expanded={showingNavigationDropdown}
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    {/* Mobile nav (collapsed by default) - mirrors desktop links */}
                    <div className="space-y-1 pb-3 pt-2">
                        {isPlatformAdmin ? (
                            <ResponsiveNavLink
                                href={route('admin.system_health.index')}
                                active={route().current('admin.system_health.index')}
                            >
                                System Health
                            </ResponsiveNavLink>
                        ) : (
                            <ResponsiveNavLink
                                href={route('dashboard')}
                                active={route().current('dashboard')}
                            >
                                Dashboard
                            </ResponsiveNavLink>
                        )}
                        {!isPlatformAdmin && canManageUsers && <ResponsiveNavLink
                            href={route('team.members.page')}
                            active={route().current('team.members.page')}
                        >
                            Team Members
                        </ResponsiveNavLink>}
                        {canViewAdmin && !isPlatformAdmin && (
                            <ResponsiveNavLink
                                href={route('admin.system_health.index')}
                                active={route().current('admin.system_health.index')}
                            >
                                System Health
                            </ResponsiveNavLink>
                        )}
                        {!isPlatformAdmin && canCreateDocuments && <ResponsiveNavLink
                            href={route('documents.upload')}
                            active={route().current('documents.upload')}
                        >
                            Documents
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canViewTransactions && <ResponsiveNavLink
                            href={route('transactions.index')}
                            active={route().current('transactions.index')}
                        >
                            Transactions
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canViewTransactions && <ResponsiveNavLink
                            href={route('search.page')}
                            active={route().current('search.page')}
                        >
                            Search
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canViewInvoices && <ResponsiveNavLink
                            href={route('invoices.page')}
                            active={route().current('invoices.page')}
                        >
                            Invoices
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canManageBilling && <ResponsiveNavLink
                            href={route('billing.page')}
                            active={route().current('billing.page')}
                        >
                            Billing
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canViewReceipts && <ResponsiveNavLink
                            href={route('receipts.page')}
                            active={route().current('receipts.page')}
                        >
                            Receipts
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canViewContracts && <ResponsiveNavLink
                            href={route('contracts.page')}
                            active={route().current('contracts.page')}
                        >
                            Contracts
                        </ResponsiveNavLink>}
                        {!isPlatformAdmin && canManageIntegrations && <ResponsiveNavLink
                            href={route('integrations.page')}
                            active={route().current('integrations.page')}
                        >
                            Integrations
                        </ResponsiveNavLink>}
                        {canViewAdmin && !isPlatformAdmin && (
                            <ResponsiveNavLink
                                href={route('queue.status.page')}
                                active={route().current('queue.status.page')}
                            >
                                Queue Monitor
                            </ResponsiveNavLink>
                        )}
                        {!isPlatformAdmin && canUseAi && <ResponsiveNavLink
                            href={route('ai.page')}
                            active={route().current('ai.page')}
                        >
                            AI Assistant
                        </ResponsiveNavLink>}
                        {isPlatformAdmin && (
                            <>
                                <ResponsiveNavLink href={route('queue.status.page')} active={route().current('queue.status.page')}>Queue Monitor</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('admin.fault_logs.index')} active={route().current('admin.fault_logs.*')}>Fault Logs</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('admin.failed_jobs.index')} active={route().current('admin.failed_jobs.*')}>Failed Jobs</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('audit.logs.page')} active={route().current('audit.logs.*')}>System Audit Logs</ResponsiveNavLink>
                            </>
                        )}
                        {!isPlatformAdmin && canViewSystemAuditLogs && <ResponsiveNavLink
                            href={route('audit.logs.page')}
                            active={route().current('audit.logs.page')}
                        >
                            System Audit Logs
                        </ResponsiveNavLink>}
                    </div>

                    {/* Mobile user info and actions */}
                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Optional header slot: pages pass `header` to render page title/actions */}
            {header && (
                <header className="bg-white shadow dark:bg-slate-900 lg:sticky lg:top-16 lg:z-30">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {/* Main content area where page components are injected */}
            <main>{children}</main>
        </div>
    );
}
