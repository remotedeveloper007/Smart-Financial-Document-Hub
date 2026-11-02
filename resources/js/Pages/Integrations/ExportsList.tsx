import { useEffect, useState } from 'react';

type ExportFile = { path: string; name: string; url: string };

type Props = {
    integrationId: number;
};

export default function ExportsList({ integrationId }: Props) {
    const [files, setFiles] = useState<ExportFile[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchFiles = async () => {
        setLoading(true);
        try {
            const res = await fetch(route('integrations.exports.list', { integration: integrationId }));
            if (res.ok) {
                const json = await res.json();
                setFiles(json.files || []);
            }
        } catch (e) {
            // ignore
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchFiles();
    }, []);

    return (
        <div>
            <h4 className="text-sm font-semibold">Exports</h4>
            {loading ? (
                <div className="text-sm text-gray-500">Loading...</div>
            ) : files.length === 0 ? (
                <div className="text-sm text-gray-500">No exports yet.</div>
            ) : (
                <ul className="mt-2 space-y-2">
                    {files.map((f) => (
                        <li key={f.path} className="flex items-center justify-between rounded border p-2">
                            <div className="text-sm text-gray-700">{f.name}</div>
                            <a href={f.url} className="text-sm text-indigo-600">Download</a>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
