import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type FaultLog = {
    id: number;
    error_code?: string | null;
    severity: string;
    message: string;
    file_name?: string | null;
    line_number?: number | null;
    status?: string;
    created_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedFaultLogs = {
    data: FaultLog[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type Props = {
    logs: PaginatedFaultLogs;
};

function severityClass(severity: string): string {
    switch (severity.toLowerCase()) {
        case 'critical':
            return 'bg-red-100 text-red-800';
        case 'high':
            return 'bg-orange-100 text-orange-800';
        case 'medium':
        case 'warning':
            return 'bg-amber-100 text-amber-800';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»');
}

export default function FaultLogsIndex({ logs }: Props) {
    const items = logs?.data ?? [];

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    Fault Logs
                </h1>
            }
        >
            <Head title="Fault Logs" />

            <main className="py-8 sm:py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-lg border-t-4 border-blue-500 bg-gray-50 p-6 shadow-sm">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-wide text-blue-700">
                                    Platform operations
                                </p>
                                <h2 className="mt-1 text-2xl font-bold text-slate-900">
                                    Fault Logs
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm text-slate-600">
                                    Review application faults and their diagnostic details.
                                    Preserve records during incident investigation.
                                </p>
                            </div>
                            <div className="rounded-lg border border-slate-200 bg-white px-5 py-3 shadow-sm">
                                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Recorded faults
                                </p>
                                <p className="mt-1 text-2xl font-bold text-slate-900">
                                    {logs.total}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        {items.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <h3 className="text-lg font-semibold text-slate-900">
                                    No fault logs found
                                </h3>
                                <p className="mt-2 text-sm text-slate-600">
                                    New application faults will appear here when recorded.
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Fault
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Severity
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Location
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Status
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Recorded
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {items.map((fault) => (
                                                <tr
                                                    key={fault.id}
                                                    className="transition-colors hover:bg-slate-50"
                                                >
                                                    <td className="max-w-md px-5 py-4">
                                                        <p className="font-medium text-slate-900">
                                                            {fault.message}
                                                        </p>
                                                        <p className="mt-1 text-xs text-slate-500">
                                                            {fault.error_code ?? `Fault #${fault.id}`}
                                                        </p>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4">
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${severityClass(fault.severity)}`}
                                                        >
                                                            {fault.severity}
                                                        </span>
                                                    </td>
                                                    <td className="max-w-xs px-5 py-4 text-sm text-slate-600">
                                                        <span className="block truncate">
                                                            {fault.file_name ?? 'Unknown file'}
                                                        </span>
                                                        {fault.line_number !== null &&
                                                            fault.line_number !== undefined && (
                                                                <span className="text-xs text-slate-500">
                                                                    Line {fault.line_number}
                                                                </span>
                                                            )}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm capitalize text-slate-600">
                                                        {fault.status ?? 'Open'}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {fault.created_at}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-right">
                                                        <Link
                                                            href={route('admin.fault_logs.show', {
                                                                id: fault.id,
                                                            })}
                                                            className="inline-flex rounded-md px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                        >
                                                            View details
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-sm text-slate-600">
                                        Showing {logs.from ?? 0}–{logs.to ?? 0} of {logs.total}
                                    </p>
                                    <nav aria-label="Fault log pagination" className="flex flex-wrap gap-1">
                                        {logs.links.map((link, index) => (
                                            <Link
                                                key={`${link.label}-${index}`}
                                                href={link.url ?? '#'}
                                                aria-disabled={!link.url}
                                                className={`rounded-md border px-3 py-2 text-sm ${
                                                    link.active
                                                        ? 'border-indigo-600 bg-indigo-600 font-semibold text-white'
                                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                                } ${link.url ? '' : 'pointer-events-none opacity-50'}`}
                                            >
                                                {paginationLabel(link.label)}
                                            </Link>
                                        ))}
                                    </nav>
                                </div>
                            </>
                        )}
                    </section>
                </div>
            </main>
        </AuthenticatedLayout>
    );
}
