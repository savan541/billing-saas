import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';

const statusColors = {
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabels = {
    active: 'Active',
    paused: 'Paused',
    cancelled: 'Cancelled',
};

const frequencyLabels = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

export default function Index({ recurringInvoices, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [frequency, setFrequency] = useState(filters.frequency || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('recurring-invoices.index'),
            { search, status, frequency },
            { preserveState: true, replace: true }
        );
    };

    const handleDelete = (recurringInvoice) => {
        if (confirm('Are you sure you want to delete this recurring invoice? This action cannot be undone.')) {
            router.delete(route('recurring-invoices.destroy', recurringInvoice.id));
        }
    };

    const handlePause = (recurringInvoice) => {
        router.post(route('recurring-invoices.pause', recurringInvoice.id));
    };

    const handleResume = (recurringInvoice) => {
        router.post(route('recurring-invoices.resume', recurringInvoice.id));
    };

    const handleCancel = (recurringInvoice) => {
        if (confirm('Are you sure you want to cancel this recurring invoice?')) {
            router.post(route('recurring-invoices.cancel', recurringInvoice.id));
        }
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setFrequency('');
        router.get(route('recurring-invoices.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Recurring Invoices" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-2xl font-semibold text-gray-900">Recurring Invoices</h1>
                                <Link href={route('recurring-invoices.create')}>
                                    <PrimaryButton>Create Recurring Invoice</PrimaryButton>
                                </Link>
                            </div>

                            {/* Filters */}
                            <form onSubmit={handleSearch} className="mb-6 space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <InputLabel htmlFor="search" value="Search" />
                                        <TextInput
                                            id="search"
                                            type="text"
                                            name="search"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Search by title or client..."
                                            className="mt-1 block w-full"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="status" value="Status" />
                                        <select
                                            id="status"
                                            name="status"
                                            value={status}
                                            onChange={(e) => setStatus(e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">All Statuses</option>
                                            <option value="active">Active</option>
                                            <option value="paused">Paused</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="frequency" value="Frequency" />
                                        <select
                                            id="frequency"
                                            name="frequency"
                                            value={frequency}
                                            onChange={(e) => setFrequency(e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">All Frequencies</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                    </div>
                                    <div className="flex items-end space-x-2">
                                        <PrimaryButton type="submit">Filter</PrimaryButton>
                                        <SecondaryButton type="button" onClick={clearFilters}>
                                            Clear
                                        </SecondaryButton>
                                    </div>
                                </div>
                            </form>

                            {/* Table */}
                            {recurringInvoices.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Title
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Client
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Frequency
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Next Run
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {recurringInvoices.data.map((recurringInvoice) => (
                                                <tr key={recurringInvoice.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Link
                                                            href={route('recurring-invoices.show', recurringInvoice.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 font-medium"
                                                        >
                                                            {recurringInvoice.title}
                                                        </Link>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {recurringInvoice.client?.name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {recurringInvoice.client?.currency_symbol || '$'}{parseFloat(recurringInvoice.amount).toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {frequencyLabels[recurringInvoice.frequency]}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[recurringInvoice.status]}`}>
                                                            {statusLabels[recurringInvoice.status]}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {new Date(recurringInvoice.next_run_date).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                        <Link href={route('recurring-invoices.show', recurringInvoice.id)}>
                                                            <span className="text-indigo-600 hover:text-indigo-900">View</span>
                                                        </Link>
                                                        <Link href={route('recurring-invoices.edit', recurringInvoice.id)}>
                                                            <span className="text-indigo-600 hover:text-indigo-900">Edit</span>
                                                        </Link>
                                                        {recurringInvoice.status === 'active' && (
                                                            <button
                                                                onClick={() => handlePause(recurringInvoice)}
                                                                className="text-yellow-600 hover:text-yellow-900"
                                                            >
                                                                Pause
                                                            </button>
                                                        )}
                                                        {recurringInvoice.status === 'paused' && (
                                                            <button
                                                                onClick={() => handleResume(recurringInvoice)}
                                                                className="text-green-600 hover:text-green-900"
                                                            >
                                                                Resume
                                                            </button>
                                                        )}
                                                        {recurringInvoice.status !== 'cancelled' && (
                                                            <button
                                                                onClick={() => handleCancel(recurringInvoice)}
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                Cancel
                                                            </button>
                                                        )}
                                                        <button
                                                            onClick={() => handleDelete(recurringInvoice)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">No recurring invoices found.</p>
                                    <Link href={route('recurring-invoices.create')} className="mt-4 inline-block">
                                        <PrimaryButton>Create your first recurring invoice</PrimaryButton>
                                    </Link>
                                </div>
                            )}

                            {/* Pagination */}
                            {recurringInvoices.data.length > 0 && (
                                <div className="mt-6">
                                    <div className="flex justify-between items-center">
                                        <div className="text-sm text-gray-700">
                                            Showing {recurringInvoices.from} to {recurringInvoices.to} of {recurringInvoices.total} results
                                        </div>
                                        <div className="flex space-x-2">
                                            {recurringInvoices.prev_page_url && (
                                                <Link
                                                    href={recurringInvoices.prev_page_url}
                                                    className="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                                >
                                                    Previous
                                                </Link>
                                            )}
                                            {recurringInvoices.next_page_url && (
                                                <Link
                                                    href={recurringInvoices.next_page_url}
                                                    className="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                                >
                                                    Next
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
