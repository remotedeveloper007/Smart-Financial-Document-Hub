import React from 'react';
import { Link } from '@inertiajs/react';

export default function FailedJobsIndex({ jobs }: any) {
    const items = jobs?.data ?? [];

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-4">Failed Jobs</h1>
            <table className="min-w-full bg-white">
                <thead>
                    <tr>
                        <th className="px-4 py-2">ID</th>
                        <th className="px-4 py-2">Exception</th>
                        <th className="px-4 py-2">Connection</th>
                        <th className="px-4 py-2">Failed At</th>
                        <th className="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item: any) => (
                        <tr key={item.id} className="border-t">
                            <td className="px-4 py-2">{item.id}</td>
                            <td className="px-4 py-2">{item.exception}</td>
                            <td className="px-4 py-2">{item.connection}</td>
                            <td className="px-4 py-2">{item.failed_at}</td>
                            <td className="px-4 py-2">
                                <Link href={`/admin/failed-jobs/${item.id}`} className="text-blue-600">
                                    View
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
