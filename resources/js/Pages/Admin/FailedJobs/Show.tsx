import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type FailedJob = {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    payload: string;
    exception: string;
    failed_at: string;
};

type Props = {
    job: FailedJob;
};

export default function FailedJobsShow({ job }: Props) {
    const retry = () => {
        if (window.confirm(`Retry failed job #${job.id}? Confirm it is safe to replay.`)) {
            router.post(route('admin.failed_jobs.retry', { id: job.id }));
        }
    };

    const remove = () => {
        if (window.confirm(`Delete failed job #${job.id}? This removes its failure record.`)) {
            router.delete(route('admin.failed_jobs.destroy', { id: job.id }));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    Failed Job Details
                </h1>
            }
        >
            <Head title={`Failed Job #${job.id}`} />

            <main className="py-8 sm:py-12">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <Link
                                href={route('admin.failed_jobs.index')}
                                className="text-sm font-semibold text-indigo-700 hover:text-indigo-900"
                            >
                                ← Back to Failed Jobs
                            </Link>
                            <h2 className="mt-3 text-2xl font-bold text-slate-900">
                                Failed Job #{job.id}
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Failed at {job.failed_at}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <button
                                type="button"
                                onClick={retry}
                                className="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Retry job
                            </button>
                            <button
                                type="button"
                                onClick={remove}
                                className="inline-flex justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Delete record
                            </button>
                        </div>
                    </div>

                    <section className="rounded-lg border-t-4 border-orange-500 bg-gray-50 p-6 shadow-sm">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Queue
                                </p>
                                <p className="mt-1 text-sm font-semibold text-slate-900">
                                    {job.queue}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Connection
                                </p>
                                <p className="mt-1 text-sm font-semibold text-slate-900">
                                    {job.connection}
                                </p>
                            </div>
                            <div className="sm:col-span-2">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    UUID
                                </p>
                                <p className="mt-1 break-all font-mono text-sm text-slate-900">
                                    {job.uuid}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-slate-950 p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-white">
                            Exception
                        </h3>
                        <pre className="mt-4 max-h-128 overflow-auto whitespace-pre-wrap wrap-break-word text-xs leading-5 text-slate-200">
                            {job.exception}
                        </pre>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Job payload
                        </h3>
                        <pre className="mt-4 max-h-128 overflow-auto whitespace-pre-wrap wrap-break-word rounded-md bg-slate-50 p-4 text-xs leading-5 text-slate-700">
                            {job.payload}
                        </pre>
                        <p className="mt-3 text-xs text-amber-800">
                            Payloads may contain sensitive operational or customer data.
                            Share only through approved secure channels.
                        </p>
                    </section>
                </div>
            </main>
        </AuthenticatedLayout>
    );
}
