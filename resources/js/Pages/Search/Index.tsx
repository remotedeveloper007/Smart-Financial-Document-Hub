import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Transaction = {
    id: number;
    description: string;
    amount: number;
    transaction_date: string;
    type: string;
    reference: string | null;
};

export default function Index() {
    const [query, setQuery] = useState('');
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        setLoading(true);
        setError('');

        try {
            const response = await fetch(route('search.transactions', { q: query })); // eslint-disable-line no-undef

            if (!response.ok) {
                throw new Error('Unable to fetch search results.');
            }

            const json = await response.json();
            setTransactions(json.transactions);
        } catch (caughtError) {
            setError('Failed to search transactions.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Search Transactions
                </h2>
            }
        >
            <Head title="Search Transactions" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold">Search extracted transactions</h3>
                        <form className="mt-4 flex gap-3" onSubmit={submit}>
                            <input
                                type="text"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Search by description, reference, or type"
                                className="w-full rounded-md border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                            />
                            <button
                                type="submit"
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Search
                            </button>
                        </form>

                        {error && (
                            <div className="mt-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <div className="mt-6 overflow-hidden rounded-md border border-gray-200">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Description
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Amount
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Reference
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {loading ? (
                                        <tr>
                                            <td className="px-6 py-4 text-sm text-gray-500" colSpan={5}>
                                                Searching...
                                            </td>
                                        </tr>
                                    ) : transactions.length === 0 ? (
                                        <tr>
                                            <td className="px-6 py-4 text-sm text-gray-500" colSpan={5}>
                                                Enter a search term and click Search.
                                            </td>
                                        </tr>
                                    ) : (
                                        transactions.map((transaction) => (
                                            <tr key={transaction.id}>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {transaction.transaction_date}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {transaction.description}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {transaction.amount.toFixed(2)}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {transaction.type}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {transaction.reference ?? '—'}
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
        </AuthenticatedLayout>
    );
}
