import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Contract = {
    id: number;
    vendor_name: string;
    contract_number: string;
    start_date: string;
    end_date: string;
    value: number;
    currency: string;
    status: string;
    reference: string | null;
};

export default function Index() {
    const [contracts, setContracts] = useState<Contract[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchContracts();
    }, []);

    const fetchContracts = async () => {
        setLoading(true);

        try {
            const response = await fetch(route('contracts.list')); // eslint-disable-line no-undef
            const json = await response.json();
            setContracts(json.contracts.data || []);
        } catch (error) {
            // ignore for now
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Contracts</h2>}>
            <Head title="Contracts" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">Extracted contracts</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                Contracts extracted from uploaded documents.
                            </p>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Start</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">End</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Party</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contract #</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Value</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {loading ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={7}>Loading...</td>
                                            </tr>
                                        ) : contracts.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={7}>No contracts found yet.</td>
                                            </tr>
                                        ) : (
                                            contracts.map((c) => (
                                                <tr key={c.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.start_date}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.end_date}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.vendor_name}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.contract_number}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.value.toFixed(2)} {c.currency}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.status}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{c.reference ?? '—'}</td>
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
