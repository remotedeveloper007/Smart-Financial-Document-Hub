import React from 'react';

export default function SystemHealthIndex({ summary }: any) {
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
        { label: 'Critical', value: summary?.critical_faults ?? 0, tone: 'bg-red-100 text-red-700' },
        { label: 'High', value: summary?.high_faults ?? 0, tone: 'bg-orange-100 text-orange-700' },
        { label: 'Medium', value: summary?.medium_faults ?? 0, tone: 'bg-yellow-100 text-yellow-700' },
        { label: 'Low', value: summary?.low_faults ?? 0, tone: 'bg-green-100 text-green-700' },
    ];

    const recentFaults = summary?.recent_faults ?? [];
    const recentFailedJobs = summary?.recent_failed_jobs ?? [];

    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900">System Health</h1>
                <p className="mt-2 text-sm text-gray-600">Operational summary for faults, jobs, queues, storage, permissions, and system reliability.</p>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                {cards.map((card) => (
                    <div key={card.label} className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div className="text-sm text-gray-500">{card.label}</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900">{card.value}</div>
                    </div>
                ))}
            </div>

            <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                {severityCards.map((card) => (
                    <div key={card.label} className={`rounded-lg border p-4 ${card.tone}`}>
                        <div className="text-sm font-medium">{card.label}</div>
                        <div className="mt-2 text-2xl font-bold">{card.value}</div>
                    </div>
                ))}
            </div>

            <div className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Recent Faults</h2>
                    <div className="space-y-3">
                        {recentFaults.length === 0 ? (
                            <p className="text-sm text-gray-500">No faults recorded.</p>
                        ) : (
                            recentFaults.map((fault: any) => (
                                <div key={fault.id} className="rounded border border-gray-200 p-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium text-gray-900">{fault.message}</span>
                                        <span className="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{fault.severity}</span>
                                    </div>
                                    <div className="mt-1 text-xs text-gray-500">{fault.file_name}:{fault.line_number} · {fault.created_at}</div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Recent Failed Jobs</h2>
                    <div className="space-y-3">
                        {recentFailedJobs.length === 0 ? (
                            <p className="text-sm text-gray-500">No failed jobs.</p>
                        ) : (
                            recentFailedJobs.map((job: any) => (
                                <div key={job.id} className="rounded border border-gray-200 p-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium text-gray-900">{job.queue}</span>
                                        <span className="text-xs text-gray-500">{job.connection}</span>
                                    </div>
                                    <div className="mt-1 text-xs text-gray-500">{job.exception}</div>
                                    <div className="mt-1 text-xs text-gray-500">{job.failed_at}</div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
