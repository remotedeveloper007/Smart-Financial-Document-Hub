import React from 'react';
const { Inertia } = require('@inertiajs/react');

export default function FailedJobsShow({ job }: any) {
    const item = job ?? {};

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-4">Failed Job #{item.id}</h1>
            <div className="bg-white p-4 rounded shadow">
                <p><strong>Exception:</strong> {item.exception}</p>
                <p><strong>Connection:</strong> {item.connection}</p>
                <pre className="mt-4 bg-gray-100 p-2 overflow-auto">{item.payload}</pre>
                <div className="mt-4 space-x-2">
                    <button
                        onClick={() => {
                            // trigger retry
                            // eslint-disable-next-line @typescript-eslint/no-var-requires                   
                            Inertia.post(route('admin.failed_jobs.retry', { id: item.id }));
                        }}
                        className="px-3 py-2 bg-blue-600 text-white rounded"
                    >
                        Retry
                    </button>
                    <button
                        onClick={() => {
                            // destroy
                            // eslint-disable-next-line @typescript-eslint/no-var-requires
                            Inertia.delete(route('admin.failed_jobs.destroy', { id: item.id }));
                        }}
                        className="px-3 py-2 bg-red-600 text-white rounded"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    );
}
