import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

type AiRequest = {
    id: number;
    model: string;
    input: string;
    response?: { text?: string } | null;
    status: string;
};

type Props = {
    mode?: string;
    available?: boolean;
    selectedProvider?: string;
    selectedModel?: string;
    providers?: {
        id: string;
        label: string;
        models: string[];
        requiresCredential: boolean;
        credentialLabel: string;
    }[];
    hasCredential?: boolean;
    canManageSettings?: boolean;
};

export default function Index({
    mode = 'unavailable',
    available: initialAvailable = false,
    selectedProvider = '',
    selectedModel = '',
    providers = [],
    hasCredential: initialHasCredential = false,
    canManageSettings = false,
}: Props) {
    const [requests, setRequests] = useState<AiRequest[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [available, setAvailable] = useState(initialAvailable);
    const [provider, setProvider] = useState(selectedProvider || providers[0]?.id || '');
    const [configuredProvider, setConfiguredProvider] = useState(selectedProvider);
    const [model, setModel] = useState(selectedModel || providers[0]?.models[0] || '');
    const [apiKey, setApiKey] = useState('');
    const [hasCredential, setHasCredential] = useState(initialHasCredential);
    const [clearApiKey, setClearApiKey] = useState(false);
    const [settingsSaving, setSettingsSaving] = useState(false);
    const [settingsMessage, setSettingsMessage] = useState('');

    useEffect(() => {
        fetchRequests();
        const refreshTimer = window.setInterval(fetchRequests, 4000);

        return () => window.clearInterval(refreshTimer);
    }, []);

    const fetchRequests = async () => {
        setLoading(true);

        try {
            const res = await fetch(route('ai.list')); // eslint-disable-line no-undef
            if (!res.ok) throw new Error(`Request list failed (${res.status})`);
            const json = await res.json();
            setRequests(json.requests.data || []);
            setError('');
        } catch {
            setError('Unable to load AI request history. Refresh the page or contact your administrator.');
        } finally {
            setLoading(false);
        }
    };

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!input.trim()) return;

        setLoading(true);
        setError('');
        try {
            // Previous direct fetch omitted Laravel's CSRF header and caused browser POSTs to fail with HTTP 419.
            const response = await csrfFetch(route('ai.create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ input }),
            });
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || `Request failed (${response.status})`);
            }
            setInput('');
            await fetchRequests();
        } catch (caughtError) {
            setError(caughtError instanceof Error ? caughtError.message : 'Unable to submit the AI request.');
        } finally {
            setLoading(false);
        }
    };

    const saveSettings = async (e: React.FormEvent) => {
        e.preventDefault();
        setSettingsSaving(true);
        setSettingsMessage('');
        setError('');

        try {
            const response = await csrfFetch(route('ai.settings.update'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    provider,
                    model,
                    api_key: apiKey || undefined,
                    clear_api_key: clearApiKey,
                }),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to save AI provider settings.');
            }

            setHasCredential(payload.hasCredential);
            setConfiguredProvider(provider);
            setApiKey('');
            setClearApiKey(false);
            setAvailable(payload.available);
            setSettingsMessage('AI provider settings saved.');
        } catch (caughtError) {
            setError(caughtError instanceof Error ? caughtError.message : 'Unable to save AI provider settings.');
        } finally {
            setSettingsSaving(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">AI Assistant</h2>}>
            <Head title="AI Assistant" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        {mode === 'simulated' && (
                            <div className="border-b border-amber-200 bg-amber-50 px-6 py-3 text-sm text-amber-900" role="status">
                                Demo mode: responses are simulated and do not analyze your financial records or contact an AI provider. Do not use results for financial decisions.
                            </div>
                        )}
                        {!available && (
                            <div className="border-b border-red-200 bg-red-50 px-6 py-3 text-sm text-red-800" role="status">
                                AI requests are unavailable because no supported provider is configured for this environment.
                            </div>
                        )}
                        {canManageSettings && (
                            <section className="border-b border-gray-200 px-6 py-5" aria-labelledby="ai-provider-settings-title">
                                <h3 id="ai-provider-settings-title" className="text-lg font-semibold">AI provider settings</h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Provider choice and credentials belong to this team. Prompts sent to OpenAI are processed by that external provider.
                                    This assistant does not retrieve or analyze your stored financial documents.
                                </p>
                                <form onSubmit={saveSettings} className="mt-4 space-y-4">
                                    <div>
                                        <label htmlFor="ai-provider" className="block text-sm font-medium text-gray-700">Provider</label>
                                        <select
                                            id="ai-provider"
                                            value={provider}
                                            onChange={(event) => {
                                                const nextProvider = providers.find((item) => item.id === event.target.value);
                                                setProvider(event.target.value);
                                                setModel(nextProvider?.models[0] ?? '');
                                                setClearApiKey(false);
                                            }}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        >
                                            {providers.map((item) => <option key={item.id} value={item.id}>{item.label}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <label htmlFor="ai-model" className="block text-sm font-medium text-gray-700">Model</label>
                                        <select
                                            id="ai-model"
                                            value={model}
                                            onChange={(event) => setModel(event.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        >
                                            {(providers.find((item) => item.id === provider)?.models ?? []).map((item) => (
                                                <option key={item} value={item}>{item}</option>
                                            ))}
                                        </select>
                                    </div>
                                    {providers.find((item) => item.id === provider)?.requiresCredential && (
                                        <div>
                                            <label htmlFor="ai-api-key" className="block text-sm font-medium text-gray-700">
                                                {providers.find((item) => item.id === provider)?.credentialLabel}
                                            </label>
                                            <input
                                                id="ai-api-key"
                                                type="password"
                                                autoComplete="new-password"
                                                value={apiKey}
                                                onChange={(event) => setApiKey(event.target.value)}
                                                placeholder={hasCredential && provider === configuredProvider
                                                    ? 'A key is saved; enter a new key to rotate it'
                                                    : 'Enter this team’s provider API key'}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            />
                                            {hasCredential && provider === configuredProvider && (
                                                <label className="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                                    <input
                                                        type="checkbox"
                                                        checked={clearApiKey}
                                                        onChange={(event) => setClearApiKey(event.target.checked)}
                                                    />
                                                    Remove the saved key (the provider will be unavailable)
                                                </label>
                                            )}
                                        </div>
                                    )}
                                    <button
                                        type="submit"
                                        disabled={settingsSaving || !provider || !model}
                                        className="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                                    >
                                        {settingsSaving ? 'Saving...' : 'Save provider settings'}
                                    </button>
                                    {settingsMessage && <p className="text-sm text-green-700" role="status">{settingsMessage}</p>}
                                </form>
                            </section>
                        )}
                        <div className="px-6 py-4">
                            <form onSubmit={submit}>
                                <label className="block text-sm font-medium text-gray-700">Ask the AI</label>
                                <textarea
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    disabled={!available}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    rows={4}
                                />
                                <div className="mt-3">
                                    <button type="submit" disabled={!available || loading || !input.trim()} className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{loading ? 'Sending...' : 'Send'}</button>
                                </div>
                                {error && <p className="mt-3 text-sm text-red-700" role="alert">{error}</p>}
                            </form>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <h3 className="text-lg font-semibold">Recent requests</h3>
                            <div className="mt-4">
                                {loading ? <div>Loading...</div> : requests.length === 0 ? <div>No requests yet.</div> : (
                                    requests.map((r) => (
                                        <div key={r.id} className="mb-4 rounded border p-3">
                                            <div className="text-sm text-gray-700"><strong>Input:</strong> {r.input}</div>
                                            <div className="text-sm text-gray-700"><strong>Status:</strong> {r.status}</div>
                                            <div className="mt-2 text-sm text-gray-900"><strong>Response:</strong> {r.response?.text ?? '—'}</div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
