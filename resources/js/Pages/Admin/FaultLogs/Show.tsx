import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type FaultLog = {
    id: number;
    team_id?: number | null;
    user_id?: number | null;
    error_code?: string | null;
    severity: string;
    message: string;
    stack_trace?: string | null;
    file_name?: string | null;
    file_path?: string | null;
    line_number?: number | null;
    class_name?: string | null;
    method_name?: string | null;
    root_cause?: string | null;
    recommended_fix?: string | null;
    status?: string;
    created_at: string;
};

type Props = {
    log: FaultLog;
};

function Detail({ label, value }: { label: string; value: string | number | null | undefined }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </dt>
            <dd className="mt-1 break-words text-sm text-slate-900">
                {value ?? '—'}
            </dd>
        </div>
    );
}

export default function FaultLogsShow({ log }: Props) {
    const removeLog = () => {
        if (window.confirm(`Delete fault log #${log.id}? This cannot be undone.`)) {
            router.delete(route('admin.fault_logs.destroy', { id: log.id }));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    Fault Log Details
                </h1>
            }
        >
            <Head title={`Fault Log #${log.id}`} />

            <main className="py-8 sm:py-12">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <Link
                                href={route('admin.fault_logs.index')}
                                className="text-sm font-semibold text-indigo-700 hover:text-indigo-900"
                            >
                                ← Back to Fault Logs
                            </Link>
                            <h2 className="mt-3 text-2xl font-bold text-slate-900">
                                Fault #{log.id}
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                {log.created_at}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={removeLog}
                            className="inline-flex justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                            Delete fault log
                        </button>
                    </div>

                    <section className="rounded-lg border-t-4 border-red-500 bg-gray-50 p-6 shadow-sm">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold uppercase text-red-800">
                                {log.severity}
                            </span>
                            {log.error_code && (
                                <span className="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {log.error_code}
                                </span>
                            )}
                            <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold capitalize text-amber-800">
                                {log.status ?? 'Open'}
                            </span>
                        </div>
                        <h3 className="mt-4 break-words text-lg font-semibold text-slate-900">
                            {log.message}
                        </h3>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Diagnostic details
                        </h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Detail label="File name" value={log.file_name} />
                            <Detail label="Line number" value={log.line_number} />
                            <Detail label="Full path" value={log.file_path} />
                            <Detail label="Class" value={log.class_name} />
                            <Detail label="Method" value={log.method_name} />
                            <Detail label="Team ID" value={log.team_id} />
                            <Detail label="User ID" value={log.user_id} />
                            <Detail label="Recorded at" value={log.created_at} />
                        </dl>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Root cause
                        </h3>
                        <p className="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">
                            {log.root_cause || 'No root cause has been recorded.'}
                        </p>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Recommended fix
                        </h3>
                        <p className="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">
                            {log.recommended_fix || 'No recommended fix has been recorded.'}
                        </p>
                    </section>

                    <section className="rounded-lg border border-slate-800 bg-slate-950 p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-white">
                            Stack trace
                        </h3>
                        <pre className="mt-4 max-h-[32rem] overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-slate-200">
                            {log.stack_trace || 'No stack trace was recorded.'}
                        </pre>
                    </section>
                </div>
            </main>
        </AuthenticatedLayout>
    );
}
