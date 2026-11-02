import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';
import ImportDialog from './ImportDialog';
import ImportCsvMapper from './ImportCsvMapper';
import ImportCsvChunkedUploader from './ImportCsvChunkedUploader';
import ExportsList from './ExportsList';

type Credentials = {
    access_token?: string;
    url?: string;
    security_code?: string;
    webhook?: string;
    [key: string]: any;
};

type Integration = {
    id: number;
    provider: string;
    status: string;
    external_id?: string | null;
    credentials?: Credentials | null;
};

export default function Index() {
    const [integrations, setIntegrations] = useState<Integration[]>([]);
    const [loading, setLoading] = useState(false);

    const [provider, setProvider] = useState('quickbooks');
    const [externalId, setExternalId] = useState('');
    const [status, setStatus] = useState('enabled');

    const [token, setToken] = useState('');
    const [url, setUrl] = useState('');
    const [securityCode, setSecurityCode] = useState('');
    const [webhook, setWebhook] = useState('');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [testState, setTestState] = useState<'idle' | 'testing' | 'success' | 'failed'>('idle');
    const [testMessage, setTestMessage] = useState('');
    const [toast, setToast] = useState<string | null>(null);
    const [mapperCsvByIntegration, setMapperCsvByIntegration] = useState<Record<number, string | null>>({});

    useEffect(() => {
        fetchIntegrations();
    }, []);

    const fetchIntegrations = async () => {
        setLoading(true);

        try {
            const response = await fetch(route('integrations.list')); // eslint-disable-line no-undef
            const json = await response.json();
            setIntegrations(json.integrations.data || []);
        } catch (error) {
            // ignore for now
        } finally {
            setLoading(false);
        }
    };

    const saveIntegration = async (event: React.FormEvent) => {
        event.preventDefault();

        const payload = {
            provider,
            external_id: externalId,
            status,
            credentials: {
                ...(token ? { access_token: token } : {}),
                ...(url ? { url } : {}),
                ...(securityCode ? { security_code: securityCode } : {}),
                ...(webhook ? { webhook } : {}),
            },
        };

        // Previous direct integration mutations omitted Laravel's CSRF header and were rejected with HTTP 419.
        const response = await csrfFetch(editingId ? route('integrations.update', { integration: editingId }) : route('integrations.store'), {
            method: editingId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            // reset form
            setProvider('quickbooks');
            setExternalId('');
            setStatus('enabled');
            setToken('');
            setUrl('');
            setSecurityCode('');
            setWebhook('');
            setEditingId(null);
            fetchIntegrations();
            // attempt verification after save
            try {
                setTestState('testing');
                const testRes = await csrfFetch(route('integrations.test_connection'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ credentials: payload.credentials }),
                });

                if (testRes.ok) {
                    const json = await testRes.json();
                    if (json.ok) {
                        setTestState('success');
                        setTestMessage('Connection verified');
                    } else {
                        setTestState('failed');
                        setTestMessage('Connection failed');
                    }
                } else {
                    setTestState('failed');
                    setTestMessage('Verification request failed');
                }
            } catch (e) {
                setTestState('failed');
                setTestMessage('Verification error');
            }
        }
    };

    const deleteIntegration = async (id: number) => {
        const response = await csrfFetch(route('integrations.destroy', { integration: id }), {
            method: 'DELETE',
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            fetchIntegrations();
        }
    };

    const editIntegration = (i: Integration) => {
        setEditingId(i.id);
        setProvider(i.provider);
        setExternalId(i.external_id ?? '');
        setStatus(i.status);
        setToken(i.credentials?.access_token ?? '');
        setUrl(i.credentials?.url ?? '');
        setSecurityCode(i.credentials?.security_code ?? '');
        setWebhook(i.credentials?.webhook ?? '');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Integrations</h2>}>
            <Head title="Integrations" />

            <div className="py-12">
                {toast && (
                    <div className="fixed right-6 top-6 z-50 rounded bg-green-600 px-4 py-2 text-white">{toast}</div>
                )}
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold">Connect a provider</h3>
                        <form onSubmit={saveIntegration} className="mt-4 grid gap-4 md:grid-cols-2">
                            <label className="block text-sm font-medium text-gray-700">
                                Provider
                                <select
                                    value={provider}
                                    onChange={(event) => setProvider(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="quickbooks">QuickBooks</option>
                                    <option value="xero">Xero</option>
                                    <option value="zoho">Zoho</option>
                                    <option value="tally">Tally</option>
                                </select>
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                External ID
                                <input
                                    value={externalId}
                                    onChange={(event) => setExternalId(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="acct-0001"
                                />
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                Status
                                <select
                                    value={status}
                                    onChange={(event) => setStatus(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="enabled">Enabled</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                Access token
                                <input
                                    value={token}
                                    onChange={(event) => setToken(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="masked token"
                                />
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                API URL
                                <input
                                    value={url}
                                    onChange={(event) => setUrl(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="https://api.example.com"
                                />
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                Security code
                                <input
                                    value={securityCode}
                                    onChange={(event) => setSecurityCode(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="optional"
                                />
                            </label>

                            <label className="block text-sm font-medium text-gray-700">
                                Webhook URL
                                <input
                                    value={webhook}
                                    onChange={(event) => setWebhook(event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="https://hooks.example.com/receive"
                                />
                            </label>

                            <div className="md:col-span-2">
                                <button type="submit" className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">
                                    {editingId ? 'Update integration' : 'Save integration'}
                                </button>
                                <button
                                    type="button"
                                    onClick={async () => {
                                        // verify without saving
                                        const creds = {
                                            ...(token ? { access_token: token } : {}),
                                            ...(url ? { url } : {}),
                                            ...(securityCode ? { security_code: securityCode } : {}),
                                            ...(webhook ? { webhook } : {}),
                                        };

                                        setTestState('testing');
                                        try {
                                            const res = await csrfFetch(route('integrations.test_connection'), {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                                                body: JSON.stringify({ credentials: creds }),
                                            });

                                            if (res.ok) {
                                                const json = await res.json();
                                                if (json.ok) {
                                                    setTestState('success');
                                                    setTestMessage('Connection verified');
                                                } else {
                                                    setTestState('failed');
                                                    setTestMessage('Connection failed');
                                                }
                                            } else {
                                                setTestState('failed');
                                                setTestMessage('Verification request failed');
                                            }
                                        } catch (e) {
                                            setTestState('failed');
                                            setTestMessage('Verification error');
                                        }
                                    }}
                                    className="ml-3 inline-flex items-center rounded-md border px-3 py-2 text-sm"
                                >
                                    Verify connection
                                </button>
                                {editingId && (
                                    <button type="button" onClick={() => {
                                        setEditingId(null);
                                        setProvider('quickbooks');
                                        setExternalId('');
                                        setStatus('enabled');
                                        setToken('');
                                        setUrl('');
                                        setSecurityCode('');
                                        setWebhook('');
                                    }} className="ml-3 inline-flex items-center rounded-md border px-3 py-2 text-sm">Cancel</button>
                                )}
                                <div className="inline-block ml-4 align-middle">
                                    {testState === 'idle' ? null : testState === 'testing' ? (
                                        <span className="text-sm text-gray-500">Verifying...</span>
                                    ) : testState === 'success' ? (
                                        <span className="text-sm text-green-600">{testMessage}</span>
                                    ) : (
                                        <span className="text-sm text-red-600">{testMessage}</span>
                                    )}
                                </div>
                            </div>
                        </form>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">Connected integrations</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                Third-party accounting integrations connected to your team.
                            </p>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Provider</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">External ID</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {loading ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={4}>Loading...</td>
                                            </tr>
                                        ) : integrations.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={4}>No integrations configured.</td>
                                            </tr>
                                        ) : (
                                            integrations.map((i) => (
                                                <tr key={i.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{i.provider}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{i.status}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{i.external_id ?? '—'}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        <div className="flex gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() => editIntegration(i)}
                                                                className="rounded-md border border-gray-200 px-3 py-1 text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => deleteIntegration(i.id)}
                                                                className="rounded-md border border-red-200 px-3 py-1 text-red-700 hover:bg-red-50"
                                                            >
                                                                Delete
                                                            </button>
                                                            <ImportDialog integrationId={i.id} onImported={() => { fetchIntegrations(); }} />
                                                            <ImportCsvMapper integrationId={i.id} onImported={() => { fetchIntegrations(); }} initialCsv={mapperCsvByIntegration[i.id] ?? null} />
                                                            <ImportCsvChunkedUploader integrationId={i.id} onComplete={() => { fetchIntegrations(); }} onAssembled={(csv: string) => { setMapperCsvByIntegration({ ...mapperCsvByIntegration, [i.id]: csv }); }} />
                                                            <button
                                                                type="button"
                                                                onClick={async () => {
                                                                    // Trigger export job and poll for new exports
                                                                    try {
                                                                        // fetch current file names
                                                                        const beforeRes = await fetch(route('integrations.exports.list', { integration: i.id }));
                                                                        const beforeJson = beforeRes.ok ? await beforeRes.json() : { files: [] };
                                                                        const beforeNames = (beforeJson.files || []).map((f: any) => f.name);

                                                                        await csrfFetch(route('integrations.export', { integration: i.id }), {
                                                                            method: 'POST',
                                                                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                                                                            body: JSON.stringify({ filters: null }),
                                                                        });

                                                                        // poll for up to 30s
                                                                        const start = Date.now();
                                                                        const timeout = 30000;
                                                                        const poll = async () => {
                                                                            const res = await fetch(route('integrations.exports.list', { integration: i.id }));
                                                                            if (res.ok) {
                                                                                const json = await res.json();
                                                                                const names = (json.files || []).map((f: any) => f.name);
                                                                                const newFiles = names.filter((n: string) => !beforeNames.includes(n));
                                                                                if (newFiles.length > 0) {
                                                                                    setToast(`Export ready: ${newFiles[0]}`);
                                                                                    setTimeout(() => setToast(null), 8000);
                                                                                    fetchIntegrations();
                                                                                    return;
                                                                                }
                                                                            }
                                                                            if (Date.now() - start < timeout) {
                                                                                setTimeout(poll, 2000);
                                                                            } else {
                                                                                setToast('Export is taking longer than expected. Check queue.');
                                                                                setTimeout(() => setToast(null), 8000);
                                                                            }
                                                                        };

                                                                        poll();
                                                                    } catch (e) {
                                                                        setToast('Export failed to start');
                                                                        setTimeout(() => setToast(null), 5000);
                                                                    }
                                                                }}
                                                                className="rounded-md border border-gray-200 px-3 py-1 text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Export
                                                            </button>
                                                            <div className="ml-2"><ExportsList integrationId={i.id} /></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
