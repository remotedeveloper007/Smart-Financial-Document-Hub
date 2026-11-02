import { useState, useEffect } from 'react';
import Papa from 'papaparse';
import { csrfFetch } from '@/lib/csrfFetch';

type Props = {
    integrationId: number;
    onImported?: () => void;
    initialCsv?: string | null;
};

const TARGET_FIELDS = [
    { value: '', label: 'Ignore' },
    { value: 'ref', label: 'Reference' },
    { value: 'amount', label: 'Amount' },
    { value: 'date', label: 'Date' },
    { value: 'description', label: 'Description' },
    { value: 'account', label: 'Account' },
];

export default function ImportCsvMapper({ integrationId, onImported, initialCsv = null }: Props) {
    const [open, setOpen] = useState(false);
    const [csvText, setCsvText] = useState('');
    const [headers, setHeaders] = useState<string[]>([]);
    const [rows, setRows] = useState<Record<string, any>[]>([]);
    const [mapping, setMapping] = useState<Record<string, string>>({});
    const [preview, setPreview] = useState<any[]>([]);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleFile = (file: File | null) => {
        setError('');
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const content = String(e.target?.result ?? '');
            setCsvText(content);

            const parsed = Papa.parse(content, { header: true, skipEmptyLines: true });
            const data = parsed.data as Record<string, any>[];
            const h = data.length > 0 ? Object.keys(data[0]) : [];
            setHeaders(h);
            setRows(data);

            const initialMap: Record<string, string> = {};
            h.forEach((col) => (initialMap[col] = ''));
            setMapping(initialMap);
            setPreview([]);
        };
        reader.readAsText(file);
    };

    // load initial CSV content if provided
    useEffect(() => {
        if (initialCsv && csvText === '') {
            const parsed = Papa.parse(initialCsv, { header: true, skipEmptyLines: true });
            const data = parsed.data as Record<string, any>[];
            const h = data.length > 0 ? Object.keys(data[0]) : [];
            setCsvText(initialCsv);
            setHeaders(h);
            setRows(data);
            const initialMap: Record<string, string> = {};
            h.forEach((col) => (initialMap[col] = ''));
            setMapping(initialMap);
            setOpen(true);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialCsv]);

    const buildPreview = () => {
        setError('');
        if (headers.length === 0) {
            setError('No headers found');
            return;
        }
        const mapped = rows.map((r) => {
            const obj: any = {};
            headers.forEach((h) => {
                const field = mapping[h];
                if (field && field !== '') {
                    obj[field] = r[h] ?? null;
                }
            });
            return obj;
        });
        setPreview(mapped.slice(0, 20));
    };

    const submit = async () => {
        setLoading(true);
        setError('');
        try {
            const mapped = rows.map((r) => {
                const obj: any = {};
                headers.forEach((h) => {
                    const field = mapping[h];
                    if (field && field !== '') {
                        obj[field] = r[h] ?? null;
                    }
                });
                return obj;
            });

            // Previous direct import POSTs omitted Laravel's CSRF header and were rejected with HTTP 419.
            const res = await csrfFetch(route('integrations.import', { integration: integrationId }), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ transactions: mapped }),
            });

            if (res.status === 202) {
                setOpen(false);
                setCsvText('');
                setHeaders([]);
                setRows([]);
                setMapping({});
                setPreview([]);
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
            <button onClick={() => setOpen(true)} className="rounded-md border px-3 py-1 text-sm">Import CSV</button>
            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
                    <div className="w-full max-w-4xl rounded bg-white p-6 shadow-lg">
                        <h3 className="text-lg font-semibold">Import CSV and map fields</h3>
                        <p className="text-sm text-gray-600">Upload a CSV file. First row must be headers.</p>
                        <div className="mt-4">
                            <input type="file" accept="text/csv,application/csv,.csv" onChange={(e) => handleFile(e.target.files ? e.target.files[0] : null)} />
                        </div>

                        {headers.length > 0 && (
                            <div className="mt-4">
                                <h4 className="font-medium">Map columns</h4>
                                <div className="grid gap-2 md:grid-cols-2 mt-2">
                                    {headers.map((h) => (
                                        <label key={h} className="block text-sm">
                                            <div className="text-xs text-gray-600">{h}</div>
                                            <select value={mapping[h] ?? ''} onChange={(e) => setMapping({ ...mapping, [h]: e.target.value })} className="mt-1 block w-full rounded border-gray-300">
                                                {TARGET_FIELDS.map((t) => (
                                                    <option key={t.value} value={t.value}>{t.label}</option>
                                                ))}
                                            </select>
                                        </label>
                                    ))}
                                </div>

                                <div className="mt-4">
                                    <button onClick={buildPreview} className="rounded-md border px-3 py-1 text-sm">Preview</button>
                                    <button onClick={submit} disabled={loading} className="ml-2 rounded-md bg-indigo-600 px-3 py-1 text-sm text-white">{loading ? 'Importing...' : 'Start Import'}</button>
                                    <button onClick={() => setOpen(false)} className="ml-2 rounded-md border px-3 py-1 text-sm">Cancel</button>
                                </div>

                                {error && <div className="mt-2 text-sm text-red-600">{error}</div>}

                                {preview.length > 0 && (
                                    <div className="mt-4 overflow-x-auto">
                                        <h5 className="font-medium">Preview (first {preview.length} rows)</h5>
                                        <table className="w-full mt-2 table-auto border-collapse">
                                            <thead>
                                                <tr>
                                                    {Object.keys(preview[0]).map((k) => (
                                                        <th key={k} className="border px-2 py-1 text-left text-xs">{k}</th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {preview.map((row, idx) => (
                                                    <tr key={idx}>
                                                        {Object.keys(row).map((k) => (
                                                            <td key={k} className="border px-2 py-1 text-sm">{row[k]}</td>
                                                        ))}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
