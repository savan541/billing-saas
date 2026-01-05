import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import EmptyState from '@/Components/EmptyState';
import ConfirmDialog from '@/Components/ConfirmDialog';
import LoadingSpinner from '@/Components/LoadingSpinner';

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
    const [deleteDialog, setDeleteDialog] = useState({ isOpen: false, invoice: null });
    const [isSearching, setIsSearching] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        setIsSearching(true);
        router.get(
            route('invoices.index'),
            { search, status },
            {
                preserveState: true, 
                replace: true,
                onFinish: () => setIsSearching(false)
            }
        );
    };

    const handleDelete = (invoice) => {
        setDeleteDialog({ isOpen: true, invoice });
    };

    const confirmDelete = () => {
        if (deleteDialog.invoice) {
            router.delete(route('invoices.destroy', deleteDialog.invoice.id), {
                onSuccess: () => setDeleteDialog({ isOpen: false, invoice: null })
            });
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
                <div className="mb-8 animate-fade-in">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Invoices</h1>
                            <p className="mt-2 text-gray-600">Manage your invoices and track payments</p>
                        </div>
                        <Link href={route('invoices.create')} className="transition-transform hover:scale-105">
                            <PrimaryButton className="shadow-lg hover:shadow-xl transition-shadow">
                                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Invoice
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white shadow-lg rounded-xl mb-8 border border-gray-100">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Filter Invoices</h3>
                        <form onSubmit={handleSearch} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel>Search</InputLabel>
                                    <TextInput
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Invoice number or client name"
                                        className="transition-all focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <InputLabel>Status</InputLabel>
                                    <select
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm transition-all"
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
                                    <PrimaryButton type="submit" disabled={isSearching} className="transition-all">
                                        {isSearching ? (
                                            <>
                                                <LoadingSpinner size="sm" className="mr-2" />
                                                Searching...
                                            </>
                                        ) : (
                                            <>
                                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                Search
                                            </>
                                        )}
                                    </PrimaryButton>
                                    <SecondaryButton type="button" onClick={clearFilters} className="transition-all">
                                        Clear
                                    </SecondaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div className="bg-white shadow-lg rounded-xl border border-gray-100">
                    <div className="p-6 bg-white border-b border-gray-100">
                        <h2 className="text-xl font-semibold text-gray-900">All Invoices</h2>
                    </div>
                    <div className="p-6">
                        {invoices.data.length === 0 ? (
                            <EmptyState
                                icon={
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                }
                                title="No invoices yet"
                                description="Create your first invoice to start billing your clients and tracking payments."
                                action={
                                    <Link href={route('invoices.create')}>
                                        <PrimaryButton className="transition-transform hover:scale-105">
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Create Your First Invoice
                                        </PrimaryButton>
                                    </Link>
                                }
                            />
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
                                    <tbody className="bg-white divide-y divide-gray-100">
                                        {invoices.data.map((invoice) => (
                                            <tr key={invoice.id} className="hover:bg-gray-50 transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <span className="font-mono">{invoice.invoice_number}</span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    {invoice.client?.name || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[invoice.status]} transition-all`}>
                                                        {statusLabels[invoice.status]}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    ${parseFloat(invoice.total).toFixed(2)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    {new Date(invoice.issue_date).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    {new Date(invoice.due_date).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={route('invoices.show', invoice.id)}>
                                                            <SecondaryButton className="transition-all hover:scale-105">
                                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                                View
                                                            </SecondaryButton>
                                                        </Link>
                                                        <Link href={route('invoices.edit', invoice.id)}>
                                                            <SecondaryButton 
                                                                disabled={!invoice.can_be_modified}
                                                                className={`transition-all hover:scale-105 ${!invoice.can_be_modified ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                            >
                                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                                Edit
                                                            </SecondaryButton>
                                                        </Link>
                                                        <DangerButton
                                                            onClick={() => handleDelete(invoice)}
                                                            disabled={!invoice.can_be_modified}
                                                            className={`transition-all hover:scale-105 ${!invoice.can_be_modified ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                        >
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
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
                            <div className="mt-8 flex justify-center">
                                <div className="flex gap-1">
                                    {invoices.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 rounded-md text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-blue-600 text-white shadow-md'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Confirmation Dialog */}
                        <ConfirmDialog
                            isOpen={deleteDialog.isOpen}
                            onClose={() => setDeleteDialog({ isOpen: false, invoice: null })}
                            onConfirm={confirmDelete}
                            title="Delete Invoice"
                            message={`Are you sure you want to delete invoice ${deleteDialog.invoice?.invoice_number}? This action cannot be undone and will permanently remove all associated data.`}
                            confirmText="Delete Invoice"
                            cancelText="Cancel"
                            type="danger"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
