import React, { PropsWithChildren } from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import Authenticated from './AuthenticatedLayout';

const { pageState, inertiaRouter } = vi.hoisted(() => ({
    pageState: {
        isPlatformAdmin: false,
        teams: [] as { id: number; name: string }[],
        activeTeam: null as { id: number; name: string } | null,
    },
    inertiaRouter: { post: vi.fn() },
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        as,
        method: _method,
        ...props
    }: PropsWithChildren<Record<string, unknown> & { href: string; as?: string }>) =>
        React.createElement(as === 'button' ? 'button' : 'a', { href, ...props }, children),
    router: inertiaRouter,
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                },
            },
            can: {
                isPlatformAdmin: pageState.isPlatformAdmin,
                viewAdmin: pageState.isPlatformAdmin,
                permissions: pageState.isPlatformAdmin
                    ? ['manage_users', 'create_documents', 'view_transactions', 'view_invoices', 'view_receipts', 'view_contracts', 'manage_integrations', 'manage_billing', 'use_ai']
                    : ['view_transactions'],
            },
            tenant: {
                teams: pageState.teams,
                activeTeam: pageState.activeTeam,
            },
        },
    }),
}));

vi.mock('@/Components/ApplicationLogo', () => ({
    default: () => <svg aria-hidden="true" />,
}));

vi.mock('@/Components/Dropdown', () => {
    const Wrapper = ({ children }: PropsWithChildren) => <div>{children}</div>;
    return {
        default: Object.assign(Wrapper, {
            Trigger: Wrapper,
            Content: Wrapper,
            Link: Wrapper,
        }),
    };
});

vi.mock('@/Components/NavLink', () => ({
    default: ({ children }: PropsWithChildren) => <a>{children}</a>,
}));

vi.mock('@/Components/ResponsiveNavLink', () => ({
    default: ({ children }: PropsWithChildren) => <a>{children}</a>,
}));

let activeRoute = 'dashboard';

beforeEach(() => {
    activeRoute = 'dashboard';
    pageState.isPlatformAdmin = false;
    pageState.teams = [];
    pageState.activeTeam = null;
    inertiaRouter.post.mockReset();
    localStorage.clear();
    document.documentElement.removeAttribute('data-theme');
    vi.stubGlobal('route', (name?: string) => {
        if (name) {
            return `/${name}`;
        }

        return {
            current: (pattern: string) =>
                activeRoute === pattern ||
                (pattern.endsWith('.*') &&
                    activeRoute.startsWith(pattern.slice(0, -1))),
        };
    });
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

test('collapses the sidebar to labeled icons and keeps the active page clear', async () => {
    render(
        <Authenticated>
            <p>Page content</p>
        </Authenticated>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Collapse sidebar' }));

    expect(
        screen.getByRole('button', { name: 'Expand sidebar' }),
    ).toBeTruthy();
    expect(
        screen.getByRole('link', { name: 'Dashboard' }).getAttribute('title'),
    ).toBe('Dashboard');
    expect(
        screen.getByRole('link', { name: 'Dashboard' }).getAttribute('aria-current'),
    ).toBe('page');
    expect(screen.queryByRole('link', { name: 'Team Members' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'System Audit Logs' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'Queue Monitor' })).toBeNull();
    await waitFor(() =>
        expect(localStorage.getItem('financial-hub-sidebar-collapsed')).toBe(
            'true',
        ),
    );
});

test('toggles dark mode and remembers the selection', async () => {
    render(
        <Authenticated>
            <p>Page content</p>
        </Authenticated>,
    );

    fireEvent.click(
        screen.getAllByRole('button', { name: 'Switch to dark mode' })[0],
    );

    await waitFor(() => {
        expect(document.documentElement.dataset.theme).toBe('dark');
        expect(localStorage.getItem('financial-hub-theme')).toBe('dark');
    });
    expect(
        screen.getAllByRole('button', { name: 'Switch to light mode' }),
    ).toHaveLength(2);
});

test('restores saved sidebar and theme preferences', () => {
    localStorage.setItem('financial-hub-sidebar-collapsed', 'true');
    localStorage.setItem('financial-hub-theme', 'dark');

    render(
        <Authenticated>
            <p>Page content</p>
        </Authenticated>,
    );

    expect(
        screen.getByRole('button', { name: 'Expand sidebar' }),
    ).toBeTruthy();
    expect(
        screen.getAllByRole('button', { name: 'Switch to light mode' }),
    ).toHaveLength(2);
});

test('switches to a team selected from the workspace header', () => {
    pageState.teams = [
        { id: 1, name: 'First Team' },
        { id: 2, name: 'Second Team' },
    ];
    pageState.activeTeam = pageState.teams[0];

    render(
        <Authenticated>
            <p>Page content</p>
        </Authenticated>,
    );

    fireEvent.change(screen.getAllByRole('combobox', { name: 'Active team' })[0], {
        target: { value: '2' },
    });

    expect(inertiaRouter.post).toHaveBeenCalledWith(
        '/team.active.update',
        { team_id: 2 },
        { preserveScroll: true },
    );
});

test('platform administrators only see platform operations navigation', () => {
    pageState.isPlatformAdmin = true;

    render(
        <Authenticated>
            <p>Page content</p>
        </Authenticated>,
    );

    for (const label of [
        'System Health',
        'Queue Monitor',
        'Fault Logs',
        'Failed Jobs',
        'System Audit Logs',
    ]) {
        expect(screen.getAllByRole('link', { name: label }).length).toBeGreaterThan(0);
    }

    for (const label of [
        'Dashboard',
        'Team Members',
        'Documents',
        'Transactions',
        'Search',
        'Invoices',
        'Receipts',
        'Contracts',
        'Integrations',
        'Billing',
        'AI Assistant',
    ]) {
        expect(screen.queryByRole('link', { name: label })).toBeNull();
    }
});
