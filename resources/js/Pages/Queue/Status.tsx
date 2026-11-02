import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type FailedJob = {
    id: number;
    connection: string;
    queue: string;
    exception: string;
    failed_at: string;
};

export default function Status() {
    const [pendingJobs, setPendingJobs] = useState(0);
    const [failedJobs, setFailedJobs] = useState(0);
    const [recentFailedJobs, setRecentFailedJobs] = useState<FailedJob[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        loadQueueStatus();
    }, []);

    const loadQueueStatus = async () => {
        try {
            const response = await fetch(route('queue.status.index')); // eslint-disable-line no-undef
            const data = await response.json();

            setPendingJobs(data.pending_jobs);
            setFailedJobs(data.failed_jobs);
            setRecentFailedJobs(data.recent_failed_jobs);
        } catch (caughtError) {
            setError('Unable to load queue metrics.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Queue Status
                </h2>
            }
        >
            <Head title="Queue Status" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid gap-6 lg:grid-cols-3">
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold">Pending jobs</h3>
                            <p className="mt-4 text-3xl font-bold text-slate-900">
                                {loading ? 'Loading...' : pendingJobs}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold">Failed jobs</h3>
                            <p className="mt-4 text-3xl font-bold text-slate-900">
                                {loading ? 'Loading...' : failedJobs}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold">Status</h3>
                            <p className="mt-4 text-sm text-gray-600">
                                {loading
                                    ? 'Fetching queue metrics...'
                                    : error || 'Queue metrics loaded successfully.'}
                            </p>
                        </div>
                    </div>

                    <div className="mt-8 rounded-lg bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">Recent failed jobs</h3>
                            <button
                                type="button"
                                onClick={loadQueueStatus}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Refresh
                            </button>
                        </div>

                        {error && (
                            <div className="mt-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <div className="mt-6 overflow-hidden rounded-md border border-gray-200">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            ID
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Queue
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Connection
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Failed at
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {recentFailedJobs.length === 0 ? (
                                        <tr>
                                            <td className="px-6 py-4 text-sm text-gray-500" colSpan={4}>
                                                No recent failed jobs.
                                            </td>
                                        </tr>
                                    ) : (
                                        recentFailedJobs.map((job) => (
                                            <tr key={job.id}>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {job.id}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {job.queue}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {job.connection}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {job.failed_at}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
