import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type FailedJob = {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    exception: string;
    failed_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedFailedJobs = {
    data: FailedJob[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type Props = {
    jobs: PaginatedFailedJobs;
};

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»');
}

export default function FailedJobsIndex({ jobs }: Props) {
    const items = jobs?.data ?? [];

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    Failed Jobs
                </h1>
            }
        >
            <Head title="Failed Jobs" />

            <main className="py-8 sm:py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-lg border-t-4 border-orange-500 bg-gray-50 p-6 shadow-sm">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-wide text-orange-700">
                                    Platform operations
                                </p>
                                <h2 className="mt-1 text-2xl font-bold text-slate-900">
                                    Failed Jobs
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm text-slate-600">
                                    Inspect queue jobs that did not complete. Check the
                                    cause and replay safety before retrying a job.
                                </p>
                            </div>
                            <div className="rounded-lg border border-slate-200 bg-white px-5 py-3 shadow-sm">
                                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Failed jobs
                                </p>
                                <p className="mt-1 text-2xl font-bold text-slate-900">
                                    {jobs.total}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        {items.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <h3 className="text-lg font-semibold text-slate-900">
                                    No failed jobs
                                </h3>
                                <p className="mt-2 text-sm text-slate-600">
                                    The failed-jobs queue is clear.
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Job
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Queue
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Connection
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Failed at
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {items.map((job) => (
                                                <tr
                                                    key={job.id}
                                                    className="transition-colors hover:bg-slate-50"
                                                >
                                                    <td className="max-w-lg px-5 py-4">
                                                        <p className="truncate font-medium text-slate-900">
                                                            {job.exception.split('\n')[0]}
                                                        </p>
                                                        <p className="mt-1 truncate text-xs text-slate-500">
                                                            ID {job.id} · {job.uuid}
                                                        </p>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4">
                                                        <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                            {job.queue}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {job.connection}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {job.failed_at}
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-4 text-right">
                                                        <Link
                                                            href={route('admin.failed_jobs.show', {
                                                                id: job.id,
                                                            })}
                                                            className="inline-flex rounded-md px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                        >
                                                            Inspect job
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-sm text-slate-600">
                                        Showing {jobs.from ?? 0}–{jobs.to ?? 0} of {jobs.total}
                                    </p>
                                    <nav aria-label="Failed job pagination" className="flex flex-wrap gap-1">
                                        {jobs.links.map((link, index) => (
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
