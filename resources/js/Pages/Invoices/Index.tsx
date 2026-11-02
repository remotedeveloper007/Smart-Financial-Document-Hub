import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Invoice = {
    id: number;
    vendor_name: string;
    invoice_number: string;
    issue_date: string;
    due_date: string;
    total_amount: number;
    currency: string;
    status: string;
    reference: string | null;
};

export default function Index() {
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchInvoices();
    }, []);

    const fetchInvoices = async () => {
        setLoading(true);

        try {
            const response = await fetch(route('invoices.list')); // eslint-disable-line no-undef
            const json = await response.json();
            setInvoices(json.invoices.data || []);
        } catch (error) {
            // ignore fetch errors for now
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Invoices</h2>}>
            <Head title="Invoices" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">Extracted invoices</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                This page shows invoices extracted from uploaded financial documents.
                            </p>
                        </div>
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Vendor</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice #</th>
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
                                        ) : invoices.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={6}>No invoices found yet.</td>
                                            </tr>
                                        ) : (
                                            invoices.map((invoice) => (
                                                <tr key={invoice.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.issue_date}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.vendor_name}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.invoice_number}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.total_amount.toFixed(2)} {invoice.currency}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.status}</td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">{invoice.reference ?? '—'}</td>
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
