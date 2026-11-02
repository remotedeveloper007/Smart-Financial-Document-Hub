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
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

type AuthenticatedPageProps = {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    can?: {
        viewAdmin?: boolean;
        permissions?: string[];
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
    const canViewAuditLogs = can('view_audit_logs');

    // Local UI state: controls mobile nav collapse/expand
    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-slate-50 lg:pl-72">
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-200 bg-white lg:flex">
                <Link href={route('dashboard')} className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-6">
                    <ApplicationLogo className="block h-9 w-auto fill-current text-indigo-700" />
                    <span className="font-semibold text-slate-900">Financial Hub</span>
                </Link>
                <nav aria-label="Primary navigation" className="flex-1 space-y-6 overflow-y-auto px-4 py-6">
                    <div>
                        <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Workspace</p>
                        <div className="mt-2 space-y-1">
                            <Link href={route('dashboard')} aria-current={route().current('dashboard') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Dashboard</Link>
                            {canManageUsers && <Link href={route('team.members.page')} aria-current={route().current('team.members.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('team.members.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Team Members</Link>}
                        </div>
                    </div>
                    {(canCreateDocuments || canViewTransactions || canViewInvoices || canViewReceipts || canViewContracts) && <div>
                        <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Financial data</p>
                        <div className="mt-2 space-y-1">
                            {canCreateDocuments && <Link href={route('documents.upload')} aria-current={route().current('documents.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('documents.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Documents</Link>}
                            {canViewTransactions && <>
                                <Link href={route('transactions.index')} aria-current={route().current('transactions.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('transactions.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Transactions</Link>
                                <Link href={route('search.page')} aria-current={route().current('search.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('search.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Search</Link>
                            </>}
                            {canViewInvoices && <Link href={route('invoices.page')} aria-current={route().current('invoices.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('invoices.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Invoices</Link>}
                            {canViewReceipts && <Link href={route('receipts.page')} aria-current={route().current('receipts.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('receipts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Receipts</Link>}
                            {canViewContracts && <Link href={route('contracts.page')} aria-current={route().current('contracts.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('contracts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Contracts</Link>}
                        </div>
                    </div>}
                    {(canManageIntegrations || canManageBilling || canUseAi || canViewAuditLogs) && <div>
                        <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Tools</p>
                        <div className="mt-2 space-y-1">
                            {canManageIntegrations && <Link href={route('integrations.page')} aria-current={route().current('integrations.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('integrations.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Integrations</Link>}
                            {canManageBilling && <Link href={route('billing.page')} aria-current={route().current('billing.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('billing.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Billing</Link>}
                            {canUseAi && <Link href={route('ai.page')} aria-current={route().current('ai.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('ai.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>AI Assistant</Link>}
                            {canViewAuditLogs && <Link href={route('audit.logs.page')} aria-current={route().current('audit.logs.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('audit.logs.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Audit Logs</Link>}
                        </div>
                    </div>}
                    {canViewAdmin && (
                        <div>
                            <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Administration</p>
                            <div className="mt-2 space-y-1">
                                <Link href={route('admin.system_health.index')} aria-current={route().current('admin.system_health.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('admin.system_health.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>System Health</Link>
                                <Link href={route('queue.status.page')} aria-current={route().current('queue.*') ? 'page' : undefined} className={`block rounded-lg px-3 py-2 text-sm font-medium ${route().current('queue.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'}`}>Queue Monitor</Link>
                            </div>
                        </div>
                    )}
                </nav>
                <div className="border-t border-slate-200 p-4">
                    <p className="truncate text-sm font-medium text-slate-900">{user.name}</p>
                    <p className="truncate text-xs text-slate-500">{user.email}</p>
                    <div className="mt-3 flex items-center justify-between">
                        <Link href={route('profile.edit')} className="text-sm text-indigo-700 hover:text-indigo-900">Profile</Link>
                        <Link href={route('logout')} method="post" as="button" className="text-sm text-slate-600 hover:text-slate-900">Log Out</Link>
                    </div>
                </div>
            </aside>

            {/* Keep the existing responsive top navigation for small screens; desktop users use the sidebar above. */}
            <nav className="border-b border-gray-100 bg-white lg:hidden">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            {/* Desktop primary nav links (hidden on small screens) */}
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Dashboard
                                </NavLink>
                                {canViewAdmin && (
                                    <NavLink
                                        href={route('admin.system_health.index')}
                                        active={route().current('admin.system_health.index')}
                                    >
                                        System Health
                                    </NavLink>
                                )}
                                {canManageUsers && <NavLink
                                    href={route('team.members.page')}
                                    active={route().current('team.members.page')}
                                >
                                    Team Members
                                </NavLink>}
                                {canCreateDocuments && <NavLink
                                    href={route('documents.upload')}
                                    active={route().current('documents.upload')}
                                >
                                    Documents
                                </NavLink>}
                                {canViewTransactions && <NavLink
                                    href={route('transactions.index')}
                                    active={route().current('transactions.index')}
                                >
                                    Transactions
                                </NavLink>}
                                {canViewTransactions && <NavLink
                                    href={route('search.page')}
                                    active={route().current('search.page')}
                                >
                                    Search
                                </NavLink>}
                                {canViewInvoices && <NavLink
                                    href={route('invoices.page')}
                                    active={route().current('invoices.page')}
                                >
                                    Invoices
                                </NavLink>}
                                {canManageBilling && <NavLink
                                    href={route('billing.page')}
                                    active={route().current('billing.page')}
                                >
                                    Billing
                                </NavLink>}
                                {canViewReceipts && <NavLink
                                    href={route('receipts.page')}
                                    active={route().current('receipts.page')}
                                >
                                    Receipts
                                </NavLink>}
                                {canViewContracts && <NavLink
                                    href={route('contracts.page')}
                                    active={route().current('contracts.page')}
                                >
                                    Contracts
                                </NavLink>}
                                {canManageIntegrations && <NavLink
                                    href={route('integrations.page')}
                                    active={route().current('integrations.page')}
                                >
                                    Integrations
                                </NavLink>}
                                {canViewAdmin && (
                                    <NavLink
                                        href={route('queue.status.page')}
                                        active={route().current('queue.status.page')}
                                    >
                                        Queue Monitor
                                    </NavLink>
                                )}
                                {canUseAi && <NavLink
                                    href={route('ai.page')}
                                    active={route().current('ai.page')}
                                >
                                    AI Assistant
                                </NavLink>}
                                {canViewAuditLogs && <NavLink
                                    href={route('audit.logs.page')}
                                    active={route().current('audit.logs.page')}
                                >
                                    Audit Logs
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

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
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
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        {canManageUsers && <ResponsiveNavLink
                            href={route('team.members.page')}
                            active={route().current('team.members.page')}
                        >
                            Team Members
                        </ResponsiveNavLink>}
                        {canViewAdmin && (
                            <ResponsiveNavLink
                                href={route('admin.system_health.index')}
                                active={route().current('admin.system_health.index')}
                            >
                                System Health
                            </ResponsiveNavLink>
                        )}
                        {canCreateDocuments && <ResponsiveNavLink
                            href={route('documents.upload')}
                            active={route().current('documents.upload')}
                        >
                            Documents
                        </ResponsiveNavLink>}
                        {canViewTransactions && <ResponsiveNavLink
                            href={route('transactions.index')}
                            active={route().current('transactions.index')}
                        >
                            Transactions
                        </ResponsiveNavLink>}
                        {canViewTransactions && <ResponsiveNavLink
                            href={route('search.page')}
                            active={route().current('search.page')}
                        >
                            Search
                        </ResponsiveNavLink>}
                        {canViewInvoices && <ResponsiveNavLink
                            href={route('invoices.page')}
                            active={route().current('invoices.page')}
                        >
                            Invoices
                        </ResponsiveNavLink>}
                        {canManageBilling && <ResponsiveNavLink
                            href={route('billing.page')}
                            active={route().current('billing.page')}
                        >
                            Billing
                        </ResponsiveNavLink>}
                        {canViewReceipts && <ResponsiveNavLink
                            href={route('receipts.page')}
                            active={route().current('receipts.page')}
                        >
                            Receipts
                        </ResponsiveNavLink>}
                        {canViewContracts && <ResponsiveNavLink
                            href={route('contracts.page')}
                            active={route().current('contracts.page')}
                        >
                            Contracts
                        </ResponsiveNavLink>}
                        {canManageIntegrations && <ResponsiveNavLink
                            href={route('integrations.page')}
                            active={route().current('integrations.page')}
                        >
                            Integrations
                        </ResponsiveNavLink>}
                        {canViewAdmin && (
                            <ResponsiveNavLink
                                href={route('queue.status.page')}
                                active={route().current('queue.status.page')}
                            >
                                Queue Monitor
                            </ResponsiveNavLink>
                        )}
                        {canUseAi && <ResponsiveNavLink
                            href={route('ai.page')}
                            active={route().current('ai.page')}
                        >
                            AI Assistant
                        </ResponsiveNavLink>}
                        {canViewAuditLogs && <ResponsiveNavLink
                            href={route('audit.logs.page')}
                            active={route().current('audit.logs.page')}
                        >
                            Audit Logs
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
                <header className="bg-white shadow">
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
