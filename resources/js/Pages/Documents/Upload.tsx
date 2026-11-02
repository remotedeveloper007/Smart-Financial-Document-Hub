import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';
import { useState } from 'react';

type DocumentPayload = {
    id: number;
    name: string;
    file_path: string;
    status: string;
    created_at: string;
};

export default function Upload() {
    const [submitting, setSubmitting] = useState(false);
    const [requestError, setRequestError] = useState('');
    const [successMessage, setSuccessMessage] = useState('');
    const form = useForm({
        name: '',
        file: null as File | null,
    });

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setSubmitting(true);
        setRequestError('');
        setSuccessMessage('');
        form.clearErrors();

        const formData = new FormData();
        formData.append('name', form.data.name);

        if (form.data.file) {
            formData.append('file', form.data.file);
        }

        try {
            const response = await csrfFetch(route('documents.store'), {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: formData,
            });

            if (response.status === 422) {
                const payload = await response.json();
                form.setError({
                    name: payload.errors?.name?.[0] ?? '',
                    file: payload.errors?.file?.[0] ?? '',
                });
                return;
            }

            if (!response.ok) {
                throw new Error('The upload could not be completed. Please try again.');
            }

            const document = (await response.json()) as DocumentPayload;
            setSuccessMessage(`Upload received. Private malware scanning is queued (document ${document.id}).`);
            form.reset();
        } catch (error) {
            setRequestError(error instanceof Error ? error.message : 'The upload could not be completed.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Upload Document
                </h2>
            }
        >
            <Head title="Upload Document" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow sm:rounded-lg">
                        <h3 className="text-lg font-semibold">Upload a financial document</h3>
                        <form onSubmit={submit} className="mt-6 space-y-6">
                            <div>
                                <label htmlFor="document-name" className="block text-sm font-medium text-gray-700">
                                    Document name
                                </label>
                                <input
                                    id="document-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    required
                                />
                                {form.errors.name && <p className="mt-1 text-sm text-red-700" role="alert">{form.errors.name}</p>}
                            </div>

                            <div>
                                <label htmlFor="document-file" className="block text-sm font-medium text-gray-700">
                                    PDF file
                                </label>
                                <input
                                    id="document-file"
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(event) =>
                                        form.setData(
                                            'file',
                                            event.target.files ? event.target.files[0] : null,
                                        )
                                    }
                                    className="mt-1 block w-full text-sm text-gray-900"
                                    required
                                />
                                {form.errors.file && <p className="mt-1 text-sm text-red-700" role="alert">{form.errors.file}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={submitting}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {submitting ? 'Uploading...' : 'Upload Document'}
                            </button>
                            {successMessage && <p className="text-sm text-green-700" role="status">{successMessage}</p>}
                            {requestError && <p className="text-sm text-red-700" role="alert">{requestError}</p>}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
