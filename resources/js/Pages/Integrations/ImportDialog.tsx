import { useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

type Props = {
    integrationId: number;
    onImported?: () => void;
};

export default function ImportDialog({ integrationId, onImported }: Props) {
    const [open, setOpen] = useState(false);
    const [fileContent, setFileContent] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleFile = (file: File | null) => {
        setError('');
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            setFileContent(String(e.target?.result ?? ''));
        };
        reader.readAsText(file);
    };

    const submit = async () => {
        setError('');
        setLoading(true);
        try {
            let parsed: any = null;
            try {
                parsed = JSON.parse(fileContent);
            } catch (e) {
                setError('Failed to parse JSON. Only JSON array of transactions is supported.');
                setLoading(false);
                return;
            }

            if (!Array.isArray(parsed)) {
                setError('Expected a JSON array of transactions.');
                setLoading(false);
                return;
            }

            // Previous direct import POSTs omitted Laravel's CSRF header and were rejected with HTTP 419.
            const res = await csrfFetch(route('integrations.import', { integration: integrationId }), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ transactions: parsed }),
            });

            if (res.status === 202) {
                setOpen(false);
                setFileContent('');
                onImported?.();
            } else {
                const json = await res.json();
                setError(json.message || 'Import failed');
            }
        } catch (e) {
            setError('Import error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            <button onClick={() => setOpen(true)} className="rounded-md border px-3 py-1 text-sm">Import</button>
            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
                    <div className="w-full max-w-2xl rounded bg-white p-6 shadow-lg">
                        <h3 className="text-lg font-semibold">Import transactions (JSON)</h3>
                        <p className="text-sm text-gray-600">Upload a JSON file containing an array of transaction objects.</p>
                        <div className="mt-4">
                            <input type="file" accept="application/json" onChange={(e) => handleFile(e.target.files ? e.target.files[0] : null)} />
                        </div>
                        <div className="mt-4">
                            <textarea className="w-full h-40 rounded border p-2" value={fileContent} onChange={(e) => setFileContent(e.target.value)} />
                        </div>
                        {error && <div className="mt-2 text-sm text-red-600">{error}</div>}
                        <div className="mt-4 flex justify-end">
                            <button onClick={() => setOpen(false)} className="mr-2 rounded-md border px-3 py-1 text-sm">Cancel</button>
                            <button onClick={submit} disabled={loading} className="rounded-md bg-indigo-600 px-3 py-1 text-sm text-white">{loading ? 'Importing...' : 'Start Import'}</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
