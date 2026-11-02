import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Receipt = {
    id: number;
    vendor_name: string;
    receipt_number: string;
    issue_date: string;
    total_amount: number;
    currency: string;
    status: string;
    reference: string | null;
};

export default function Index() {
    const [receipts, setReceipts] = useState<Receipt[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchReceipts();
    }, []);

    const fetchReceipts = async () => {
        setLoading(true);

        try {
            const response = await fetch(route('receipts.list')); // eslint-disable-line no-undef
            const json = await response.json();
            setReceipts(json.receipts.data || []);
        } catch (error) {
            // ignore fetch errors for now
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Receipts</h2>}>
            <Head title="Receipts" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">Extracted receipts</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                This page shows receipts extracted from uploaded financial documents.
                            </p>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Vendor</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Receipt #</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {loading ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={6}>Loading...</td>
                                            </tr>
                                        ) : receipts.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={6}>No receipts found yet.</td>
                                            </tr>
                                        ) : (
                                            receipts.map((receipt) => (
                                                <tr key={receipt.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.issue_date}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.vendor_name}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.receipt_number}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.total_amount.toFixed(2)} {receipt.currency}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.status}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{receipt.reference ?? '—'}</td>
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
