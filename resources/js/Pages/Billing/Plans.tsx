import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

type Plan = {
    id: number;
    slug: string;
    name: string;
    description?: string;
    price: number;
    currency: string;
};

export default function Plans() {
    const [plans, setPlans] = useState<Plan[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchPlans();
    }, []);

    const fetchPlans = async () => {
        setLoading(true);
        try {
            const res = await fetch(route('billing.plans')); // eslint-disable-line no-undef
            const json = await res.json();
            setPlans(json.plans || []);
        } catch (e) {
            // noop
        } finally {
            setLoading(false);
        }
    };

    const subscribe = async (planId: number) => {
        // Previous direct fetch calls omitted Laravel's CSRF header and were rejected with HTTP 419.
        const res = await csrfFetch(route('billing.subscriptions.store'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: planId }),
        });

        if (res.ok) {
            alert('Subscribed successfully');
        } else {
            alert('Failed to subscribe');
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Billing Plans</h2>}>
            <Head title="Plans" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {plans.map((plan) => (
                            <div key={plan.id} className="rounded-lg border bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-semibold">{plan.name}</h3>
                                <p className="mt-2 text-sm text-gray-600">{plan.description}</p>
                                <p className="mt-4 text-xl font-bold">{plan.price} {plan.currency}</p>
                                <div className="mt-4">
                                    <button onClick={() => subscribe(plan.id)} className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Subscribe
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {loading && <p className="mt-4 text-sm text-gray-500">Loading plans...</p>}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
