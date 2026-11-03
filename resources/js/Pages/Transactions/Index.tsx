import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

type Transaction = {
    id: number;
    description: string;
    amount: number;
    transaction_date: string;
    type: string;
    reference: string | null;
};

type Props = {
    transactions: Transaction[];
};

export default function Index({ transactions }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Transactions
                </h2>
            }
        >
            <Head title="Transactions" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm border-t-4 border-t-blue-500">
                        {/* <div className="px-6 py-4">
                            <h3 className="text-lg font-semibold">Recently extracted transactions</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                This page shows the latest transactions extracted from uploaded documents.
                            </p>
                        </div> */}
                        <div className="border-t border-gray-200 px-6 py-4">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 className="text-lg font-semibold">Recently extracted transactions <span className="mt-2 text-sm text-gray-600">
                                        (This page shows the latest transactions extracted from uploaded documents.)
                                    </span></h3>
                                    {/* <p className="mt-2 text-sm text-gray-600">
                                        This page shows the latest transactions extracted from uploaded documents.
                                    </p> */}
                                </div>
                                <div>
                                    <a
                                        href={route('transactions.export')} // eslint-disable-line no-undef
                                        className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                    >
                                        Export CSV
                                    </a>
                                </div>
                            </div>
                            <div className="mt-4 overflow-x-auto">
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
                                        {transactions.length === 0 ? (
                                            <tr>
                                                <td className="px-6 py-4 text-sm text-gray-500" colSpan={5}>
                                                    No transactions have been extracted yet.
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
            </div>
        </AuthenticatedLayout>
    );
}
