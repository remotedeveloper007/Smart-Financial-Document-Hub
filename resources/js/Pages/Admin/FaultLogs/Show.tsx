import React from 'react';

export default function FaultLogsShow({ log }: any) {
    const item = log ?? {};

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-4">Fault Log #{item.id}</h1>
            <div className="bg-white p-4 rounded shadow">
                <p><strong>Message:</strong> {item.message}</p>
                <p><strong>Severity:</strong> {item.severity}</p>
                <p><strong>File:</strong> {item.file_name} ({item.line_number})</p>
                <pre className="mt-4 bg-gray-100 p-2 overflow-auto">{item.stack_trace}</pre>
            </div>
        </div>
    );
}
