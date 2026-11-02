import React from 'react';
import { Link } from '@inertiajs/react';

export default function FaultLogsIndex({ logs }: any) {
    const items = logs?.data ?? [];

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-4">Fault Logs</h1>
            <table className="min-w-full bg-white">
                <thead>
                    <tr>
                        <th className="px-4 py-2">ID</th>
                        <th className="px-4 py-2">Message</th>
                        <th className="px-4 py-2">Severity</th>
                        <th className="px-4 py-2">Created</th>
                        <th className="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item: any) => (
                        <tr key={item.id} className="border-t">
                            <td className="px-4 py-2">{item.id}</td>
                            <td className="px-4 py-2">{item.message}</td>
                            <td className="px-4 py-2">{item.severity}</td>
                            <td className="px-4 py-2">{item.created_at}</td>
                            <td className="px-4 py-2">
                                <Link href={`/admin/fault-logs/${item.id}`} className="text-blue-600">View</Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
