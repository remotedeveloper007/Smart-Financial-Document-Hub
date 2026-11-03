import React, { PropsWithChildren } from 'react';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vitest';
import SystemHealthIndex from './Index';

const { usePoll, router } = vi.hoisted(() => ({
    usePoll: vi.fn(),
    router: {
        on: vi.fn(),
        reload: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    usePoll,
    router,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: PropsWithChildren) => <div>{children}</div>,
}));

afterEach(() => {
    cleanup();
    vi.clearAllMocks();
});

test('polls only the system health summary without overlapping refresh requests', () => {
    render(
        <SystemHealthIndex
            summary={{
                errors: 2,
                critical_faults: 1,
                recent_faults: [],
                recent_failed_jobs: [],
            }}
        />,
    );

    expect(usePoll).toHaveBeenCalledWith(
        30_000,
        expect.objectContaining({
            only: ['summary'],
            preserveScroll: true,
        }),
        { mode: 'rest' },
    );
    expect(screen.getByText('2')).toBeTruthy();
    expect(screen.getByText('No faults recorded.')).toBeTruthy();
});

test('allows an admin to manually refresh the dashboard summary', () => {
    render(
        <SystemHealthIndex
            summary={{ recent_faults: [], recent_failed_jobs: [] }}
        />,
    );

    fireEvent.click(
        screen.getByRole('button', { name: 'Refresh system health' }),
    );

    expect(router.reload).toHaveBeenCalledWith(
        expect.objectContaining({
            only: ['summary'],
            preserveScroll: true,
        }),
    );
});

test('shows a visible error when the refresh request cannot reach the server', () => {
    let exceptionHandler: ((event: { detail: { exception: Error } }) => void) | undefined;
    router.on.mockImplementation((eventName: string, handler: typeof exceptionHandler) => {
        if (eventName === 'exception') {
            exceptionHandler = handler;
        }

        return vi.fn();
    });

    render(
        <SystemHealthIndex
            summary={{ recent_faults: [], recent_failed_jobs: [] }}
        />,
    );

    act(() => {
        exceptionHandler?.({
            detail: { exception: new Error('Connection unavailable') },
        });
    });

    expect(screen.getByRole('alert').textContent).toContain(
        'Check your connection',
    );
});
