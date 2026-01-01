import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

const statusLabels = {
    draft: 'Draft',
    sent: 'Sent',
    paid: 'Paid',
    overdue: 'Overdue',
    cancelled: 'Cancelled',
};

export default function Index({ invoices, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('invoices.index'),
            { search, status },
            { preserveState: true, replace: true }
        );
    };

    const handleDelete = (invoice) => {
        if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
            router.delete(route('invoices.destroy', invoice.id));
        }
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        router.get(route('invoices.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Invoices" />

            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-semibold text-gray-900">Invoices</h1>
                    <Link href={route('invoices.create')}>
                        <PrimaryButton>Create Invoice</PrimaryButton>
                    </Link>
                </div>

                {/* Filters */}
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div className="p-6">
                        <form onSubmit={handleSearch} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel>Search</InputLabel>
                                    <TextInput
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Invoice number or client name"
                                    />
                                </div>
                                <div>
                                    <InputLabel>Status</InputLabel>
                                    <select
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="sent">Sent</option>
                                        <option value="paid">Paid</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div className="flex items-end gap-2">
                                    <PrimaryButton type="submit">Search</PrimaryButton>
                                    <SecondaryButton type="button" onClick={clearFilters}>
                                        Clear
                                    </SecondaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 bg-white border-b border-gray-200">
                        <h2 className="text-xl font-semibold text-gray-800">All Invoices</h2>
                    </div>
                    <div className="p-6">
                        {invoices.data.length === 0 ? (
                            <div className="text-center py-12">
                                <p className="text-gray-500 mb-4">No invoices yet</p>
                                <Link href={route('invoices.create')}>
                                    <PrimaryButton>Create your first invoice</PrimaryButton>
                                </Link>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Invoice Number
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Client
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Issue Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Due Date
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {invoices.data.map((invoice) => (
                                            <tr key={invoice.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {invoice.invoice_number}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {invoice.client?.name || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[invoice.status]}`}>
                                                        {statusLabels[invoice.status]}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    ${parseFloat(invoice.total).toFixed(2)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(invoice.issue_date).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(invoice.due_date).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={route('invoices.show', invoice.id)}>
                                                            <SecondaryButton>View</SecondaryButton>
                                                        </Link>
                                                        <Link href={route('invoices.edit', invoice.id)}>
                                                            <SecondaryButton 
                                                                disabled={!invoice.can_be_modified}
                                                                className={!invoice.can_be_modified ? 'opacity-50 cursor-not-allowed' : ''}
                                                            >
                                                                Edit
                                                            </SecondaryButton>
                                                        </Link>
                                                        <DangerButton
                                                            onClick={() => handleDelete(invoice)}
                                                            disabled={!invoice.can_be_modified}
                                                            className={!invoice.can_be_modified ? 'opacity-50 cursor-not-allowed' : ''}
                                                        >
                                                            Delete
                                                        </DangerButton>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {invoices.links && (
                            <div className="mt-6 flex justify-center">
                                <div className="flex gap-2">
                                    {invoices.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded ${
                                                link.active
                                                    ? 'bg-blue-500 text-white'
                                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
