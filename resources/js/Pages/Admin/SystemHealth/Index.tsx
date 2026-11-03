import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import useSystemHealth from './useSystemHealth';

type Fault = {
    id: number;
    message: string;
    severity: string;
    file_name?: string | null;
    line_number?: number | null;
    created_at: string;
};

type FailedJob = {
    id: number | string;
    queue: string;
    connection: string;
    exception: string;
    failed_at: string;
};

type SystemHealthSummary = {
    errors?: number;
    warnings?: number;
    failed_jobs?: number;
    slow_queries?: number;
    queue_issues?: number;
    storage_issues?: number;
    permission_issues?: number;
    api_failures?: number;
    database_failures?: number;
    critical_faults?: number;
    high_faults?: number;
    medium_faults?: number;
    low_faults?: number;
    recent_faults?: Fault[];
    recent_failed_jobs?: FailedJob[];
};

type Props = {
    summary: SystemHealthSummary;
};

export default function SystemHealthIndex({ summary }: Props) {
    const { lastUpdatedAt, isRefreshing, refreshError, refresh } =
        useSystemHealth();

    const cards = [
        { label: 'Errors', value: summary?.errors ?? 0 },
        { label: 'Warnings', value: summary?.warnings ?? 0 },
        { label: 'Failed Jobs', value: summary?.failed_jobs ?? 0 },
        { label: 'Slow Queries', value: summary?.slow_queries ?? 0 },
        { label: 'Queue Issues', value: summary?.queue_issues ?? 0 },
        { label: 'Storage Issues', value: summary?.storage_issues ?? 0 },
        { label: 'Permission Issues', value: summary?.permission_issues ?? 0 },
        { label: 'API Failures', value: summary?.api_failures ?? 0 },
        { label: 'Database Failures', value: summary?.database_failures ?? 0 },
    ];

    const severityCards = [
        {
            label: 'Critical',
            value: summary?.critical_faults ?? 0,
            tone: 'bg-red-100 text-red-700',
        },
        {
            label: 'High',
            value: summary?.high_faults ?? 0,
            tone: 'bg-orange-100 text-orange-700',
        },
        {
            label: 'Medium',
            value: summary?.medium_faults ?? 0,
            tone: 'bg-yellow-100 text-yellow-700',
        },
        {
            label: 'Low',
            value: summary?.low_faults ?? 0,
            tone: 'bg-green-100 text-green-700',
        },
    ];

    const recentFaults = summary?.recent_faults ?? [];
    const recentFailedJobs = summary?.recent_failed_jobs ?? [];

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    System Health
                </h2>
            }
        >
            <Head title="System Health" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border-t-4 border-t-blue-500 bg-gray-50 shadow-sm">
                        <div className="px-6 pb-6">
                            <div className="flex flex-col gap-4 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold">
                                        System Health
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-600">
                                        Operational summary for faults, jobs,
                                        queues, storage, permissions, and system
                                        reliability.
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <p
                                        aria-live="polite"
                                        className="text-sm text-gray-600"
                                    >
                                        {isRefreshing
                                            ? 'Updating system health…'
                                            : `Last updated ${lastUpdatedAt.toLocaleTimeString()}`}
                                        <span className="sr-only">
                                            {' '}
                                            Automatic updates every 30 seconds.
                                        </span>
                                    </p>
                                    <button
                                        type="button"
                                        onClick={refresh}
                                        disabled={isRefreshing}
                                        aria-label={
                                            isRefreshing
                                                ? 'Refreshing system health'
                                                : 'Refresh system health'
                                        }
                                        title={
                                            isRefreshing
                                                ? 'Refreshing system health'
                                                : 'Refresh system health'
                                        }
                                        className="rounded-md border-none bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-wait disabled:opacity-60 cursor-pointer"
                                    >
                                        <svg
                                            aria-hidden="true"
                                            className={`h-5 w-5 ${
                                                isRefreshing ? 'animate-spin' : ''
                                            }`}
                                            fill="none"
                                            stroke="currentColor"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="1.8"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M20 7v5h-5 M4 17v-5h5" />
                                            <path d="M5.64 9A7 7 0 0 1 17.5 6.5L20 12 M4 12l2.5 5.5A7 7 0 0 0 18.36 15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            {refreshError && (
                                <p
                                    role="alert"
                                    className="mx-6 mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                                >
                                    {refreshError}
                                </p>
                            )}
                            <hr className="mb-4 text-gray-300" />

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {cards.map((card) => (
                                    <div
                                        key={card.label}
                                        className="rounded-lg border border-gray-300 bg-white p-4 shadow-sm"
                                    >
                                        <div className="text-sm text-gray-500">
                                            {card.label}
                                        </div>
                                        <div className="mt-2 text-3xl font-bold text-gray-900">
                                            {card.value}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <hr className="mt-4 text-gray-300" />
                            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                {severityCards.map((card) => (
                                    <div
                                        key={card.label}
                                        className={`rounded-lg border p-4 ${card.tone}`}
                                    >
                                        <div className="text-sm font-medium">
                                            {card.label}
                                        </div>
                                        <div className="mt-2 text-2xl font-bold">
                                            {card.value}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <hr className="mt-4 text-gray-300" />
                            <div className="mt-4 grid grid-cols-1 gap-6 xl:grid-cols-2">
                                <div className="rounded-lg border border-gray-300 bg-white p-4 shadow-sm">
                                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                                        Recent Faults
                                    </h2>
                                    <div className="space-y-3">
                                        {recentFaults.length === 0 ? (
                                            <p className="text-sm text-gray-500">
                                                No faults recorded.
                                            </p>
                                        ) : (
                                            recentFaults.map((fault) => (
                                                <div
                                                    key={fault.id}
                                                    className="rounded border border-gray-200 p-3"
                                                >
                                                    <div className="flex items-center justify-between gap-3">
                                                        <span className="font-medium text-gray-900">
                                                            {fault.message}
                                                        </span>
                                                        <span className="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                                            {fault.severity}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        {fault.file_name ?? 'Unknown file'}:
                                                        {fault.line_number ?? '—'} ·{' '}
                                                        {fault.created_at}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-300 bg-white p-4 shadow-sm">
                                    <h2 className="mb-4 text-lg font-semibold text-gray-900">
                                        Recent Failed Jobs
                                    </h2>
                                    <div className="space-y-3">
                                        {recentFailedJobs.length === 0 ? (
                                            <p className="text-sm text-gray-500">
                                                No failed jobs.
                                            </p>
                                        ) : (
                                            recentFailedJobs.map((job) => (
                                                <div
                                                    key={job.id}
                                                    className="rounded border border-gray-300 p-3"
                                                >
                                                    <div className="flex items-center justify-between gap-3">
                                                        <span className="font-medium text-gray-900">
                                                            {job.queue}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {job.connection}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        {job.exception}
                                                    </div>
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        {job.failed_at}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
