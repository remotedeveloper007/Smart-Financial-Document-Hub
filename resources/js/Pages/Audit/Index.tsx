import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Log = {
    id: number;
    team?: { id: number; name: string };
    action: string;
    model_type?: string;
    model_id?: number;
    message?: string | null;
    severity?: string;
    created_at: string;
};

export default function Index() {
    const [logs, setLogs] = useState<Log[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchLogs();
    }, []);

    const fetchLogs = async () => {
        setLoading(true);

        try {
            const res = await fetch(route('audit.logs.list')); // eslint-disable-line no-undef
            const json = await res.json();
            setLogs(json.logs.data || []);
        } catch (e) {
            // noop
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    System Audit Logs
                </h2>
            }
        >
            <Head title="System Audit Logs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">System Audit Logs</h3>
                            <p className="mt-2 text-sm text-gray-600">Application-wide audit events, restricted to platform administrators.</p>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Team</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Model</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Message</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Severity</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {loading ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={6}>Loading...</td>
                                            </tr>
                                        ) : logs.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={6}>No audit logs yet.</td>
                                            </tr>
                                        ) : (
                                            logs.map((log) => (
                                                <tr key={log.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.created_at}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.team?.name ?? 'System'}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.action}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.model_type ?? '—'}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.message ?? '—'}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{log.severity ?? 'info'}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
