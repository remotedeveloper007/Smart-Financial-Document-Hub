import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

type Plan = {
    id: number;
    slug: string;
    name: string;
    description?: string | null;
    price: string | number;
    yearly_price: string | number | null;
    currency: string;
    features?: string[] | null;
};

type BillingRecord = {
    id: number;
    status: string;
    billing_interval?: BillingInterval;
    provider?: string | null;
    checkout_url?: string | null;
    failure_reason?: string | null;
    created_at: string;
    plan?: { name: string } | null;
};

type BillingPageProps = PageProps<{
    plans: Plan[];
    providers: string[];
    billingIntervals: { value: BillingInterval; label: string }[];
    subscriptions: BillingRecord[];
    paymentAttempts: BillingRecord[];
    canRefund: boolean;
}>;

type BillingInterval = 'monthly' | 'yearly';

const providerLabels: Record<string, string> = {
    stripe: 'Stripe',
    razorpay: 'Razorpay',
    paypal: 'PayPal',
};

export default function Plans() {
    const { plans, providers, billingIntervals, subscriptions, paymentAttempts, canRefund } = usePage<BillingPageProps>().props;
    const [selectedProvider, setSelectedProvider] = useState(providers[0] ?? 'stripe');
    const [selectedInterval, setSelectedInterval] = useState<BillingInterval>('monthly');
    const [submittingPlanId, setSubmittingPlanId] = useState<number | null>(null);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const requestInProgress = useRef(false);

    const requestRefund = async (record: BillingRecord) => {
        if (!window.confirm(`Request a refund for the ${record.plan?.name ?? 'subscription'} payment?`)) return;

        setError('');
        try {
            const response = await csrfFetch(route('billing.payments.refund', record.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason: 'Requested from the billing page' }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message ?? 'Refund request failed.');
            window.location.reload();
        } catch (reason) {
            setError(reason instanceof Error ? reason.message : 'Refund request failed.');
        }
    };

    const startCheckout = async (planId: number) => {
        if (requestInProgress.current || !selectedProvider) return;

        requestInProgress.current = true;
        setSubmittingPlanId(planId);
        setMessage('');
        setError('');

        try {
            const response = await csrfFetch(route('billing.subscriptions.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': crypto.randomUUID(),
                },
                body: JSON.stringify({ plan_id: planId, provider: selectedProvider, billing_interval: selectedInterval }),
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message ?? result.failure_reason ?? 'Checkout could not be started.');
            }

            if (result.checkout_url) {
                window.location.assign(result.checkout_url);
                return;
            }

            setMessage(`Payment attempt ${result.status}. Refresh this page to see the latest status.`);
        } catch (reason) {
            setError(reason instanceof Error ? reason.message : 'Checkout could not be started.');
        } finally {
            requestInProgress.current = false;
            setSubmittingPlanId(null);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Billing</h2>}>
            <Head title="Billing" />

            <div className="py-10">
                <div className="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h1 className="text-2xl font-semibold text-slate-900">Plans and payments</h1>
                        <p className="mt-2 text-sm text-slate-600">
                            Choose a plan and renewal period. Paid subscriptions activate only after the provider confirms payment.
                        </p>

                        <fieldset className="mt-6">
                            <legend className="text-sm font-medium text-slate-700">Billing period</legend>
                            <div className="mt-3 flex flex-wrap gap-3">
                                {billingIntervals.map((interval) => (
                                    <label key={interval.value} className="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-3">
                                        <input
                                            type="radio"
                                            name="billing-interval"
                                            value={interval.value}
                                            checked={selectedInterval === interval.value}
                                            onChange={() => setSelectedInterval(interval.value)}
                                        />
                                        <span>{interval.label}</span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>

                        {providers.length > 0 ? (
                            <fieldset className="mt-6">
                                <legend className="text-sm font-medium text-slate-700">Choose a payment provider</legend>
                                <div className="mt-3 flex flex-wrap gap-3">
                                    {providers.map((provider) => (
                                        <label key={provider} className="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-3">
                                            <input
                                                type="radio"
                                                name="payment-provider"
                                                value={provider}
                                                checked={selectedProvider === provider}
                                                onChange={() => setSelectedProvider(provider)}
                                            />
                                            <span>{providerLabels[provider] ?? provider}</span>
                                        </label>
                                    ))}
                                </div>
                            </fieldset>
                        ) : (
                            <p className="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
                                No payment provider is ready. Configure sandbox API credentials and webhook secrets in the server environment.
                            </p>
                        )}

                        {message && <p role="status" className="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800">{message}</p>}
                        {error && <p role="alert" className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{error}</p>}

                        <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            {plans.map((plan) => {
                                const price = selectedInterval === 'yearly'
                                    ? plan.yearly_price ?? (Number(plan.price) === 0 ? plan.price : null)
                                    : plan.price;
                                const available = price !== null;

                                return (
                                <article key={plan.id} className="rounded-xl border border-slate-200 p-5">
                                    <h2 className="text-lg font-semibold text-slate-900">{plan.name}</h2>
                                    {plan.description && <p className="mt-2 text-sm text-slate-600">{plan.description}</p>}
                                    <p className="mt-4 text-2xl font-bold text-slate-900">
                                        {available
                                            ? new Intl.NumberFormat(undefined, { style: 'currency', currency: plan.currency }).format(Number(price))
                                            : 'Not available'}
                                        {available && Number(price) > 0 && (
                                            <span className="text-sm font-normal text-slate-500"> / {selectedInterval === 'yearly' ? 'year' : 'month'}</span>
                                        )}
                                    </p>
                                    <button
                                        type="button"
                                        disabled={!available || (Number(price) > 0 && !providers.length) || submittingPlanId !== null}
                                        onClick={() => startCheckout(plan.id)}
                                        className="mt-5 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {!available
                                            ? 'Period unavailable'
                                            : submittingPlanId === plan.id
                                                ? 'Starting checkout...'
                                                : Number(price) === 0 ? 'Choose free plan' : 'Continue to checkout'}
                                    </button>
                                </article>
                                );
                            })}
                            {plans.length === 0 && <p className="text-sm text-slate-600">No plans are available yet.</p>}
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-900">Subscriptions</h2>
                            <RecordList records={subscriptions} emptyText="There are no subscriptions for this team." />
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-900">Recent payment attempts</h2>
                            <RecordList
                                records={paymentAttempts}
                                emptyText="No payment attempts have been made."
                                showProvider
                                onRefund={canRefund ? requestRefund : undefined}
                            />
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function RecordList({
    records,
    emptyText,
    showProvider = false,
    onRefund,
}: {
    records: BillingRecord[];
    emptyText: string;
    showProvider?: boolean;
    onRefund?: (record: BillingRecord) => void;
}) {
    if (records.length === 0) {
        return <p className="mt-4 text-sm text-slate-500">{emptyText}</p>;
    }

    return (
        <ul className="mt-4 divide-y divide-slate-100">
            {records.map((record) => (
                <li key={record.id} className="py-3">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="font-medium text-slate-800">{record.plan?.name ?? 'Plan'}</p>
                            {record.billing_interval && (
                                <p className="text-xs capitalize text-slate-500">{record.billing_interval}</p>
                            )}
                            {showProvider && <p className="text-xs text-slate-500">{providerLabels[record.provider ?? ''] ?? record.provider}</p>}
                        </div>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-700">
                            {record.status}
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-slate-500">{new Date(record.created_at).toLocaleString()}</p>
                    {record.failure_reason && <p className="mt-1 text-sm text-red-700">{record.failure_reason}</p>}
                    {record.checkout_url && record.status === 'pending' && (
                        <a className="mt-2 inline-block text-sm font-medium text-indigo-700 underline" href={record.checkout_url}>
                            Resume checkout
                        </a>
                    )}
                    {onRefund && ['succeeded', 'refunding'].includes(record.status) && (
                        <button
                            type="button"
                            onClick={() => onRefund(record)}
                            className="mt-2 text-sm font-medium text-red-700 underline"
                        >
                            {record.status === 'refunding' ? 'Retry refund request' : 'Request refund'}
                        </button>
                    )}
                </li>
            ))}
        </ul>
    );
}
