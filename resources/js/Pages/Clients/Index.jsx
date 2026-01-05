import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import ConfirmDialog from '@/Components/ConfirmDialog';
import LoadingSpinner from '@/Components/LoadingSpinner';

export default function ClientsIndex({ clients, currencyOptions }) {
    const { flash } = usePage().props;
    const [editingClient, setEditingClient] = useState(null);
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [deleteDialog, setDeleteDialog] = useState({ isOpen: false, client: null });

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        company: '',
        address: '',
        tax_id: '',
        tax_country: '',
        tax_state: '',
        tax_rate: '',
        tax_exempt: false,
        tax_exemption_reason: '',
        currency: 'USD',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editingClient) {
            put(route('clients.update', editingClient.id), {
                onSuccess: () => {
                    reset();
                    setEditingClient(null);
                },
            });
        } else {
            post(route('clients.store'), {
                onSuccess: () => {
                    reset();
                    setShowCreateForm(false);
                },
            });
        }
    };

    const handleEdit = (client) => {
        setEditingClient(client);
        setData({
            name: client.name,
            email: client.email || '',
            phone: client.phone || '',
            company: client.company || '',
            address: client.address || '',
            tax_id: client.tax_id || '',
            tax_country: client.tax_country || '',
            tax_state: client.tax_state || '',
            tax_rate: client.tax_rate || '',
            tax_exempt: client.tax_exempt || false,
            tax_exemption_reason: client.tax_exemption_reason || '',
            currency: client.currency || 'USD',
        });
        setShowCreateForm(false);
    };

    const handleDelete = (client) => {
        setDeleteDialog({ isOpen: true, client });
    };

    const confirmDelete = () => {
        if (deleteDialog.client) {
            destroy(route('clients.destroy', deleteDialog.client.id), {
                onSuccess: () => setDeleteDialog({ isOpen: false, client: null })
            });
        }
    };

    const cancelEdit = () => {
        setEditingClient(null);
        reset();
    };

    const cancelCreate = () => {
        setShowCreateForm(false);
        reset();
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="animate-fade-in">
                    <h2 className="text-3xl font-bold text-gray-900">Clients</h2>
                    <p className="mt-2 text-gray-600">Manage your client information and billing details</p>
                </div>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-lg rounded-xl border border-gray-100">
                        <div className="p-6">
                            {flash?.success && (
                                <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg animate-fade-in">
                                    <div className="flex items-center">
                                        <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                        </svg>
                                        {flash.success}
                                    </div>
                                </div>
                            )}

                            {(showCreateForm || editingClient) && (
                                <div className="mb-8 p-6 bg-gradient-to-br from-gray-50 to-blue-50 rounded-xl border border-gray-200">
                                    <h3 className="text-xl font-semibold mb-6 text-gray-900">
                                        {editingClient ? 'Edit Client' : 'Create New Client'}
                                    </h3>
                                    <form onSubmit={handleSubmit}>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                                    Name *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    required
                                                />
                                                {errors.name && (
                                                    <p className="mt-2 text-sm text-red-600 flex items-center">
                                                        <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                        {errors.name}
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                                    Email
                                                </label>
                                                <input
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                />
                                                {errors.email && (
                                                    <p className="mt-2 text-sm text-red-600 flex items-center">
                                                        <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                        {errors.email}
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Phone
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.phone}
                                                    onChange={(e) => setData('phone', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.phone && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Company
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company}
                                                    onChange={(e) => setData('company', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.company && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.company}</p>
                                                )}
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Address
                                                </label>
                                                <textarea
                                                    value={data.address}
                                                    onChange={(e) => setData('address', e.target.value)}
                                                    rows={3}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.address && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                                )}
                                            </div>

                                            <h4 className="md:col-span-2 text-lg font-medium mt-6 mb-4 text-gray-800 border-t pt-4">
                                                Tax Information
                                            </h4>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Currency
                                                </label>
                                                <select
                                                    value={data.currency}
                                                    onChange={(e) => setData('currency', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    {currencyOptions.map(option => (
                                                        <option key={option.value} value={option.value}>
                                                            {option.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors.currency && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.currency}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tax ID (VAT/GST)
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_id}
                                                    onChange={(e) => setData('tax_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g., VAT123456789"
                                                />
                                                {errors.tax_id && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_id}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Country
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_country}
                                                    onChange={(e) => setData('tax_country', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="US, CA, UK, etc."
                                                    maxLength={2}
                                                />
                                                {errors.tax_country && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_country}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    State/Province
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_state}
                                                    onChange={(e) => setData('tax_state', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="CA, NY, ON, etc."
                                                    maxLength={50}
                                                />
                                                {errors.tax_state && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_state}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tax Rate (%)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    value={data.tax_rate ? (parseFloat(data.tax_rate) * 100) : ''}
                                                    onChange={(e) => setData('tax_rate', e.target.value ? parseFloat(e.target.value) / 100 : '')}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="8.25 (for 8.25%)"
                                                />
                                                <p className="mt-1 text-xs text-gray-500">Enter percentage (e.g., 8.25 for 8.25%)</p>
                                                {errors.tax_rate && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_rate}</p>
                                                )}
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.tax_exempt}
                                                        onChange={(e) => setData('tax_exempt', e.target.checked)}
                                                        className="mr-2"
                                                    />
                                                    <span className="text-sm font-medium text-gray-700">
                                                        Tax Exempt
                                                    </span>
                                                </label>
                                                {errors.tax_exempt && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_exempt}</p>
                                                )}
                                            </div>

                                            {data.tax_exempt && (
                                                <div className="md:col-span-2">
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Exemption Reason
                                                    </label>
                                                    <textarea
                                                        value={data.tax_exemption_reason}
                                                        onChange={(e) => setData('tax_exemption_reason', e.target.value)}
                                                        rows={2}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                        placeholder="Reason for tax exemption (e.g., Resale, Charity, Government)"
                                                    />
                                                    {errors.tax_exemption_reason && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.tax_exemption_reason}</p>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        <div className="mt-8 flex gap-3">
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center"
                                            >
                                                {processing ? (
                                                    <>
                                                        <LoadingSpinner size="sm" className="mr-2" />
                                                        {editingClient ? 'Updating...' : 'Creating...'}
                                                    </>
                                                ) : (
                                                    <>
                                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        {editingClient ? 'Update Client' : 'Create Client'}
                                                    </>
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={editingClient ? cancelEdit : cancelCreate}
                                                disabled={processing}
                                                className="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {!showCreateForm && !editingClient && (
                                <button
                                    onClick={() => setShowCreateForm(true)}
                                    className="mb-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center"
                                >
                                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    Add New Client
                                </button>
                            )}

                            {clients.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Email
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Phone
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Company
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Address
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100">
                                            {clients.map((client) => (
                                                <tr key={client.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {client.name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {client.email || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {client.phone || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {client.company || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-600">
                                                        {client.address || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button
                                                            onClick={() => handleEdit(client)}
                                                            className="text-blue-600 hover:text-blue-900 mr-4 transition-colors flex items-center"
                                                        >
                                                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(client)}
                                                            className="text-red-600 hover:text-red-900 transition-colors flex items-center"
                                                        >
                                                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <EmptyState
                                    icon={
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    }
                                    title="No clients yet"
                                    description="Add your first client to start creating invoices and managing your business relationships."
                                    action={
                                        <button
                                            onClick={() => setShowCreateForm(true)}
                                            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center"
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            Add Your First Client
                                        </button>
                                    }
                                />
                            )}

                            {/* Confirmation Dialog */}
                            <ConfirmDialog
                                isOpen={deleteDialog.isOpen}
                                onClose={() => setDeleteDialog({ isOpen: false, client: null })}
                                onConfirm={confirmDelete}
                                title="Delete Client"
                                message={`Are you sure you want to delete ${deleteDialog.client?.name}? This action cannot be undone and will remove all associated invoices and data.`}
                                confirmText="Delete Client"
                                cancelText="Cancel"
                                type="danger"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
